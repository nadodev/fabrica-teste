# PROJETO #005 / RELATÓRIO TÉCNICO

## EM DESENVOLVIMENTO

### FRAMEWORK PHP

Loja virtual modular com Laravel, Inertia e React. Um projeto de portfólio criado para explorar arquitetura hexagonal, monólito modular e integrações substituíveis em um domínio real de comércio eletrônico.

## PROGRESSO

**30%**

[Repositório](https://github.com/nadodev/fabrica-teste)

## Visão geral

Uma loja virtual de uniformes criada para praticar modelagem de domínio e evolução sustentável de software. O objetivo é construir catálogo, estoque, carrinho, pedidos, pagamentos e fretes sem acoplar as regras de negócio ao Laravel, ao banco de dados ou a fornecedores externos.

A primeira entrega estabelece a arquitetura, implementa o Catálogo como corte vertical funcional, conecta o backend à página de produtos pelo Inertia e define as portas que permitirão adicionar gateways de pagamento, transportadoras e mecanismos de estoque.

## Arquitetura

O projeto utiliza um monólito modular com arquitetura hexagonal. Cada módulo é organizado em `Domain`, `Application`, `Infrastructure` e `Presentation`. As regras de negócio ficam no centro e dependem apenas de PHP. Laravel, Eloquent, HTTP, Inertia e serviços externos permanecem nos adaptadores.

Os módulos planejados são:

- Catalog
- Inventory
- Cart
- Ordering
- Payment
- Shipping
- Shared

No front-end, as funcionalidades são organizadas em `domain`, `application`, `infrastructure` e `ui`. As páginas Inertia atuam como pontos de composição, evitando concentrar regras de negócio e comunicação externa em componentes visuais.

Mais detalhes estão em [docs/architecture.md](docs/architecture.md).

## Stack técnica

- PHP 8.3
- Laravel 12
- Inertia 3
- React 19
- TypeScript
- Eloquent ORM
- MySQL ou SQLite
- Pest
- Vite
- Tailwind CSS

## Decisões

O monólito modular foi escolhido para manter implantação e operação simples sem abrir mão de limites explícitos entre os domínios.

A arquitetura hexagonal foi aplicada para permitir a substituição de banco, gateway de pagamento, serviço de frete e outros fornecedores por adaptadores.

Valores monetários são armazenados em centavos inteiros e carregam a moeda, evitando erros de ponto flutuante.

Produtos utilizam UUIDs, evitando expor identificadores sequenciais e mantendo referências estáveis entre módulos.

Models Eloquent ficam na infraestrutura. Entidades de domínio não estendem classes do framework.

Testes arquiteturais verificam automaticamente que as camadas de domínio não importam Laravel ou Eloquent.

ADRs registram o contexto e as consequências das decisões mais importantes.

## Desafios

- Definir limites claros entre catálogo, estoque, carrinho, pedidos e pagamentos.
- Evitar que a conveniência do Eloquent introduza dependências do framework no domínio.
- Projetar contratos de pagamento e frete antes de escolher fornecedores definitivos.
- Migrar uma interface baseada em dados fixos para casos de uso e persistência reais.
- Manter backend e front-end modulares sem duplicar responsabilidades.

## Aprendizados

- Estruturar um monólito modular orientado a capacidades de negócio.
- Aplicar portas e adaptadores dentro de uma aplicação Laravel.
- Separar entidades de domínio de models de persistência.
- Criar value objects para dinheiro, SKU e identificadores.
- Entregar uma feature por corte vertical, do banco à interface.
- Usar testes para proteger decisões arquiteturais.
- Preparar integrações externas para substituição por configuração.

## Estado atual

- Arquitetura e ADRs documentados.
- Catálogo implementado do domínio à interface.
- Persistência de produtos com adaptador Eloquent.
- Dados demonstrativos reproduzíveis.
- Contratos iniciais de estoque, carrinho, pedidos, pagamentos e frete.
- Testes unitários, funcionais e arquiteturais.

## Próximas etapas

- Implementar movimentações e reservas de estoque.
- Persistir carrinhos e adicionar itens pela interface.
- Modelar checkout e ciclo de vida dos pedidos.
- Criar um gateway de pagamento falso para desenvolvimento.
- Adicionar o primeiro adaptador real de pagamento.
- Implementar cotação de frete por adaptadores.
- Criar área administrativa, autenticação e autorização.

## Execução local

Consulte [docs/development.md](docs/development.md) para instalação, testes, dados demonstrativos e convenções de commits.

