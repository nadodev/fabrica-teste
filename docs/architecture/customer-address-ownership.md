# Propriedade e seleção de endereços do cliente

## Contexto

O checkout precisava reutilizar dados da conta sem permitir que um cliente consultasse ou modificasse endereços de outro usuário.

## Decisão

Os endereços são uma capacidade do módulo `Customers` e possuem vínculo obrigatório com `users`. Toda consulta ou mutação recebe o identificador do usuário autenticado e filtra simultaneamente pelo UUID do endereço e por `user_id`.

A Application depende da porta `CustomerAccountRepository`; o adaptador de banco executa transações e bloqueia a linha do cliente durante escritas. Isso serializa alterações concorrentes do mesmo cadastro e preserva um único padrão por tipo no fluxo da aplicação.

O módulo `Ordering` consome apenas a query pública `ShowCustomerAccount` para preparar o checkout. Ele não consulta diretamente a tabela interna de endereços.

## Consequências

- UUIDs não funcionam como autorização;
- Controllers permanecem sem acesso direto ao banco;
- o checkout recebe um snapshot próprio para apresentação;
- a regra de endereço padrão é centralizada no adaptador da porta;
- exclusão ou alteração de tipo promove um substituto quando necessário.

## Alternativas rejeitadas

- guardar endereços em JSON no usuário: dificulta integridade, consulta e evolução;
- consultar `customer_addresses` no Controller de checkout: viola o limite modular;
- confiar no endereço selecionado pelo navegador como pertencente ao cliente: permitiria referência indevida e não é necessário, pois o pedido recebe o snapshot validado dos campos.

## Segurança

As rotas são autenticadas, limitadas por frequência e protegidas por CSRF. Requests validam tamanhos e formatos. Dados de outro proprietário retornam 404 para reduzir enumeração.

## Limitação consciente

A unicidade do padrão por tipo é garantida pela transação e bloqueio por usuário no adaptador. Não foi criada constraint parcial específica de banco porque o projeto precisa operar de forma compatível nos bancos suportados pela suíte.
