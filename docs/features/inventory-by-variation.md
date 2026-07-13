# Estoque por SKU e variacao

## Objetivo

Manter uma unica fonte operacional para saldo fisico, reservado e disponivel de cada produto simples ou variacao.

## Modelo

`inventory_stock_levels` possui um registro para cada unidade comercial de estoque:

- Produto simples: o identificador do nivel e o UUID do produto e `variation_key` fica vazio.
- Produto com variacoes: cada variacao possui identificador, SKU, saldo e limite de estoque baixo proprios.
- Saldo disponivel e sempre `on_hand - reserved`.

O JSON `catalog_products.variations` guarda apenas metadados de catalogo: identificador, nome, valor e SKU. Ele nao guarda saldo, reserva, disponibilidade ou limite operacional.

## Fluxos que usam a fonte unica

- Catalogo publico e detalhe do produto.
- Inclusao e alteracao de quantidade no carrinho.
- Revalidacao e reserva durante checkout.
- Cadastro e edicao de produtos.
- Ajuste manual de estoque.
- Dashboard administrativo e relatorios.
- Importacao e exportacao de produtos.

## Regras

- Estoque baixo e alerta, nao bloqueio. A compra bloqueia quando o saldo disponivel chega a zero, salvo quando venda sem estoque estiver habilitada.
- Uma variacao com reserva ativa nao pode ser removida.
- Um ajuste nao pode reduzir o saldo fisico abaixo da quantidade reservada.
- Movimentacoes e reservas armazenam snapshot de SKU e variacao.
- O SKU de variacao pode ser informado; quando vazio, e gerado de forma estavel a partir do SKU base e identificador da variacao.
