# Vinculo seguro entre cliente e pedido

## Objetivo

Garantir que a area do cliente exiba somente pedidos vinculados explicitamente ao usuario autenticado.

## Escopo

Vinculo no checkout autenticado, persistencia por chave estrangeira, consulta da conta por ID, redirecionamento correto de visitantes e testes contra acesso por coincidencia de e-mail.

## Fora do escopo

Reivindicacao de pedidos antigos ou de convidados, verificacao de e-mail, recuperacao de senha e tela de detalhe do pedido.

## Regras de negocio

- Checkout autenticado usa nome, e-mail e ID da conta da sessao, ignorando identidade enviada pelo navegador.
- A area do cliente filtra exclusivamente por `customer_user_id`.
- Pedido de convidado com o mesmo e-mail nao e atribuido automaticamente.
- Exclusao da conta remove o vinculo, mas preserva o snapshot fiscal e comercial do pedido.

## Fluxo principal

O Controller autenticado adiciona o ID do usuario ao `CheckoutData`. `CheckoutCart` cria o agregado com essa propriedade e o repositorio a persiste na mesma transacao do pedido. `ListCustomerOrders` consulta pelo ID autenticado por uma porta de leitura.

## Fluxos alternativos

Checkout convidado grava `customer_user_id=null`. Visitante da area do cliente e redirecionado para o login de cliente; rotas administrativas continuam redirecionando para o login administrativo.

## Casos de uso

`CheckoutCart` foi ampliado e `ListCustomerOrders` foi criado.

## Arquitetura

O Controller nao acessa mais a tabela de pedidos. A Application depende de `CustomerOrderReadModel`, implementado pelo adaptador SQL do modulo Ordering.

## Portas e adaptadores

`CustomerOrderReadModel` e `DatabaseCustomerOrderReadModel`.

## Persistencia

`ordering_orders.customer_user_id` e uma chave estrangeira nullable para `users.id`, indexada e com `nullOnDelete`.

## Transacoes

O vinculo e persistido pela mesma unidade transacional do checkout.

## Idempotencia

Repetir o checkout pelo mesmo carrinho retorna o mesmo pedido e nao altera sua propriedade.

## Seguranca

Nao existe consulta nem backfill por e-mail. A identidade vem apenas da sessao autenticada e o ID nunca e aceito do formulario publico.

## Eventos

Nenhum evento novo; os eventos existentes continuam referenciando o ID interno do pedido.

## Interface

A pagina da conta informa que os pedidos estao vinculados a conta. O layout e os estados existentes foram preservados.

## Testes automatizados

Checkout autenticado, isolamento entre usuarios, pedido sem dono com e-mail igual, visitante e regressao do login administrativo.

## Casos de QA

Consulte `docs/qa/2026-07-13-secure-customer-order-ownership.md`.

## Como validar

Autenticar, finalizar um pedido e abrir `/minha-conta`. Criar um pedido convidado com o mesmo e-mail e confirmar que ele nao aparece sem um processo futuro de reivindicacao verificada.

## Riscos e limitacoes

Pedidos anteriores a migration permanecem sem dono por decisao de seguranca. Um fluxo de reivindicacao devera exigir prova de posse do e-mail e token de uso unico.
