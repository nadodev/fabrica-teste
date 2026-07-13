# QA - vinculo seguro entre cliente e pedido

## Objetivo
Validar que a conta acessa pedidos por identidade interna, sem confiar em e-mail.
## Pre-condicoes
Migration `2026_07_13_070000` aplicada e usuarios de teste criados.
## Dados de teste
Um pedido do usuario, um pedido convidado com o mesmo e-mail e um pedido de outro usuario.
## Ambiente
Local, Laravel, SQLite para testes automatizados.
## Casos de sucesso
- OWN-01: finalizar checkout autenticado; ID, nome e e-mail da sessao sao persistidos; obtido conforme esperado; OK.
- OWN-02: abrir a conta autenticada; somente o pedido com o mesmo `customer_user_id` aparece; obtido conforme esperado; OK.
## Casos de validacao
- OWN-03: enviar nome e e-mail diferentes no checkout autenticado; valores da conta prevalecem; obtido conforme esperado; OK.
## Casos de autorizacao
- OWN-04: visitante abre `/minha-conta`; redirecionado para `/entrar`; obtido conforme esperado; OK.
- OWN-05: usuario possui mesmo e-mail do snapshot de pedido convidado; pedido nao aparece; obtido conforme esperado; OK.
- OWN-06: pedido pertence a outro ID; pedido nao aparece; obtido conforme esperado; OK.
## Casos de falha
- OWN-07: conta removida; chave estrangeira anula o vinculo e preserva o pedido; regra da migration, validacao manual pendente.
## Casos de concorrencia
Vinculo e gravado dentro do checkout transacional; nenhuma operacao separada de reivindicacao existe.
## Casos de idempotencia
Repeticao do carrinho retorna o pedido original e nao cria segundo vinculo; coberto pela suite de checkout; OK.
## Casos de regressao
Suite completa com 53 testes e 346 assercoes aprovada.
## Casos responsivos
Layout existente preservado; apenas texto explicativo alterado.
## Casos de acessibilidade
Estrutura semantica existente da pagina preservada.
## Evidencias
Migration aplicada; PHPStan, Pint, TypeScript, ESLint, Prettier, testes e build completos aprovados.
## Riscos conhecidos
Pedidos legados e convidados nao podem ser reivindicados ainda; isso evita atribuicao insegura por e-mail.
