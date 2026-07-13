# Perfil e endereços do cliente

## Objetivo

Permitir que o cliente autenticado mantenha seus dados pessoais e endereços e reutilize essas informações no checkout.

## Escopo

- edição de nome, telefone e CPF/CNPJ na conta;
- cadastro, edição e exclusão de endereços principais e de entrega;
- definição automática e manual de endereço padrão por tipo;
- seleção de endereço salvo no checkout;
- preenchimento manual de outro endereço no checkout;
- preenchimento automático de nome, e-mail, telefone e documento do cliente autenticado.

## Fora do escopo

- validação fiscal externa de CPF/CNPJ;
- geocodificação;
- salvar automaticamente no cadastro um endereço digitado somente no checkout;
- endereço de cobrança separado do endereço pessoal.

## Regras de negócio

- somente o proprietário pode consultar ou alterar seus endereços;
- o primeiro endereço de cada tipo torna-se padrão;
- existe no máximo um endereço padrão por tipo;
- ao remover ou mover o endereço padrão para outro tipo, o endereço mais antigo do tipo anterior é promovido;
- nome e e-mail autenticados usados no pedido continuam vindo da conta no servidor;
- sem endereço salvo, o checkout mantém os campos de entrega disponíveis para preenchimento manual.

## Fluxo principal

O cliente entra em Minha conta, atualiza seu perfil, cadastra um endereço e o marca como padrão. Ao abrir um checkout com itens, os dados do perfil e o endereço de entrega padrão são selecionados e preenchidos automaticamente.

## Fluxos alternativos

- o cliente escolhe outro endereço salvo;
- o cliente escolhe “Preencher outro endereço” e informa os campos manualmente;
- um cliente sem endereço salvo recebe o formulário vazio;
- ao excluir o padrão, outro endereço do mesmo tipo passa a ser padrão.

## Casos de uso

- `ShowCustomerAccount`;
- `UpdateCustomerProfile`;
- `SaveCustomerAddress`;
- `DeleteCustomerAddress`.

## Arquitetura

A capacidade fica no módulo `Customers`. A camada HTTP valida e encaminha os comandos; a Application coordena os casos de uso; a porta `CustomerAccountRepository` isola a persistência; o adaptador de banco controla propriedade, transações e padrões.

## Portas e adaptadores

- porta: `CustomerAccountRepository`;
- adaptador: `DatabaseCustomerAccountRepository`;
- provider: `CustomersServiceProvider`.

## Persistência

Telefone e documento ficam em `users`. Endereços ficam em `customer_addresses`, vinculados por chave estrangeira ao usuário e identificados por UUID.

## Transações

Cadastro, alteração e exclusão de endereço usam transação. A linha do cliente é bloqueada durante a escrita para serializar mudanças de padrão da mesma conta.

## Idempotência

Não há repetição automática de chamadas externas. As ações são formulários autenticados protegidos por CSRF; edição é determinística e exclusão de recurso inexistente retorna 404.

## Segurança

As rotas exigem autenticação, usam validação dedicada, rate limit e filtragem por `user_id`. UUID sozinho não concede acesso. O checkout ignora nome e e-mail submetidos quando há usuário autenticado.

## Eventos

Não são produzidos eventos: a alteração não possui efeito externo assíncrono no escopo atual.

## Interface

Minha conta possui formulário de dados pessoais, listagem e formulário de endereços. O checkout exibe cartões selecionáveis de endereços salvos e a alternativa de preenchimento manual.

## Testes automatizados

Os testes de feature cobrem perfil, CRUD, propriedade, validação, autenticação, promoção do padrão e dados enviados à página de checkout.

## Casos de QA

Consulte `docs/qa/2026-07-13-customer-profile-and-addresses.md`.

## Como validar

1. Execute as migrations.
2. Entre como cliente e abra `/minha-conta`.
3. Salve perfil e ao menos dois endereços.
4. Adicione um produto ao carrinho e abra `/checkout`.
5. Alterne entre endereços salvos e preenchimento manual.

## Riscos e limitações

A consulta de CEP depende do serviço já configurado na aplicação. CPF/CNPJ é validado por tamanho e formato, sem consulta a uma base fiscal externa.
