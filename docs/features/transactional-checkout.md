# Checkout transacional

## Objetivo

Criar um pedido completo sem deixar dados parciais quando qualquer validacao ou gravacao falhar.

## Entrada confiavel

O controller valida os campos HTTP e monta `CheckoutData`. O caso de uso nao aceita totais calculados pelo navegador. Ele carrega o carrinho persistido, consulta novamente o catalogo e calcula subtotal, desconto, frete e total no servidor.

## Fluxo atomico

Dentro da mesma transacao:

1. Carrega o carrinho pelo hash do token.
2. Retorna o pedido existente se o carrinho ja tiver sido convertido.
3. Confere valor minimo e forma de pagamento habilitada.
4. Bloqueia, valida e consome o cupom.
5. Revalida produto ativo, SKU, preco, moeda e variacao.
6. Cria o snapshot completo do pedido.
7. Reserva o estoque.
8. Persiste pedido e itens.
9. Converte o carrinho.
10. Persiste o evento `ordering.order_placed` na outbox.

Qualquer excecao desfaz todas essas alteracoes.

## Efeitos externos

E-mail nao e enviado durante o checkout. O comando agendado `outbox:process-orders` reivindica mensagens pendentes e envia as notificacoes depois do commit. Falhas voltam para a fila com atraso de cinco minutos. Uma reivindicacao abandonada e liberada depois de quinze minutos.

## Idempotencia

A rota continua protegida pelo middleware de idempotencia. Alem disso, `ordering_orders.cart_id` e unico e o caso de uso devolve o pedido ja criado quando o mesmo carrinho e repetido.

## Limites deste ciclo

O pagamento permanece pendente e sem cobranca externa. A intencao persistida sera introduzida com o gateway falso antes do adaptador Asaas.
