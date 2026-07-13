# Processamento assincrono de pagamentos

## Decisao

O checkout persiste a intencao e uma mensagem de outbox, mas nunca chama o gateway dentro da transacao. Um processador posterior realiza a chamada e aplica o resultado em uma nova transacao.

## Motivo

Gateways podem atrasar, falhar ou responder de forma ambigua. Manter locks durante rede aumenta deadlocks e indisponibilidade. A outbox garante que uma intencao confirmada no banco nao seja perdida.

## Estados

`pending -> processing -> paid|declined`; timeout realiza `processing -> pending`. Estorno realiza `paid -> refunded`.

## Consistencia

- Aprovacao local confirma reserva, pagamento e pedido juntos.
- Recusa libera reserva, pagamento e pedido juntos.
- Uma aprovacao cuja reserva nao pode ser confirmada e estornada de forma idempotente antes do cancelamento local.
- Chaves estaveis permitem retry sem nova cobranca.
- Antes da conversao do carrinho, `EnsurePaymentGatewayReady` usa a porta `PaymentGatewayReadiness` para rejeitar configuracao local desabilitada ou invalida sem criar pedido parcial.
- Uma falha apos o commit permanece `pending` com `failure_code`; a interface diferencia falha retryable de processamento ativo e o outbox pode tentar novamente.

## Adaptabilidade

Asaas e gateway falso implementam `PaymentGateway` e sua verificacao de prontidao sem expor configuracao a Presentation. Webhook e reconciliacao chamam as mesmas transicoes, com autenticidade e deduplicacao.
