# Ciclo de vida das reservas de estoque

## Objetivo

Garantir que toda reserva de estoque possa ser confirmada, liberada ou expirada exatamente uma vez, mantendo os saldos fisico e reservado consistentes.

## Escopo

- Confirmacao idempotente de reserva ativa.
- Liberacao idempotente de reserva ativa.
- Expiracao automatica em lotes limitados.
- Historico separado dos impactos fisico e reservado.
- Comando agendado a cada minuto.

## Fora do escopo

- Aprovacao de pagamento e cancelamento de pedido, que chamarao estes casos de uso nos ciclos de Payments e Ordering.
- Conciliacao com o Asaas.
- Reposicao de estoque por devolucao ou estorno.

## Regras de negocio

- Somente uma reserva `active` e ainda dentro da validade pode ser confirmada.
- Repetir a confirmacao de uma reserva `confirmed` nao altera o estoque nem duplica historico.
- Uma reserva vencida encontrada durante a confirmacao e expirada e nao pode consumir estoque.
- Liberacao e expiracao reduzem apenas o saldo reservado.
- Confirmacao reduz na mesma quantidade os saldos fisico e reservado.
- Uma inconsistencia entre reserva e nivel de estoque interrompe a operacao; o saldo nunca e corrigido silenciosamente.
- O lote de expiracao aceita entre 1 e 1000 registros.

## Fluxo principal

1. O caso de uso recebe o identificador da reserva.
2. O adaptador bloqueia a reserva e o nivel de estoque.
3. Valida status, validade e saldos.
4. Atualiza nivel e status na mesma transacao.
5. Registra uma movimentacao auditavel com o delta fisico e o delta reservado.

## Fluxos alternativos

- Confirmacao repetida: retorna sem nova alteracao.
- Reserva liberada, expirada ou inexistente: a confirmacao falha com conflito.
- Expiracao concorrente: somente a transacao que ainda encontrar status `active` altera o saldo.
- Liberacao repetida: retorna sem nova alteracao.

## Casos de uso

- `ConfirmStockReservation`.
- `ReleaseStockReservation`.
- `ExpireStockReservations`.

## Arquitetura

Os casos de uso dependem de `StockReservationLifecycle`. `DatabaseStockGateway` implementa a porta e concentra locks, transacoes e persistencia do modulo Inventory.

## Portas e adaptadores

- Porta: `StockReservationLifecycle`.
- Adaptador: `DatabaseStockGateway`.
- Entrada operacional: comando `inventory:expire-reservations`.

## Persistencia

`inventory_reservations.status` usa `active`, `confirmed`, `released` ou `expired`. `inventory_movements` recebeu `reservation_id`, `reserved_delta` e `reserved_after`; `quantity` e `balance_after` continuam representando o impacto e o saldo fisico.

## Transacoes

Cada transicao bloqueia a reserva e o nivel com `lockForUpdate`. Confirmacao altera os dois saldos e o status em uma unica transacao. O expurgador processa cada identificador em uma transacao curta e revalida o status depois do lock.

## Idempotencia

O status da reserva impede repetir a transicao. As referencias `reservation-reserved`, `reservation-confirmed`, `reservation-released` e `reservation-expired` sao unicas por reserva e evento.

## Seguranca

Nao ha endpoint publico. A confirmacao e a liberacao sao portas internas para os casos de uso de pagamento e pedido. O lote possui limite superior para evitar consumo sem controle.

## Eventos

Neste ciclo nao ha efeito externo. As movimentacoes persistidas formam o registro de auditoria; integracoes futuras chamarao os casos de uso depois de validar o evento de pagamento.

## Interface

O painel de estoque preserva o layout e passou a mostrar o saldo fisico e, quando aplicavel, o saldo reservado depois de cada movimentacao.

## Testes automatizados

Cobrem confirmacao repetida, consumo de saldo, expiracao em lotes, repeticao do expurgador, tentativa de confirmar reserva vencida, liberacao repetida e entrada pelo comando de console.

## Casos de QA

Consulte `docs/qa/2026-07-13-stock-reservation-lifecycle.md`.

## Como validar

1. Executar os testes do modulo Inventory.
2. Executar `php artisan inventory:expire-reservations --limit=100`.
3. Conferir `php artisan schedule:list`.
4. Conferir no painel os deltas fisico e reservado.

## Riscos e limitacoes

- A validacao concorrente local usa SQLite; a prova de concorrencia real ainda deve ser executada em MySQL/InnoDB.
- O scheduler do Laravel precisa permanecer ativo no ambiente operacional.
- A ligacao da confirmacao ao pagamento aprovado sera concluida junto ao gateway falso e depois mantida no Asaas.
