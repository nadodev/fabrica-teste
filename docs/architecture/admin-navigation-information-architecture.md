# Arquitetura da informação da navegação administrativa

## Contexto

A sidebar administrativa apresentava uma lista única, misturando catálogo, vendas, conteúdo e operação. A quantidade crescente de links reduzia a leitura rápida e a rota operacional existente não tinha acesso na navegação.

## Decisão

Manter o `AdminLayout` como ponto único de composição e representar a navegação por uma coleção tipada de grupos e itens. Dashboard fica isolado; páginas de domínio são agrupadas por finalidade; “Ver loja” é tratado como saída para o ambiente público.

O estado ativo é calculado a partir da URL Inertia: igualdade para a rota base e prefixo para páginas filhas. A semântica `aria-current` acompanha o destaque visual, evitando depender somente de cor.

## Mapeamento atual

- Catálogo: Produtos, Categorias e Estoque.
- Vendas: Pedidos e Cupons e promoções.
- Clientes: Clientes.
- Conteúdo: Banners, Notificações, Lojas, Nossa história e Marketing.
- Relatórios: Relatórios.
- Configurações: Geral, Frete e entrega e Sistema e segurança.

## Limites arquiteturais

A coleção contém somente destinos com rotas e telas existentes. Ela não antecipa módulos futuros nem assume autorização granular por item. O backend continua sendo a autoridade de autenticação e autorização.

## Consequências

A navegação ganha ordem previsível, nomenclatura mais clara e um único ponto de manutenção. Grupos com poucos itens são aceitos agora para preservar uma taxonomia que possa crescer sem reorganizações frequentes.
