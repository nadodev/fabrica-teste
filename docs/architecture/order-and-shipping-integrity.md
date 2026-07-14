# Integridade entre catalogo, frete e pedido

## Contexto

O checkout aceitava o valor salvo em sessao, o adaptador enviava medidas fixas e o painel atualizava status diretamente no banco.

## Decisao

O produto passa a possuir um Value Object logistico. `QuoteCartShipping` consulta produtos pelo contrato do Catalog e chama `ShippingQuoteGateway`. O checkout revalida o servico fora da transacao externa e compara a fingerprint dentro da transacao local.

O agregado `Order` define transicoes administrativas. `ChangeOrderStatus` usa lock, repositorio e auditoria. O repositorio diferencia insert de update, preservando data e snapshots.

A inbox usa lease temporal: `processing` antigo retorna a `pending`; evento que esgota tentativas muda para `failed`.

## Consequencias

Preco, prazo, peso e dimensoes voltam a ter servidor e provedor como fontes de verdade. Transicoes financeiras continuam exclusivas dos casos de pagamento. A chamada externa nao segura locks do banco.

## Operacao

`ASAAS_WEBHOOK_MAX_ATTEMPTS` e `ASAAS_WEBHOOK_STALE_MINUTES` controlam dead-letter e recuperacao. O scheduler continua obrigatorio.
