# Plano de prontidao funcional da loja

Atualizado em 2026-07-13. Este arquivo acompanha a ordem obrigatoria definida no `AGENTS.md`.

Legenda: `[x]` concluido e verificado; `[ ]` ainda falta.

## Ordem de execucao

- [x] Checkout transacional unico
    - [x] Snapshot de cliente, endereco, itens, frete, pagamento escolhido e totais
    - [x] Revalidacao de produto, SKU, variacao, preco e moeda no servidor
    - [x] Consumo atomico de cupom com bloqueio concorrente
    - [x] Reserva de estoque, pedido e conversao do carrinho na mesma transacao
    - [x] Outbox transacional para notificacoes apos commit
    - [x] Repeticao pelo mesmo carrinho retorna o mesmo pedido
- [x] Estoque unificado por SKU/variante
    - [x] Remover saldo operacional do JSON de variacoes
    - [x] Migrar os saldos existentes sem perda
    - [x] Usar a mesma fonte no catalogo, carrinho, checkout e painel
    - [x] Validar bloqueios e concorrencia no adaptador transacional
- [x] Confirmacao e expiracao das reservas
    - [x] Caso de uso idempotente para confirmar uma reserva ativa
    - [x] Acionar confirmacao quando o pagamento for aprovado
    - [x] Caso de uso idempotente para liberar uma reserva ativa
    - [x] Acionar liberacao no cancelamento/falha definitiva
    - [x] Expirar reservas vencidas com comando agendado idempotente
    - [x] Auditar separadamente deltas fisico e reservado
- [x] Gateway de pagamento falso
    - [x] Criar intencao, tentativa e historico persistidos
    - [x] Simular aprovacao, recusa e timeout de forma deterministica
    - [x] Cobrir retry sem duplicar cobranca
    - [x] Confirmar/liberar estoque conforme resultado
    - [x] Estornar de forma idempotente e compensar reserva vencida
- [ ] Gateway real Asaas
    - [x] Adaptador de producao com trava explicita para cobrancas reais
    - [x] PIX, boleto e cartao conforme metodos habilitados na administracao
    - [x] Segredos somente por variaveis de ambiente
    - [x] Reutilizacao de cliente e consulta por referencia antes de cobrar
    - [x] QR Code PIX, copia e cola e atualizacao automatica na confirmacao
    - [ ] Validacao controlada com a primeira cobranca real
- [x] Webhook, idempotencia e reconciliacao
    - [x] Validar autenticidade antes de processar
    - [x] Deduplicar eventos do Asaas
    - [x] Processar eventos de forma assincrona
    - [x] Reconciliar cobrancas pendentes por tarefa agendada
- [x] Vinculo seguro entre usuario e pedido
    - [x] Persistir chave estrangeira do usuario no checkout autenticado
    - [x] Listar pedidos exclusivamente pelo ID autenticado
    - [x] Impedir reivindicacao automatica por coincidencia de e-mail
- [x] Cupom consumido atomicamente no checkout
- [ ] Suite final, observabilidade, seguranca e preparacao de producao
    - [x] Suite backend: 56 testes e 374 assercoes
    - [x] PHPStan sem erros
    - [x] Pint, ESLint, Prettier e TypeScript aprovados
    - [x] Build de producao aprovado
    - [ ] Validacao concorrente em MySQL/InnoDB
    - [ ] Monitoramento, alertas e rotinas de backup/restauracao de producao
    - [ ] Deploy e primeira cobranca Asaas controlada

## Pendencias conhecidas apos o primeiro ciclo

- O adaptador Asaas, webhook e reconciliacao estao implementados e ja foram validados com cobranca real. A producao exige `PAYMENT_GATEWAY=asaas`, `ASAAS_LIVE_ENABLED=true` e segredos somente no ambiente do servidor.
- O saldo de variacoes foi removido do JSON e agora possui fonte unica no modulo Inventory.
- Confirmacao, liberacao e expiracao de reservas estao implementadas; o gateway falso ligara aprovacao e falha aos casos de uso.
- O envio por outbox exige o scheduler do Laravel em execucao no ambiente operacional.
