# Reconciliacao de pagamentos Asaas

## Decisao

Webhooks continuam sendo a fonte primaria de atualizacao, mas pagamentos Asaas nao finais sao consultados a cada 15 minutos. O resultado da consulta e convertido em `ProviderWebhookEvent` e entra na mesma inbox usada pelo endpoint publico.

## Motivacao

O envio de webhook e pelo menos uma vez e pode sofrer atraso. Usar um unico processador evita duas maquinas de estado concorrentes e conserva as mesmas transacoes, locks, historico e regras de estoque.

## Fluxo

`ReconcileAsaasPayments` seleciona pagamentos abertos, consulta `GET /payments/{id}`, reduz a resposta a campos financeiros seguros, cria um ID deterministico pelo estado observado e chama a inbox. `ProcessAsaasWebhooks` aplica aprovacao, recusa, estorno ou chargeback dentro da transacao local.

## Persistencia e idempotencia

O valor devolvido e persistido em centavos em `payment_payments.refunded_amount`. Apenas itens de estorno `DONE` sao somados. A fingerprint do snapshot e a chave unica da inbox evitam reprocessamento, e o valor parcial nunca pode regredir.

## Seguranca e operacao

A consulta usa a mesma chave de producao, mas so executa quando gateway Asaas e `ASAAS_LIVE_ENABLED=true`. Enquanto a trava estiver desligada, o comando e um no-op. Payload completo, chave e token nao sao gravados. O scheduler precisa estar continuamente ativo no ambiente publicado.

## Consequencias

Webhooks permanecem rapidos e o estado local converge mesmo quando um evento se perde. A loja assume chamadas periodicas adicionais ao Asaas e precisa monitorar falhas do scheduler e da API.
