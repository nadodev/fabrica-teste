# PROJETO #005 / RELATÓRIO TÉCNICO

## EM DESENVOLVIMENTO

### FRAMEWORK PHP

Loja virtual modular com Laravel, Inertia e React. Um projeto de portfólio criado para explorar arquitetura hexagonal, monólito modular e integrações substituíveis em um domínio real de comércio eletrônico.

## PROGRESSO

**Em evolucao para operacao completa em producao**

[Repositório](https://github.com/nadodev/fabrica-teste)

## Visão geral

Uma loja virtual de uniformes criada para praticar modelagem de domínio e evolução sustentável de software. O objetivo é construir catálogo, estoque, carrinho, pedidos, pagamentos e fretes sem acoplar as regras de negócio ao Laravel, ao banco de dados ou a fornecedores externos.

A aplicacao ja opera catalogo, estoque, carrinho, checkout, clientes, pedidos, cupons, frete pelo Melhor Envio e pagamentos reais pelo Asaas. A etapa atual fortalece logistica, seguranca, observabilidade e operacao de producao.

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
- Estoque unificado por SKU/variacao, carrinho persistido, pedidos e checkout transacional.
- Testes unitários, funcionais e arquiteturais.
- Idempotência persistida para comandos comerciais.
- Estoque transacional com reservas e ledger de movimentações.
- Rate limits e headers de segurança em modo compatível com observação de CSP.
- Carrinho persistido e checkout local com reserva de estoque.
- Pedidos com snapshots imutáveis dos itens.
- Dashboard administrativo protegido para cadastrar e publicar produtos.
- Edição, arquivamento e upload validado de imagens no dashboard.
- Gateway falso e integracao real Asaas com Pix, cartao, boleto, webhook, reconciliacao e estorno.
- Melhor Envio com credencial no ambiente, peso e dimensoes por produto e revalidacao no checkout.
- Cadastro de clientes e enderecos com preenchimento automatico por CEP.
- Maquina de estados administrativa e historico auditavel do pedido.

## Próximas etapas

- Completar etiqueta, expedicao e rastreamento no Melhor Envio.
- Adicionar detalhe do pedido para o cliente e repeticao segura de pagamento.
- Implementar recuperacao de senha, verificacao de e-mail e permissoes administrativas especificas.
- Paginar consultas administrativas e adicionar cache de configuracoes publicas.
- Implantar backup/restauracao MySQL, metricas, alertas e testes concorrentes em InnoDB.

O diagnóstico completo está em [docs/security-readiness.md](docs/security-readiness.md), e o plano funcional em [docs/ecommerce-roadmap.md](docs/ecommerce-roadmap.md).

## Execução local

Consulte [docs/development.md](docs/development.md) para instalação, testes, dados demonstrativos e convenções de commits.
