# Detalhe seguro do pedido do cliente

## Objetivo

Permitir que o cliente autenticado consulte os dados completos de um pedido vinculado à sua identidade interna.

## Escopo

Listagem com acesso ao detalhe, itens, totais, pagamento, endereço e modalidade de entrega. Pedidos com pagamento recusado orientam o cliente a revisar o carrinho restaurado antes de uma nova finalização.

## Fora do escopo

Reprocessar a mesma cobrança recusada, reivindicar pedidos de convidado, alterar pedido ou endereço e exibir dados administrativos internos.

## Regras de negócio

- O pedido é localizado simultaneamente pelo UUID e por `customer_user_id`.
- Pedido inexistente e pedido de outro cliente produzem a mesma resposta 404.
- Acesso exige autenticação e e-mail verificado.
- Uma cobrança recusada nunca é reutilizada; a retomada ocorre pelo carrinho restaurado e por um novo checkout validado.
- Documento fiscal do cliente e identificadores internos do provedor não são expostos na página.

## Fluxo principal

O cliente abre o pedido pela conta. O Controller entrega o ID autenticado ao caso de uso, que consulta a porta de leitura. O adaptador SQL aplica a propriedade antes de carregar os itens e a página apresenta os snapshots comerciais do pedido.

## Fluxos alternativos

Outro proprietário recebe 404. Visitante vai para o login. Conta não verificada vai para a verificação. Pedido recusado mostra a orientação para voltar ao carrinho.

## Casos de uso

`ShowCustomerOrder`.

## Arquitetura

A autorização por propriedade ocorre na consulta do módulo Ordering. O Controller não usa Query Builder e a interface não decide propriedade.

## Portas e adaptadores

`CustomerOrderReadModel::findForUser` e `DatabaseCustomerOrderReadModel`.

## Persistência

Não há nova persistência. A consulta usa `ordering_orders.customer_user_id` e os snapshots de `ordering_order_items`.

## Transações

Fluxo somente leitura, sem unidade transacional de escrita.

## Idempotência

Requisições GET são naturalmente repetíveis e não alteram estado.

## Segurança

Autenticação, e-mail verificado, UUID na rota e filtro por identidade interna. A resposta 404 uniforme reduz enumeração e nenhum dado de cartão ou provedor é retornado.

## Eventos

Nenhum evento novo.

## Interface

Nova página `cliente/pedido`, acessível pelo número ou pelo botão “Ver pedido” na conta. O layout e os componentes visuais existentes foram preservados.

## Testes automatizados

Cobertura de proprietário, isolamento entre usuários, visitante e conta não verificada, além da presença dos itens e snapshots do pedido.

## Casos de QA

Consulte `docs/qa/2026-07-22-detalhe-seguro-do-pedido-do-cliente.md`.

## Como validar

Entre com um cliente verificado, abra `/minha-conta` e selecione um pedido. Repita a URL autenticado como outro cliente e confirme a resposta 404.

## Riscos e limitações

Pedidos convidados e legados sem `customer_user_id` continuam invisíveis. A nova tentativa de pagamento é intencionalmente feita como novo checkout do carrinho restaurado, pois estoque, cupom, frete e cobrança precisam ser revalidados.
