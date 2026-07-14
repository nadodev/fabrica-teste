# QA — Navegação administrativa agrupada

## Objetivo

Validar a organização, navegação, responsividade e semântica da nova sidebar administrativa.

## Pré-condições

Aplicação compilada, usuário administrador ativo e acesso ao painel local.

## Dados de teste

Conta administrativa temporária criada exclusivamente para a validação e removida ao final.

## Ambiente

Aplicação Laravel/Inertia local em `uniform-crafted.test`, build de produção e navegador integrado em viewport desktop e 390 × 844 px.

## Casos de sucesso

### NAV-01 — Exibir os grupos atuais

- Cenário: administrador abre uma página do painel.
- Pré-condições: sessão administrativa autenticada.
- Passos: abrir Produtos e inspecionar a sidebar.
- Resultado esperado: grupos Catálogo, Vendas, Clientes, Conteúdo, Relatórios e Configurações, na ordem definida.
- Resultado obtido: os seis grupos e 17 links navegáveis foram exibidos.
- Status: OK.

### NAV-02 — Manter somente destinos existentes

- Cenário: administrador consulta os itens disponíveis.
- Pré-condições: rotas administrativas carregadas.
- Passos: comparar links da sidebar com as telas atuais.
- Resultado esperado: nenhum atalho sem rota; área operacional disponível como “Sistema e segurança”.
- Resultado obtido: todos os destinos correspondem a rotas existentes e `/admin/operacao` foi incluída.
- Status: OK.

### NAV-03 — Destacar a área atual

- Cenário: administrador acessa Produtos e depois Configurações.
- Pré-condições: sidebar visível.
- Passos: navegar para `/admin/produtos` e `/admin/configuracoes`.
- Resultado esperado: apenas o item da área atual recebe destaque e `aria-current="page"`.
- Resultado obtido: Produtos e Geral foram identificados como ativos em suas respectivas páginas.
- Status: OK.

## Casos de validação

### NAV-04 — Ordem e nomenclatura

- Cenário: leitura sequencial da navegação.
- Pré-condições: painel carregado.
- Passos: inspecionar títulos e rótulos.
- Resultado esperado: textos claros, acentuados e coerentes com o domínio.
- Resultado obtido: Catálogo, Conteúdo, Relatórios, Configurações e seus itens foram renderizados corretamente.
- Status: OK.

## Casos de autorização

### NAV-05 — Acesso administrativo preservado

- Cenário: usuário autenticado acessa a sidebar.
- Pré-condições: conta com `is_admin` habilitado.
- Passos: autenticar e abrir o painel.
- Resultado esperado: painel disponível somente após autenticação administrativa.
- Resultado obtido: autenticação administrativa exigida e preservada; a alteração não modifica middleware.
- Status: OK.

## Casos de falha

### NAV-06 — Página filha

- Cenário: administrador abre cadastro ou edição dentro de uma área.
- Pré-condições: rota filha válida.
- Passos: acessar URL iniciada pelo destino principal.
- Resultado esperado: item pai permanece ativo pelo casamento de prefixo.
- Resultado obtido: regra verificada no código tipado e na compilação; não houve mutação de dados para abrir uma tela de edição.
- Status: OK.

## Casos de concorrência

### NAV-07 — Abas simultâneas

- Cenário: duas páginas administrativas abertas em paralelo.
- Pré-condições: mesma sessão em duas abas.
- Passos: abrir áreas diferentes.
- Resultado esperado: cada página calcula seu próprio destaque pela URL atual.
- Resultado obtido: estado não é compartilhado nem persistido; cálculo é local por página.
- Status: OK.

## Casos de idempotência

### NAV-08 — Reabrir o mesmo destino

- Cenário: usuário abre repetidamente o mesmo link.
- Pré-condições: sessão ativa.
- Passos: navegar mais de uma vez para o mesmo destino.
- Resultado esperado: mesma página e mesmo estado visual, sem gravação de dados.
- Resultado obtido: navegação é somente leitura e determinística.
- Status: OK.

## Casos de regressão

### NAV-09 — Acesso à loja

- Cenário: administrador deseja retornar ao site público.
- Pré-condições: sidebar aberta.
- Passos: localizar “Ver loja”.
- Resultado esperado: link separado dos grupos e apontando para `/produtos`.
- Resultado obtido: link preservado após divisor visual.
- Status: OK.

## Casos responsivos

### NAV-10 — Viewport de celular

- Cenário: painel aberto em 390 × 844 px.
- Pré-condições: configuração Geral carregada.
- Passos: medir viewport, largura do documento e itens da navegação.
- Resultado esperado: todos os grupos e links disponíveis, sem estouro horizontal.
- Resultado obtido: 6 grupos e 17 links; documento com 375 px úteis dentro de viewport de 390 px, sem largura excedente.
- Status: OK.

## Casos de acessibilidade

### NAV-11 — Marcos e página atual

- Cenário: navegação assistida.
- Pré-condições: sidebar renderizada.
- Passos: inspecionar o marco de navegação, títulos e item ativo.
- Resultado esperado: `nav` nomeado, títulos semânticos e `aria-current="page"`.
- Resultado obtido: “Navegação administrativa”, títulos de nível 2 e estado atual confirmados.
- Status: OK.

## Evidências

Snapshot semântico no navegador, inspeção do estado ativo, medição responsiva, build de produção e verificações estáticas.

## Riscos conhecidos

O projeto ainda não possui testes automatizados de componentes React; ordem visual e responsividade dependem de QA no navegador até essa infraestrutura existir.
