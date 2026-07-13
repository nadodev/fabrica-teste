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

## Adaptabilidade

O Asaas implementara `PaymentGateway` sem alterar os casos de uso. Webhook e reconciliacao chamarao as mesmas transicoes, adicionando verificacao de autenticidade e deduplicacao.
