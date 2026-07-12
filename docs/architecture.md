# Arquitetura da loja

## Objetivo

Esta aplicação é um monólito modular em Laravel 12 com Inertia e React. Os módulos são isolados por limites de negócio e usam arquitetura hexagonal para impedir que regras de domínio dependam do Laravel, Eloquent, HTTP ou fornecedores externos.

## Módulos

| Módulo | Responsabilidade |
| --- | --- |
| Shared | Tipos e contratos realmente compartilhados |
| Catalog | Produtos, categorias, atributos, preços e disponibilidade comercial |
| Inventory | Estoque físico, reservas e movimentações |
| Cart | Carrinho e cálculo preliminar de totais |
| Ordering | Checkout, pedidos e ciclo de vida da compra |
| Payment | Intenções, cobranças, estornos e gateways |
| Shipping | Cotações, modalidades, expedição e transportadoras |

## Camadas de cada módulo

```text
Module/
├── Domain/          Regras de negócio, entidades, value objects e portas
├── Application/     Casos de uso, comandos, consultas e DTOs
├── Infrastructure/  Adaptadores de banco, filas, cache e fornecedores
└── Presentation/    Entradas HTTP, CLI, jobs e serialização
```

As dependências apontam para dentro:

```text
Presentation ──┐
Infrastructure ├──> Application ──> Domain
               ┘
```

- `Domain` não importa classes do Laravel.
- `Application` coordena o domínio por meio de portas.
- `Infrastructure` implementa portas de saída.
- `Presentation` traduz entradas externas para casos de uso.
- Módulos não acessam tabelas ou models de outros módulos diretamente.
- Integrações entre módulos usam contratos públicos ou eventos.

## Estrutura do front-end

```text
resources/js/
├── modules/
│   └── catalog/
│       ├── domain/          Tipos e regras puras
│       ├── application/     Casos de uso e contratos
│       ├── infrastructure/  Adaptadores HTTP/Inertia
│       └── ui/              Componentes, páginas e hooks
└── shared/                  UI e utilitários transversais
```

Páginas Inertia apenas compõem features. Componentes não conhecem rotas do backend nem formatos brutos de fornecedores.

## Portas substituíveis

- `ProductRepository`: persistência do catálogo.
- `StockGateway`: consulta e reserva de estoque.
- `PaymentGateway`: autorização, captura e estorno.
- `ShippingQuoteGateway`: cotação e modalidades de frete.
- `TransactionManager`: unidade de trabalho.
- `EventBus`: publicação de eventos de domínio.

Mercado Pago, Stripe, PagSeguro, Correios ou qualquer outro fornecedor serão adaptadores dessas portas, selecionados por configuração e registrados no container do Laravel.

## Convenções

- IDs são UUIDs encapsulados em value objects.
- Dinheiro usa valor inteiro em centavos e código ISO da moeda.
- Entidades protegem invariantes; controllers não contêm regra de negócio.
- DTOs atravessam limites; models Eloquent permanecem na infraestrutura.
- Migrations pertencem ao projeto, com nomes de tabela prefixados pelo contexto quando necessário.
- Testes de domínio são unitários e não inicializam o Laravel.
- Testes de adaptadores e apresentação podem usar o framework e banco em memória.

## Estratégia de evolução

1. Implementar Catálogo como corte vertical de referência.
2. Implementar movimentações e reservas em Estoque.
3. Criar Carrinho independente de sessão e persistência.
4. Orquestrar checkout e Pedidos.
5. Integrar Pagamentos por adaptadores configuráveis.
6. Integrar cotações e expedição em Frete.
7. Adicionar painel administrativo, autenticação e autorização.

