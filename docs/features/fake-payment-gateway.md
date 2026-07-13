# Gateway de pagamento falso

## Objetivo

Executar o fluxo completo de pagamento sem provedor externo, com resultados deterministicos, persistencia, idempotencia e integracao real com pedido e estoque.

## Escopo

- Intencao criada dentro da transacao do checkout.
- Processamento assincrono por outbox.
- Aprovacao, recusa e timeout configuraveis.
- Tentativas e historico de estados persistidos.
- Confirmacao ou liberacao das reservas.
- Estorno total idempotente.
- Compensacao quando uma aprovacao encontra reserva indisponivel.

## Fora do escopo

- Uso em producao.
- Captura de cartao ou dados bancarios.
- Webhook e reconciliacao externa.
- Integracao Asaas.

## Regras de negocio

- Um pedido de pagamento possui uma unica intencao e chave de idempotencia.
- A mesma chave nunca cria duas transacoes no gateway falso.
- Aprovacao confirma estoque e marca pedido/pagamento como pagos na mesma transacao local.
- Recusa definitiva libera estoque e cancela o pedido.
- Timeout volta o pagamento para `pending`, preserva a reserva e permite retry.
- Estorno repetido nao duplica o valor devolvido.
- Aprovacao sem reserva valida e compensada por estorno e cancelamento.
- O gateway falso e proibido no ambiente `production`.

## Fluxo principal

1. Checkout grava pedido, reserva, intencao e mensagem `payment.requested` atomicamente.
2. `payments:process` reclama a mensagem.
3. `ProcessPayment` marca a tentativa antes de chamar o gateway.
4. O gateway retorna resultado estavel para a chave.
5. O caso de uso atualiza pagamento, pedido, estoque, tentativa e historico.
6. A mensagem e concluida; timeout a devolve para retry.

## Fluxos alternativos

- `declined`: libera reserva e cancela.
- `timeout`: registra tentativa e agenda retry sem nova transacao do provedor.
- Reserva vencida durante aprovacao: estorna e cancela.
- Estorno repetido: retorna o resultado existente.

## Casos de uso

- `CreatePaymentIntent`.
- `ProcessPayment`.
- `ProcessPaymentOutbox`.
- `RefundPayment`.

## Arquitetura

O modulo Payment contem entidade, estados, portas, casos de uso e adaptadores. Ordering comunica-se pelo caso de uso de criacao; Payment usa portas publicas de Ordering e Inventory para aplicar o resultado.

## Portas e adaptadores

- `PaymentGateway` -> `FakePaymentGateway`.
- `PaymentRepository` -> `DatabasePaymentRepository`.
- `OutboxQueue` -> fila transacional compartilhada.

## Persistencia

- `payment_payments`: intencao e estado atual.
- `payment_attempts`: tentativas de cobranca e estorno.
- `payment_status_history`: transicoes auditaveis.
- `payment_fake_transactions`: simulacao idempotente do provedor.
- `payment_fake_refunds`: estornos idempotentes.

## Transacoes

A chamada do gateway ocorre fora da transacao. Preparacao e aplicacao do resultado usam transacoes curtas com locks nas fronteiras de persistencia e estoque.

## Idempotencia

IDs e chaves sao UUID v5 derivados do pedido. Intencao, mensagem, transacao falsa e estorno possuem constraints unicas. Estados finais impedem novas tentativas.

## Seguranca

Nenhum dado de cartao e aceito ou persistido. Tentativas guardam somente codigos tecnicos saneados. O provider impede resolucao do adaptador falso em producao.

## Eventos

O checkout publica `payment.requested` na outbox. O processamento usa scheduler a cada minuto com `withoutOverlapping`.

## Interface

A confirmacao informa que a proxima etapa sera processada e atende tambem pedidos de orcamento. Status de pagamento ja aparece nas telas administrativas e na conta do cliente.

## Testes automatizados

Cobrem aprovacao, recusa, timeout, retry, cobranca unica, estorno unico e compensacao por reserva vencida.

## Casos de QA

Consulte `docs/qa/2026-07-13-fake-payment-gateway.md`.

## Como validar

Configure `FAKE_PAYMENT_OUTCOME` como `approved`, `declined` ou `timeout`, finalize um pedido e execute `php artisan payments:process`.

## Riscos e limitacoes

- E apenas um simulador local/teste e nao movimenta dinheiro.
- Scheduler e outbox precisam estar ativos.
- Concorrencia real ainda deve ser validada em MySQL/InnoDB.
- Webhook e reconciliacao pertencem ao ciclo do Asaas.
