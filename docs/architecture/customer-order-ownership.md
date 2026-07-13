# Propriedade de pedidos por identidade interna

## Decisao

Autorizar e listar pedidos por chave estrangeira para o usuario autenticado, nunca pelo e-mail armazenado no snapshot comercial.

## Motivo

E-mail e dado mutavel e nao comprova propriedade. Vincular automaticamente registros antigos por igualdade permitiria que uma conta criada posteriormente reivindicasse pedidos sem uma prova independente.

## Limites

O agregado `Order` carrega `customerUserId`, a Application recebe o valor da camada autenticada e a infraestrutura o persiste. A leitura da conta usa uma porta especifica e nao expoe Query Builder ao Controller.

## Dados legados e convidados

O campo e nullable. Nenhum backfill por e-mail e executado. Pedidos convidados e legados exigirao, em outra etapa, link assinado ou desafio de uso unico enviado ao endereco original.

## Integridade

A chave estrangeira usa `nullOnDelete`: o pedido permanece para obrigacoes operacionais e fiscais, mas deixa de aparecer em qualquer conta removida.
