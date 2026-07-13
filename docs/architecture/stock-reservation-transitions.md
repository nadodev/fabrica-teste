# Transicoes atomicas de reserva de estoque

## Decisao

As transicoes de uma reserva pertencem ao modulo Inventory e sao expostas pela porta pequena `StockReservationLifecycle`. Payments e Ordering nao alteram tabelas ou saldos diretamente.

## Estados e efeitos

| Origem | Operacao | Destino | Fisico | Reservado |
| --- | --- | --- | ---: | ---: |
| `active` valida | confirmar | `confirmed` | `-quantidade` | `-quantidade` |
| `active` | liberar | `released` | `0` | `-quantidade` |
| `active` vencida | expirar | `expired` | `0` | `-quantidade` |
| `confirmed` | confirmar novamente | `confirmed` | `0` | `0` |

Outras tentativas de confirmacao resultam em conflito. Liberar ou expirar um estado final nao produz efeito.

## Concorrencia

A reserva e bloqueada antes do nivel. Todos os fluxos seguem essa mesma ordem para reduzir risco de deadlock. Depois do lock, status e validade sao revalidados. Assim, confirmacao e expiracao concorrentes nao conseguem consumir e liberar a mesma reserva.

## Auditoria

Uma movimentacao guarda `reservation_id`, delta fisico, delta reservado e os dois saldos posteriores. A referencia unica protege contra duplicacao e permite correlacionar o historico com a reserva.

## Agendamento

`inventory:expire-reservations --limit=100` roda a cada minuto com `withoutOverlapping`. O limite mantem transacoes curtas; uma nova execucao continua as reservas restantes.

## Consequencias

O gateway de pagamento pode confirmar por caso de uso sem conhecer persistencia de estoque. Cancelamento ou falha definitiva podem liberar pela mesma fronteira. A infraestrutura operacional deve executar o scheduler continuamente.
