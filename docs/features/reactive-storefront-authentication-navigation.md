# Navegação reativa de autenticação na loja

## Objetivo

Manter as ações do cabeçalho sincronizadas com a sessão atual durante login, logout e transições entre loja e painel administrativo.

## Escopo

Estado de visitante, cliente e administrador; ações Minha conta e Sair; atalho Painel admin exclusivo para administradores; atualização sem recarga manual; comportamento equivalente em desktop e celular; montagem e desmontagem correta do cabeçalho público ao alternar entre loja e backoffice.

## Fora do escopo

Novos perfis, permissões granulares, alteração das telas de login, recuperação de senha ou reformulação visual do cabeçalho.

## Regras de negócio

- Visitante vê Entrar e Cadastrar.
- Usuário autenticado vê Minha conta e Sair.
- Somente usuário com `is_admin=true` vê Painel admin.
- Logout invalida a sessão e restaura as ações de visitante.
- Páginas administrativas não exibem cabeçalho, rodapé ou aviso de cookies da loja.
- Retorno do painel para a loja restaura o shell público com a sessão atual.

## Fluxo principal

O visitante entra pela loja. Após a resposta Inertia de autenticação, o shell global recebe as novas propriedades compartilhadas e atualiza o cabeçalho. O logout faz o caminho inverso sem depender de atualização manual do navegador.

## Fluxos alternativos

Administrador autenticado pela entrada de cliente pode acessar Minha conta ou Painel admin. Ao navegar para o painel, o shell público é ocultado; ao sair do painel, a página inicial é apresentada novamente como visitante.

## Casos de uso

Foram ajustados os casos de uso de navegação autenticada no cliente. Os casos de uso de login e logout do servidor foram preservados.

## Arquitetura

`SiteShell` acompanha os eventos de sucesso do roteador Inertia e mantém componente e propriedades compartilhadas atuais. `SiteHeader` continua puramente apresentacional e recebe a identidade vigente.

## Portas e adaptadores

O adaptador HTTP/Inertia continua compartilhando `auth.user`; o roteador Inertia é o canal de atualização do shell React.

## Persistência

Sem alteração. A sessão Laravel permanece como fonte de verdade.

## Transações

Não se aplica.

## Idempotência

Renderizações repetidas do mesmo estado produzem as mesmas ações. Repetir logout após a sessão ter sido invalidada continua protegido pelo middleware `auth`.

## Segurança

O botão administrativo depende do indicador enviado pelo servidor, mas não concede autorização. Rotas administrativas continuam protegidas por autenticação e middleware de administrador. Logout continua invalidando sessão e regenerando o token CSRF.

## Eventos

O shell observa o evento Inertia `success` e substitui o snapshot anterior pelas propriedades da página recém-recebida.

## Interface

Desktop mantém as ações no bloco superior do cabeçalho. Em telas menores, Entrar, Cadastrar, Minha conta, Painel admin e Sair ficam disponíveis na navegação horizontal existente.

## Testes automatizados

Testes de feature cobrem propriedades de visitante, cliente e administrador e a invalidação das duas rotas de logout. ESLint, TypeScript, Prettier e build validam o shell e o cabeçalho.

## Casos de QA

Consulte `docs/qa/2026-07-13-reactive-storefront-authentication-navigation.md`.

## Como validar

Abrir a home como visitante, entrar como cliente, sair, entrar como administrador, acessar o painel e sair novamente. Repetir a inspeção em 390 px.

## Riscos e limitações

Não existe suíte automatizada de componentes React no projeto; as transições visuais foram validadas no navegador integrado.
