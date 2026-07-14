# QA — Sincronização de estorno Asaas

## Objetivo

Validar que um estorno feito no Asaas atualiza imediatamente o mesmo pagamento e o pedido local, sem criar uma segunda cobrança.

## Pré-condições

Pagamento Asaas vinculado ao pedido, token de webhook configurado e migrations da inbox aplicadas.

## Dados de teste

Cobrança de cartão `pay_webhook_refund`, valor de R$ 100,00, eventos `PAYMENT_CONFIRMED` e `PAYMENT_REFUNDED`.

## Ambiente

Laravel local, SQLite de teste e requisições HTTP simuladas; nenhuma operação foi enviada ao Asaas real.

## Casos de sucesso

### REF-01 — Estorno total imediato

- Cenário: cobrança de cartão paga é estornada no Asaas.
- Pré-condições: pagamento e pedido estão pagos.
- Passos: enviar webhook autenticado `PAYMENT_REFUNDED`.
- Resultado esperado: mesmo pagamento fica `refunded`, valor estornado igual ao total e pedido fica `refunded`.
- Resultado obtido: pagamento registrou 10000 centavos devolvidos e pedido/pagamento ficaram estornados na própria requisição.
- Status: OK.

## Casos de validação

### REF-02 — Histórico financeiro

- Cenário: estorno muda o estado do pagamento existente.
- Pré-condições: pagamento pago persistido.
- Passos: processar estorno total.
- Resultado esperado: uma transição para `refunded`, sem novo pagamento.
- Resultado obtido: um único registro de histórico foi criado para o estorno e a cobrança original foi preservada.
- Status: OK.

## Casos de autorização

### REF-03 — Token inválido

- Cenário: origem sem o token correto tenta informar estorno.
- Pré-condições: token de webhook com pelo menos 32 caracteres.
- Passos: enviar payload sem `asaas-access-token` válido.
- Resultado esperado: HTTP 403 e nenhum evento financeiro processado.
- Resultado obtido: cobertura existente do endpoint permaneceu aprovada.
- Status: OK.

## Casos de falha

### REF-04 — Pagamento ainda não localizado

- Cenário: evento chega antes de o vínculo local com a cobrança estar disponível.
- Pré-condições: ID Asaas desconhecido localmente.
- Passos: enviar `PAYMENT_REFUNDED` autenticado.
- Resultado esperado: resposta HTTP 200 para evitar bloqueio do webhook e evento pendente com erro saneado para retry.
- Resultado obtido: inbox ficou `pending`, uma tentativa registrada e retry agendado.
- Status: OK.

## Casos de concorrência

### REF-05 — Entregas simultâneas

- Cenário: duas requisições disputam o mesmo ID de evento.
- Pré-condições: constraint única na inbox.
- Passos: entregar o mesmo evento repetidamente.
- Resultado esperado: claim atômico permite uma aplicação financeira.
- Resultado obtido: idempotência e trava implementadas; concorrência real em MySQL/InnoDB não foi executada nesta rodada.
- Status: Pendente em produção controlada.

## Casos de idempotência

### REF-06 — Evento duplicado

- Cenário: Asaas reenvia o mesmo estorno.
- Pré-condições: primeiro envio processado.
- Passos: repetir o mesmo ID e payload.
- Resultado esperado: valor e histórico não duplicam.
- Resultado obtido: uma linha de inbox, uma transição para estornado e valor inalterado.
- Status: OK.

## Casos de regressão

### REF-07 — Processamento agendado

- Cenário: evento não pode ser aplicado imediatamente.
- Pré-condições: falha local transitória.
- Passos: consultar o estado da inbox após a tentativa.
- Resultado esperado: scheduler pode reclamar novamente o evento após o backoff.
- Resultado obtido: status retornou para `pending`; comando agendado existente foi preservado.
- Status: OK.

## Casos responsivos

### REF-08 — Exibição no admin

- Cenário: pedido estornado aparece nas telas administrativas existentes.
- Pré-condições: estado local `refunded`.
- Passos: consultar o mapeamento de status da listagem e detalhe.
- Resultado esperado: rótulo “Estornado”.
- Resultado obtido: mapeamento responsivo existente foi preservado; não houve mudança visual.
- Status: OK.

## Casos de acessibilidade

### REF-09 — Informação textual

- Cenário: status é comunicado sem depender apenas de cor.
- Pré-condições: pedido estornado renderizado.
- Passos: verificar rótulo do status.
- Resultado esperado: texto “Estornado” disponível.
- Resultado obtido: rótulo textual já existe nas duas telas administrativas.
- Status: OK.

## Evidências

Testes de feature autenticam o webhook, confirmam atualização imediata, histórico único, duplicidade inerte e fallback pendente.

## Riscos conhecidos

É necessário manter os eventos de Cobranças, incluindo `PAYMENT_REFUNDED`, habilitados no Asaas. O cron continua necessário para retry e reconciliação de eventos perdidos.
