# Integracao Asaas em producao

## Objetivo

Criar e acompanhar cobrancas Asaas sem marcar um pedido como pago antes da confirmacao financeira.

## Escopo

Clientes, PIX com QR Code e copia e cola, boleto, cartao, estorno total e parcial, chargeback, webhook autenticado, inbox idempotente, processamento e reconciliacao agendados.

## Fora do escopo

Primeira cobranca real, ainda bloqueada por `ASAAS_LIVE_ENABLED=false`.

## Regras de negocio

- Documento e obrigatorio para pedidos pagos pelo Asaas.
- Criacao de cobranca resulta em `pending`; somente webhook confirma.
- PIX confirma em `PAYMENT_RECEIVED`; cartao e boleto podem confirmar em `PAYMENT_CONFIRMED`.
- Recusa, exclusao ou cancelamento de boleto liberam reserva e cancelam o pedido.
- Evento duplicado nao repete transicao.
- Reserva vencida apos recebimento provoca estorno compensatorio.
- Somente estornos com estado `DONE` entram no valor devolvido.
- Estorno parcial e cumulativo e nunca pode reduzir o valor ja registrado.
- Chargeback permanece visivel no pagamento e no snapshot do pedido.
- Checkout PIX cria a cobranca imediatamente, consulta `/payments/{id}/pixQrCode` e apresenta as instrucoes somente a sessao compradora ou ao dono autenticado.
- Falha antes de receber o ID do provedor devolve o pagamento para `pending`, permitindo retry seguro.

## Fluxo principal

No checkout pago, a cobranca e criada imediatamente para apresentar as instrucoes ao cliente. O outbox permanece como fallback, consulta por `externalReference` antes de criar e nao duplica uma cobranca ja vinculada. O endpoint `/webhooks/asaas` autentica, reduz e grava o evento; o scheduler aplica a transicao. A cada 15 minutos, pagamentos abertos sao consultados no Asaas e transformados nos mesmos eventos internos para recuperar webhooks perdidos.

## Fluxos alternativos

Timeout permanece retryable. Eventos informativos sao auditados sem alterar pedido. Estorno total atualiza pagamento e pedido; estorno parcial preserva o pedido pago e registra o valor devolvido; chargeback registra o estado financeiro sem alterar indevidamente a expedicao.

## Casos de uso

`ProcessPayment`, `ReceiveAsaasWebhook`, `ProcessAsaasWebhooks` e `ReconcileAsaasPayments`.

## Arquitetura

`AsaasPaymentGateway` implementa as portas de cobranca e consulta. `PaymentWebhookInbox` separa recepcao HTTP de processamento financeiro e tambem recebe snapshots da reconciliacao.

## Portas e adaptadores

HTTP do Laravel para Asaas; persistencia SQL para clientes e inbox; portas de Ordering e Inventory para efeitos locais.

## Persistencia

`payment_provider_customers` evita clientes duplicados, `payment_webhook_events` guarda eventos saneados e seus estados, e `payment_payments.refunded_amount` guarda o acumulado devolvido em centavos.

## Transacoes

Rede ocorre fora da transacao. Pedido, pagamento e estoque mudam juntos depois do lock.

## Idempotencia

Referencia externa do pedido, ID do evento, fingerprint da reconciliacao e transicoes monotonicamente crescentes impedem repeticoes ou regressao do valor estornado.

## Seguranca

Chave e token ficam no ambiente. Webhook usa `asaas-access-token`, comparacao constante, limite de requisicoes e exclusao CSRF apenas na rota dedicada.

## Eventos

Somente eventos de cobrancas selecionados no Asaas sao aceitos e novos campos desconhecidos sao descartados.

## Interface

A URL publica configurada e `https://fabricafardamento.gejalabs.com.br/webhooks/asaas`. A pagina de confirmacao exibe QR Code, copia e cola, vencimento e atualiza o estado automaticamente.

## Testes automatizados

Contrato HTTP simulado, trava de producao, token invalido, duplicacao, confirmacao de estoque/pedido, estorno parcial, chargeback e reconciliacao sem acesso real.

## Casos de QA

Consulte `docs/qa/2026-07-13-asaas-production-integration.md`.

## Como validar

Publicar, executar migrations e scheduler, validar HTTPS e somente entao habilitar a primeira cobranca controlada.

## Riscos e limitacoes

Sem sandbox, a validacao final movimentara dinheiro real. A reconciliacao depende do scheduler ativo e a validacao de concorrencia ainda precisa ocorrer em MySQL/InnoDB no ambiente publicado.
