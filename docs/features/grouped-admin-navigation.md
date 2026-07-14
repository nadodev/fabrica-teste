# Navegação administrativa agrupada

## Objetivo

Organizar a sidebar do painel por áreas de trabalho, facilitando a localização das páginas já existentes sem apresentar funcionalidades que o projeto ainda não possui.

## Escopo

Dashboard isolado; grupos Catálogo, Vendas, Clientes, Conteúdo, Relatórios e Configurações; nomes mais claros; indicação da página ativa; acesso à loja separado dos itens administrativos; navegação completa em telas grandes e pequenas.

## Fora do escopo

Criação de páginas para marcas, variações, atributos, produção, logística avançada, blog, avaliações ou outras opções sugeridas que ainda não possuem rota e tela no projeto. Menus recolhíveis e uma sidebar móvel em formato de gaveta também não fazem parte desta entrega.

## Regras de negócio

- Somente páginas administrativas existentes podem aparecer na navegação.
- Dashboard permanece como acesso principal e fora dos grupos.
- A página atual deve ser identificada visualmente e com `aria-current="page"`.
- Páginas filhas, como cadastro e edição de produtos, mantêm o item pai como ativo.
- O acesso público “Ver loja” fica separado dos recursos administrativos.
- “Sistema e segurança” aponta para a área operacional existente.

## Fluxo principal

O administrador entra no painel, identifica a área desejada pelo título do grupo e acessa a funcionalidade. O item correspondente permanece destacado enquanto ele estiver naquela área ou em uma página filha.

## Fluxos alternativos

Em telas estreitas, a navegação permanece empilhada acima do conteúdo e todos os grupos continuam disponíveis. Se uma nova funcionalidade for criada, ela só deve ser adicionada ao grupo após existir uma rota navegável.

## Casos de uso

O caso de uso de navegação do administrador foi reorganizado. Não houve alteração nos casos de uso de catálogo, pedidos, clientes, conteúdo, relatórios ou configurações.

## Arquitetura

A estrutura declarativa dos grupos permanece no componente compartilhado `AdminLayout`. Cada página Inertia continua fornecendo apenas seu título e conteúdo.

## Portas e adaptadores

Sem alteração. A navegação usa links Inertia para as rotas HTTP já existentes.

## Persistência

Sem alteração.

## Transações

Não se aplica; a alteração é somente de navegação e apresentação.

## Idempotência

Não se aplica; abrir um item da sidebar não modifica estado persistido.

## Segurança

Os links não substituem autorização. Todas as rotas continuam protegidas pelos middlewares administrativos existentes. Nenhum dado sensível foi adicionado à interface.

## Eventos

Sem alteração.

## Interface

Os grupos têm títulos semânticos, itens recuados com separador visual, ícones, estados de foco e hover e destaque persistente da rota atual. A sidebar passa a ter rolagem própria em desktop quando sua altura exceder a janela.

## Testes automatizados

ESLint, TypeScript, Prettier, build de produção e a suíte Laravel verificam regressões estruturais. O projeto ainda não possui uma suíte de componentes React para afirmar a ordem visual dos itens.

## Casos de QA

Consulte `docs/qa/2026-07-13-grouped-admin-navigation.md`.

## Como validar

Entrar em `/admin`, conferir os grupos, abrir Produtos e Configurações e verificar o item ativo. Repetir em viewport de 390 px e confirmar que todos os itens continuam disponíveis sem rolagem horizontal.

## Riscos e limitações

O menu não exibe áreas sem implementação. À medida que novas páginas forem entregues, a estrutura deverá ser atualizada deliberadamente para não criar atalhos sem destino.
