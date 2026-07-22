# Leitura autorizada do pedido do cliente

## Decisão

Carregar o detalhe do pedido por uma porta de leitura que recebe simultaneamente `orderId` e `userId`, aplicando ambos os filtros antes de consultar os itens.

## Contexto

A conta já listava pedidos por proprietário, mas não oferecia detalhe. Consultar primeiro por UUID e autorizar depois ampliaria o risco de enumeração ou de um futuro Controller esquecer a verificação.

## Consequências

O adaptador retorna `null` tanto para ausência quanto para propriedade divergente. O Controller traduz ambos em 404. A Application permanece independente de HTTP e o Controller permanece sem acesso direto ao banco.

## Retomada após cartão recusado

Não há transição de `declined` para uma nova cobrança no mesmo pedido. A recusa libera reserva e cupom e restaura um carrinho ativo. A interface conduz a esse carrinho para que preço, estoque, cupom, frete, dados do cartão e idempotência sejam novamente validados pelo checkout transacional.

## Dados expostos

São retornados apenas snapshots necessários ao cliente: itens, totais, contato, entrega e estado do pagamento. Documento, payload do gateway, chaves, tentativas internas e histórico administrativo permanecem fora do contrato.
