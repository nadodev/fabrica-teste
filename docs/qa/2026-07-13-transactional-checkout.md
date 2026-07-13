# QA - checkout transacional

Data: 2026-07-13

## Cenarios verificados automaticamente

- Pedido simples reserva estoque, converte carrinho e gera apenas uma outbox.
- Repeticao do checkout para o mesmo carrinho retorna o mesmo pedido.
- Cliente, endereco, frete, cupom, subtotal, desconto e total ficam congelados no pedido.
- Cupom com limite e contado uma unica vez dentro da transacao.
- Falha de estoque desfaz cupom, pedido, reserva, outbox e conversao do carrinho.
- Nenhum e-mail e enviado dentro da transacao.
- Processamento posterior da outbox envia o e-mail e marca a mensagem como processada.
- Requisicao HTTP publica traduz os dados para o caso de uso e limpa a sessao apos sucesso.
- Suite completa: 32 testes, 210 assercoes, sem falhas.

## QA manual orientado

1. Adicionar um produto ativo e com estoque ao carrinho.
2. Calcular e selecionar uma entrega, aplicar cupom quando aplicavel e abrir o checkout.
3. Finalizar uma vez e confirmar o numero publico na pagina de sucesso.
4. Conferir no painel que cliente, endereco, itens, frete, cupom e total correspondem ao checkout.
5. Executar `php artisan outbox:process-orders` e conferir a notificacao no mailer do ambiente.
6. Repetir a requisicao com a mesma chave idempotente e confirmar que nao surgiu outro pedido.

## Resultado

Camada de dominio, persistencia e processamento posterior aprovada pela suite automatizada. A validacao visual completa sera repetida ao concluir o ciclo de estoque por variante, pois essa etapa altera a disponibilidade mostrada no front-end.

## Verificacoes de qualidade

- PHPStan no modulo alterado: aprovado, zero erros.
- Pint nos arquivos alterados: aprovado.
- TypeScript: aprovado.
- Build de producao: aprovado; permanece aviso de bundle JavaScript com cerca de 646 kB.
- Migrations: plano completo aprovado, incluindo criacao e rollback da outbox.
- PHPStan global: 59 erros anteriores fora deste ciclo.
- ESLint de `resources/js`: 27 erros anteriores fora deste ciclo.
- Prettier global: 37 arquivos anteriores fora deste ciclo.
- O comando global de ESLint tambem percorre indevidamente o repositorio aninhado `fardamentos-loja`.
