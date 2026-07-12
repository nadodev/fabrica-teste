# ADR 0001: Monólito modular com arquitetura hexagonal

- Status: aceito
- Data: 2026-07-12

## Contexto

A loja precisa administrar catálogo, estoque, carrinhos, pedidos, pagamentos e fretes. Gateways e transportadoras devem ser substituíveis, e o projeto precisa continuar simples de executar e publicar enquanto o domínio amadurece.

## Decisão

Usar um monólito modular. Cada capacidade de negócio fica em um módulo autônomo e aplica portas e adaptadores internamente. Laravel atua como mecanismo de entrega e composição, não como núcleo do domínio.

## Consequências

- Uma única aplicação, banco e processo de deploy reduzem a complexidade operacional inicial.
- Limites explícitos permitem extrair módulos no futuro, caso exista necessidade comprovada.
- Fornecedores externos são trocados por configuração e implementação de contratos.
- A equipe precisa proteger os limites para evitar dependências diretas entre models e tabelas de módulos distintos.

