# AGENTS.md

## 1. Propósito

Estas instruções são obrigatórias para qualquer agente, harness ou desenvolvedor que trabalhe neste repositório.

O projeto é um e-commerce de fardamentos desenvolvido com Laravel 12, Inertia, React 19 e TypeScript. A arquitetura oficial é um monólito modular, orientado por capacidades de negócio, usando arquitetura hexagonal e princípios SOLID.

O objetivo atual é consolidar checkout, estoque, pagamento, segurança, logística, qualidade e testes. Não priorize novas telas superficiais enquanto os fluxos críticos estiverem incompletos, salvo solicitação explícita.

---

## 2. Regras inegociáveis

### 2.1 Escopo estrito

Implemente somente o que foi solicitado.

Não adicione por iniciativa própria:

* páginas, campos, componentes ou relatórios;
* alterações visuais ou de navegação;
* dependências;
* abstrações especulativas;
* refatorações amplas;
* funcionalidades futuras;
* mudanças em arquivos não relacionados.

Uma alteração adicional só é permitida quando for indispensável para concluir corretamente o pedido. Nesse caso, limite-a ao mínimo e documente a razão.

### 2.2 Conclusão de ponta a ponta

Não entregue feature pela metade.

Uma feature não está pronta quando existe apenas migration, Model, rota, Controller, DTO, contrato, caso de uso, tela, teste isolado ou documentação.

Uma feature somente está concluída quando todos os elementos aplicáveis estiverem implementados:

```text
regra de negócio
+ caso de uso
+ portas e adaptadores
+ persistência
+ transação
+ segurança
+ interface
+ testes
+ QA
+ documentação
+ validação final
```

Não deixe TODO, placeholder ou etapa crítica para depois dentro do escopo iniciado.

### 2.3 Preservação de layout

Não altere o layout sem solicitação explícita.

Preserve:

* cores, tipografia e espaçamentos;
* componentes e identidade visual;
* estrutura das páginas;
* textos e navegação;
* formulários, tabelas, modais e feedbacks;
* responsividade e acessibilidade existentes.

Novas interfaces solicitadas devem reutilizar os padrões visuais atuais.

### 2.4 Qualidade real

Não contorne arquitetura ou ferramentas para terminar mais rápido.

É proibido:

* colocar regra de negócio em Controller ou React;
* acessar tabelas diretamente em Controller;
* desabilitar teste, lint ou análise estática;
* reduzir nível do PHPStan;
* adicionar ignores genéricos;
* esconder erro com `try/catch` vazio;
* afirmar que algo passou sem executar;
* apresentar simulação como integração de produção.

---

## 3. Prioridades do projeto

### Prioridade crítica

1. checkout transacional único;
2. estoque unificado por SKU ou variante;
3. confirmação e expiração de reservas;
4. gateway de pagamento falso completo e testável;
5. pagamento real;
6. webhook, idempotência e reconciliação;
7. associação segura entre usuário e pedido;
8. consumo concorrente de cupons;
9. testes dos fluxos novos;
10. correção de PHPStan, ESLint, Prettier e Pint.

### Antes de clientes reais

1. formulário de orçamento funcional;
2. verificação de e-mail e recuperação de senha;
3. endereços e detalhe do pedido;
4. máquina de estados de pedido;
5. cancelamentos, devoluções e reembolsos;
6. etiqueta, expedição e rastreamento;
7. paginação;
8. Policies administrativas específicas;
9. README, arquitetura e roadmap atualizados.

### Antes de produção

1. Redis, filas, outbox e scheduler;
2. logs estruturados, métricas e alertas;
3. backup e restauração de MySQL;
4. CSP, segredos e criptografia;
5. LGPD;
6. SEO, sitemap e dados estruturados;
7. acessibilidade e testes móveis;
8. testes de carga e concorrência em MySQL/InnoDB.

---

## 4. Arquitetura oficial

Estrutura de referência:

```text
app/Modules/NomeDoModulo/
├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Policies/
│   └── Services/
├── Application/
│   ├── Commands/
│   ├── Queries/
│   ├── UseCases/
│   ├── DTOs/
│   ├── Ports/
│   │   ├── In/
│   │   └── Out/
│   └── Exceptions/
├── Infrastructure/
│   ├── Persistence/
│   ├── Gateways/
│   ├── Providers/
│   ├── Jobs/
│   ├── Listeners/
│   └── Mappers/
└── Presentation/
    └── Http/
        ├── Controllers/
        ├── Requests/
        ├── Resources/
        └── Routes/
```

Respeite a convenção existente do módulo. Não crie estruturas paralelas para a mesma responsabilidade.

### 4.1 Domain

Contém regras puras:

* entidades e agregados;
* Value Objects;
* invariantes;
* políticas;
* eventos e exceções de domínio;
* serviços de domínio.

Domain não depende de Laravel, Eloquent, banco, HTTP, Inertia, React, filas, cache ou SDK externo.

Entidades devem impedir estados inválidos.

Valores monetários devem ser armazenados em centavos ou Value Object. Nunca use `float` para dinheiro.

### 4.2 Application

Contém casos de uso e orquestração.

Todo fluxo relevante deve possuir caso de uso explícito, por exemplo:

* `CheckoutCart`;
* `ReserveStock`;
* `ConfirmStockReservation`;
* `CreatePayment`;
* `HandlePaymentWebhook`;
* `ApplyCoupon`;
* `CancelOrder`;
* `RequestQuote`.

O caso de uso deve:

1. receber DTO, Command ou tipos explícitos;
2. validar pré-condições;
3. coordenar entidades e portas;
4. controlar a unidade de trabalho;
5. retornar resultado explícito;
6. produzir eventos quando necessário;
7. ser testável sem HTTP.

Application depende de Domain e portas. Não depende diretamente de Eloquent, Query Builder, Request, Response, SDK externo ou adaptador concreto.

### 4.3 Infrastructure

Implementa portas de saída:

* repositórios;
* persistência Eloquent;
* gateways;
* transações;
* locks;
* cache;
* filas;
* outbox;
* criptografia;
* jobs e listeners;
* integrações externas.

Infrastructure não deve conter regra central de negócio.

### 4.4 Presentation

Controllers devem apenas:

1. receber a requisição;
2. autorizar;
3. validar com Form Request;
4. criar DTO ou Command;
5. chamar o caso de uso;
6. retornar Resource, redirect ou resposta Inertia.

Controllers não podem calcular preço, desconto, frete ou estoque; controlar transação; consumir cupom; alterar estoque; enviar e-mail; chamar SDK externo; ou atualizar múltiplos Models para concluir um fluxo.

---

## 5. SOLID e limites modulares

Aplicar SOLID de forma prática:

* uma responsabilidade clara por classe;
* integrações dependem de contratos;
* adaptadores respeitam integralmente suas portas;
* interfaces pequenas e específicas;
* casos de uso dependem de abstrações.

Módulos esperados ou equivalentes:

* Catalog;
* Cart;
* Ordering;
* Inventory;
* Payments;
* Shipping;
* Coupons;
* Customers;
* Identity;
* Administration;
* Content;
* Notifications;
* Reporting.

Um módulo não acessa diretamente tabelas internas de outro módulo.

A comunicação ocorre por:

* portas;
* casos de uso públicos;
* queries públicas;
* DTOs;
* eventos.

Não use Model Eloquent de outro módulo como contrato da Application.

---

## 6. Fluxo obrigatório de trabalho

### Antes de codificar

1. leia este arquivo;
2. leia a documentação do módulo;
3. verifique o Git e não sobrescreva trabalho local;
4. analise implementação e testes existentes;
5. identifique regra, caso de uso e módulos envolvidos;
6. avalie persistência, segurança, concorrência e layout;
7. defina um plano restrito ao pedido.

### Durante a implementação

1. implemente ou ajuste o caso de uso;
2. mantenha regras em Domain ou Application;
3. use portas para infraestrutura;
4. mantenha Controllers finos;
5. preserve o layout;
6. crie migrations reversíveis;
7. implemente autorização e validação;
8. trate falhas e concorrência;
9. adicione testes;
10. crie casos de QA;
11. atualize documentação.

### Antes de concluir

1. revise o diff completo;
2. remova código morto e TODOs do escopo;
3. confirme que nada fora do pedido foi alterado;
4. execute testes e verificações;
5. registre resultados reais;
6. informe limitações sem ocultá-las.

---

## 7. Definição de pronto

Uma feature só pode ser marcada como concluída quando todos os itens aplicáveis estiverem atendidos:

* requisito funcional completo;
* regras e invariantes protegidas;
* caso de uso implementado;
* autorização e validação;
* persistência e migration;
* transação;
* idempotência;
* concorrência;
* eventos ou outbox;
* adaptadores;
* interface completa;
* loading, vazio, erro e sucesso;
* testes unitários;
* testes de Application;
* testes de integração;
* testes de feature;
* testes de autorização e falha;
* testes de concorrência quando necessários;
* documento de QA;
* documentação técnica e funcional;
* README ou roadmap atualizado quando necessário;
* PHPStan, Pint, ESLint, Prettier, TypeScript e build aprovados;
* nenhum TODO dentro do escopo.

---

## 8. Testes obrigatórios

Toda feature deve possuir testes proporcionais ao risco.

### Unitários

Cobrir entidades, Value Objects, cálculos, políticas, invariantes e transições de estado.

### Application

Cobrir:

* sucesso;
* entrada inválida;
* recurso inexistente;
* conflito;
* idempotência;
* falha de dependência;
* rollback;
* eventos produzidos.

### Integração

Cobrir repositórios, transações, locks, persistência, mapeadores, gateways falsos, outbox, jobs e listeners.

### Feature

Cobrir rota, autenticação, autorização, validação, resposta, persistência, redirect, mensagens, páginas Inertia e erros.

### Concorrência

Obrigatórios para:

* estoque e reservas;
* cupons;
* pagamentos;
* webhooks;
* idempotência;
* mudanças de status;
* cancelamentos e reembolsos.

Fluxos concorrentes críticos devem ser validados com MySQL/InnoDB. SQLite não é evidência suficiente.

### Frontend

Quando houver infraestrutura existente, cobrir renderização, interação, submissão, validação, loading, erro e acessibilidade básica.

Não adicione uma nova ferramenta de testes frontend sem justificativa.

---

## 9. QA obrigatório

Para cada feature, criar:

```text
docs/qa/YYYY-MM-DD-nome-da-feature.md
```

Exemplo:

```text
docs/qa/2026-07-13-checkout-transacional.md
```

Estrutura:

```md
# QA — Nome da feature

## Objetivo
## Pré-condições
## Dados de teste
## Ambiente
## Casos de sucesso
## Casos de validação
## Casos de autorização
## Casos de falha
## Casos de concorrência
## Casos de idempotência
## Casos de regressão
## Casos responsivos
## Casos de acessibilidade
## Evidências
## Riscos conhecidos
```

Cada caso deve possuir:

* identificador;
* cenário;
* pré-condições;
* passos;
* resultado esperado;
* resultado obtido;
* status.

---

## 10. Documentação obrigatória

Toda alteração funcional deve criar ou atualizar:

```text
docs/features/nome-da-feature.md
docs/architecture/nome-da-decisao.md
docs/qa/YYYY-MM-DD-nome-da-feature.md
```

Use nomes claros e específicos.

Não use nomes vagos como `doc.md`, `ajustes.md`, `mudancas.md`, `novo.md`, `feature.md` ou `teste.md`.

A documentação da feature deve conter:

```md
# Nome da feature

## Objetivo
## Escopo
## Fora do escopo
## Regras de negócio
## Fluxo principal
## Fluxos alternativos
## Casos de uso
## Arquitetura
## Portas e adaptadores
## Persistência
## Transações
## Idempotência
## Segurança
## Eventos
## Interface
## Testes automatizados
## Casos de QA
## Como validar
## Riscos e limitações
```

README e roadmap devem refletir o estado real. Não marque como concluído algo parcial.

---

## 11. Regras dos fluxos críticos

### 11.1 Checkout

O checkout deve ser coordenado por um único caso de uso.

Dentro da unidade transacional apropriada, ele deve:

1. carregar e validar o carrinho;
2. validar preços e itens;
3. validar e reservar estoque;
4. revalidar frete;
5. validar e consumir cupom atomicamente;
6. criar pedido e snapshots;
7. criar intenção de pagamento;
8. converter ou encerrar o carrinho;
9. persistir eventos na outbox.

E-mail, notificações, analytics e sincronizações devem ocorrer após commit.

Falha de e-mail não invalida pedido criado.

A mesma chave de idempotência não pode criar pedidos, reservas, usos de cupom ou pagamentos duplicados.

### 11.2 Estoque

Deve existir uma única fonte de verdade por SKU ou variante.

Não mantenha saldo em tabela e JSON ao mesmo tempo.

Toda movimentação deve ser rastreável:

* entrada;
* ajuste;
* reserva;
* liberação;
* confirmação;
* venda;
* cancelamento;
* devolução.

Regras:

* saldo nunca fica negativo;
* concorrência usa lock ou operação atômica;
* reserva expirada é liberada;
* reserva não é confirmada duas vezes;
* estoque baixo é alerta, não indisponibilidade;
* disponibilidade depende de saldo maior que zero, salvo regra documentada.

### 11.3 Pagamento

Registrar a preferência do usuário não significa pagamento implementado.

O pagamento deve possuir:

* entidade persistida;
* valor e moeda;
* método e status;
* identificador interno e do provedor;
* chave de idempotência;
* histórico de transições;
* reconciliação;
* cancelamento;
* estorno quando aplicável.

Transições devem ser protegidas por máquina de estados ou política explícita.

Antes do provedor real, implemente gateway falso completo e testável.

Webhooks devem validar assinatura, ser idempotentes, impedir duplicidade, registrar correlação e atualizar o estado por caso de uso.

### 11.4 Cupons

Validação e consumo devem acontecer na mesma transação.

Use lock, atualização condicional, contador atômico, registro único ou constraint.

Teste duas compras simultâneas disputando o último uso.

### 11.5 Clientes e pedidos

Pedidos não podem ser autorizados apenas por coincidência de e-mail.

Use vínculo seguro com usuário, verificação de e-mail, Policies e processo verificado para reivindicar pedido de visitante.

### 11.6 Frete

A cotação deve ser revalidada no checkout.

Considere CEP, endereço, peso e dimensões por SKU, quantidade, prazo, preço, validade da cotação, indisponibilidade, timeout, retry, etiqueta e rastreamento.

Tokens devem ser criptografados e logs devem minimizar dados do fornecedor.

---

## 12. Segurança

Toda feature deve analisar:

* autenticação e autorização;
* validação;
* CSRF e XSS;
* SQL Injection;
* mass assignment;
* enumeração;
* exposição de dados;
* rate limiting;
* idempotência e concorrência;
* auditoria;
* segredos e criptografia;
* LGPD.

Use Policies e Gates.

Não dependa apenas de `is_admin` quando forem necessárias permissões específicas.

Não registre senha, token, segredo, cartão, código de segurança ou payload sensível completo.

---

## 13. Banco, eventos e jobs

Migrations devem ser reversíveis, possuir índices e constraints adequados e preservar integridade referencial.

Não altere migration já aplicada em ambiente compartilhado. Crie migration corretiva.

Evite JSON para dados centrais consultáveis.

Operações de pedido, pagamento, cupom e estoque devem usar transações.

Efeitos externos devem ocorrer por eventos, outbox e jobs quando necessário.

Jobs devem ser:

* idempotentes;
* seguros para retry;
* observáveis;
* configurados com backoff;
* configurados com limite de tentativas;
* capazes de registrar falha final.

---

## 14. Frontend

Mantenha TypeScript estrito.

Não use `any` sem justificativa.

Componentes devem ter responsabilidade única, reutilizar padrões existentes, preservar acessibilidade e tratar loading, vazio, erro e sucesso.

O servidor é a fonte de verdade para preço, desconto, frete, estoque, total, status e autorização.

Não coloque regra financeira ou de estoque definitiva no cliente.

---

## 15. Verificações obrigatórias

Execute os comandos existentes equivalentes a:

```bash
php artisan test
./vendor/bin/phpstan analyse
./vendor/bin/pint --test
npm run lint
npm run typecheck
npm run format:check
npm run build
```

Não finalize com erros conhecidos nas áreas alteradas.

A pasta aninhada `fardamentos-loja` deve ser removida ou ignorada pelas ferramentas somente após confirmar que não contém trabalho necessário.

---

## 16. Git e workspace

Antes de alterar:

* verifique arquivos modificados;
* preserve trabalho local;
* não execute `reset`, `clean` ou checkout forçado;
* não apague arquivos sem necessidade confirmada;
* não reformate o projeto inteiro;
* não execute comandos destrutivos.

Faça alterações pequenas, rastreáveis e restritas ao pedido.

---

## 17. Relatório final obrigatório

Ao concluir uma tarefa, apresente:

```md
## Resumo
## Escopo concluído
## Arquivos principais alterados
## Caso de uso criado ou alterado
## Regras de negócio
## Decisões arquiteturais
## Migrations
## Segurança
## Testes automatizados
## Casos de QA
## Documentação
## Comandos executados
## Resultado das verificações
## Limitações reais
## Fora do escopo
```

Não afirme que testes, build ou integração passaram sem execução e evidência.

---

## 18. Regra final

Faça a menor alteração que entregue a solução completa, correta, segura, testada e documentada.

Menor alteração não significa solução parcial.

Nenhuma feature está concluída enquanto faltar qualquer elemento necessário ao pedido.
