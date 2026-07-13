# Fonte unica de estoque

## Decisao

O modulo Inventory e a autoridade exclusiva sobre quantidades. Catalog nao calcula nem persiste saldo; apenas combina metadados do produto com os niveis retornados por `StockGateway`.

## Contratos

- `StockGateway`: consulta niveis, disponibilidade e controla reservas.
- `StockManager`: recebe, ajusta e sincroniza a estrutura de estoque de um produto.
- `InventoryReadModel`: fornece saldos e historico para o painel.

Controllers nao alteram tabelas de estoque diretamente. Os comandos de criacao e atualizacao de produto executam persistencia de catalogo e sincronizacao de estoque na mesma transacao.

## Concorrencia

Recebimentos, ajustes, sincronizacao e reservas bloqueiam o nivel com `lockForUpdate`. Referencias unicas tornam movimentacoes repetidas idempotentes. A reserva usa uma chave deterministica por pedido, produto e variacao.

## Migracao

A migration `2026_07_13_010000_unify_inventory_by_sku_and_variation`:

1. Cria os niveis por SKU/variacao.
2. Confere que o total agregado corresponde ao total das variacoes.
3. Preserva historico de movimentacoes e reservas.
4. Classifica reservas antigas ja vencidas como expiradas.
5. Remove os campos operacionais do JSON.
6. Remove a antiga tabela agregada.

A migration detecta e recupera uma tentativa parcial antes de repetir. O rollback recompõe o modelo agregado e devolve os campos de saldo ao JSON.
