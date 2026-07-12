# ADR 0003: Consistência transacional e idempotência

- Status: aceito
- Data: 2026-07-12

## Contexto

Operações de checkout atravessam banco, estoque e fornecedores sujeitos a timeout e entrega duplicada. Transações distribuídas não estão disponíveis e manter locks durante chamadas externas degrada a capacidade.

## Decisão

Usar transações locais curtas, idempotência persistida, locks de linha para saldo de estoque e outbox para efeitos assíncronos. Chamadas externas acontecem fora da transação e propagam uma referência idempotente.

## Consequências

- Retries seguros não duplicam pedido, reserva ou cobrança.
- Estoque exige banco com suporte transacional e testes específicos em MySQL.
- Processamento assíncrono deve aceitar eventos duplicados e fora de ordem.
- Estados intermediários são explícitos e reconciliáveis.

