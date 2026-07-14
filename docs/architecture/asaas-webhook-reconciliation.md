# Reconciliacao de pagamentos Asaas

## Decisao

Webhooks continuam sendo a fonte primaria de atualizacao. Depois de persistir o evento na inbox, o endpoint tenta reclamar e processar aquele ID imediatamente. Pagamentos Asaas nao finais continuam sendo consultados a cada 15 minutos; o resultado e convertido em `ProviderWebhookEvent` e entra na mesma inbox.

## Motivacao

O envio de webhook e pelo menos uma vez e pode sofrer atraso. Em hospedagem sem worker residente, esperar exclusivamente pelo scheduler aumenta o tempo de divergencia. Usar um unico processador para chamada imediata, retry e reconciliacao evita maquinas de estado concorrentes e conserva as mesmas transacoes, locks, historico e regras de estoque.

## Fluxo

O controller autentica, saneia e persiste o evento, então chama `ProcessAsaasWebhooks::handleEvent` com o ID recebido. A inbox faz claim atomico somente se ele estiver pendente. Falha de aplicacao devolve o evento para `pending` com backoff. `ReconcileAsaasPayments` seleciona pagamentos abertos, consulta `GET /payments/{id}`, reduz a resposta a campos financeiros seguros e cria um ID deterministico pelo estado observado. O mesmo processador aplica aprovacao, recusa, estorno ou chargeback dentro da transacao local.

## Persistencia e idempotencia

O valor devolvido e persistido em centavos em `payment_payments.refunded_amount`. Apenas itens de estorno `DONE` sao somados. A fingerprint do snapshot e a chave unica da inbox evitam reprocessamento, e o valor parcial nunca pode regredir.

## Seguranca e operacao

A consulta usa a mesma chave de producao, mas so executa quando gateway Asaas e `ASAAS_LIVE_ENABLED=true`. Enquanto a trava estiver desligada, o comando e um no-op. Payload completo, chave e token nao sao gravados. O scheduler precisa estar continuamente ativo no ambiente publicado.

## Consequencias

O estado local normalmente converge na mesma chamada do Asaas, inclusive em estornos. Duplicatas continuam inertes. O scheduler permanece obrigatorio para retries e eventos perdidos, e a loja precisa monitorar falhas do cron e da API.
