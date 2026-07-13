# QA - integracao Asaas producao

## Objetivo
Validar o contrato sem gerar cobranca real.
## Pre-condicoes
Segredos presentes e `ASAAS_LIVE_ENABLED=false`.
## Dados de teste
Respostas HTTP simuladas e evento PIX duplicado.
## Ambiente
Local, SQLite, API real nao chamada.
## Casos de sucesso
- ASAAS-01: criar cliente e cobranca simulados; resposta pendente e URL persistida; obtido conforme esperado; OK.
- ASAAS-02: receber confirmacao PIX autenticada; pedido, pagamento e reserva convergem; obtido conforme esperado; OK.
- ASAAS-03: consultar cobranca com estornos `DONE` e `PENDING`; somente `DONE` soma 3000 centavos; obtido conforme esperado; OK.
- ASAAS-04: reconciliar estorno parcial perdido; pagamento fica `partially_refunded` e acumula 1500 centavos; obtido conforme esperado; OK.
- ASAAS-04A: finalizar checkout PIX; criar cobranca, consultar QR Code e exibir payload somente ao comprador; obtido conforme esperado; OK.
- ASAAS-04B: selecionar cartao; campos condicionais aparecem e a cobranca simulada recebe cartao, titular e IP; obtido conforme esperado; OK.
## Casos de validacao
- ASAAS-05: checkout Asaas sem CPF/CNPJ; requisicao recusada; obtido conforme esperado; OK.
- ASAAS-06: estorno parcial regressivo ou repetido; valor local nao diminui nem duplica; coberto pela regra de dominio e idempotencia; OK.
- ASAAS-06A: numero, validade ou CVV invalidos; checkout recusa e numero/CVV nao entram no flash da sessao; obtido conforme esperado; OK.
## Casos de autorizacao
- ASAAS-07: token ausente ou invalido; endpoint retorna 403 antes da inbox; obtido conforme esperado; OK.
## Casos de falha
- ASAAS-08: trava de producao desligada; nenhuma requisicao HTTP e realizada; obtido conforme esperado; OK.
- ASAAS-09: estorno em andamento ou negado; snapshot do pedido registra o estado sem marcar reembolso concluido; implementado, validacao manual publicada pendente.
- ASAAS-09A: integracao desativada ou falha antes do ID Asaas; pagamento retorna a `pending` em vez de ficar preso em `processing`; obtido conforme esperado; OK.
- ASAAS-09B: Asaas devolve HTTP 400 para cartao; pagamento fica recusado, pedido cancelado e estoque liberado sem ID ficticio; obtido conforme esperado; OK.
## Casos de concorrencia
- ASAAS-10: webhook e reconciliacao simultaneos; inbox e locks evitam transicao duplicada; cobertura SQLite aprovada, MySQL/InnoDB pendente.
## Casos de idempotencia
- ASAAS-11: mesmo ID de webhook duas vezes; uma linha e uma transicao; obtido conforme esperado; OK.
- ASAAS-12: mesmo snapshot reconciliado novamente; fingerprint nao cria nova transicao; implementado; OK.
## Casos de regressao
- ASAAS-13: testes focados de checkout e pagamentos; 24 testes e 149 assercoes aprovados.
- ASAAS-14: suite completa; 61 testes e 398 assercoes aprovados.
## Casos responsivos
- ASAAS-15: campos do cartao usam uma coluna no celular e duas em telas maiores; revisao estrutural concluida; validacao visual publicada pendente.
## Casos de acessibilidade
- ASAAS-16: campos possuem labels, autocomplete semantico, teclado numerico e erros associados visualmente; revisao estrutural concluida; teste manual com leitor de tela pendente.
## Evidencias
Rota autenticada, QR Code PIX protegido, processador a cada minuto, reconciliacao a cada 15 minutos, migrations aplicadas e testes automatizados aprovados.
## Riscos conhecidos
Primeira cobranca real, deploy e concorrencia MySQL/InnoDB pendentes. `ASAAS_LIVE_ENABLED=false` permanece obrigatorio ate a validacao controlada.
