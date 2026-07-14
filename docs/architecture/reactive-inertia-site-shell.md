# Shell reativo para navegação Inertia

## Contexto

O cabeçalho, rodapé e propriedades globais eram calculados somente com `props.initialPage`. O componente de página do Inertia mudava após login e logout, mas o shell externo permanecia com usuário e tipo de página da primeira carga.

## Decisão

Introduzir `SiteShell` como componente React responsável pelo chrome global. Ele inicia com a primeira página e observa eventos `success` do roteador para substituir o nome do componente e as propriedades compartilhadas.

O shell decide dinamicamente se a página pertence à loja ou ao backoffice. O cabeçalho recebe `auth.user` atual e mostra ações conforme autenticação e `is_admin`; a autorização real permanece no servidor.

## Limites arquiteturais

O shell não autentica, não autoriza e não persiste usuário. Ele apenas projeta a sessão enviada pelo middleware Inertia. Login, logout, regeneração de sessão e proteção das rotas continuam no Laravel.

## Consequências

Login e logout refletem imediatamente no chrome da loja. Navegações SPA entre loja e painel deixam de manter cabeçalho ou rodapé incorretos. Outras propriedades globais, como configurações e categorias, também passam a acompanhar a página atual.
