# QA — Navegação reativa de autenticação na loja

## Objetivo

Validar ações do cabeçalho para visitante, cliente e administrador durante navegações Inertia.

## Pré-condições

Build atualizado e contas temporárias de cliente e administrador.

## Dados de teste

Cliente e administrador locais exclusivos para QA, ambos removidos ao final.

## Ambiente

Aplicação local `uniform-crafted.test`, navegador integrado, desktop e viewport de 390 × 844 px.

## Casos de sucesso

### AUTHNAV-01 — Login do cliente

- Cenário: visitante autentica como cliente.
- Pré-condições: home exibe Entrar e Cadastrar.
- Passos: abrir Entrar, informar credenciais e enviar.
- Resultado esperado: redirecionar para Minha conta e trocar ações por Minha conta e Sair.
- Resultado obtido: cabeçalho foi atualizado na mesma navegação.
- Status: OK.

### AUTHNAV-02 — Atalho administrativo

- Cenário: administrador autentica pela loja.
- Pré-condições: conta com `is_admin=true`.
- Passos: entrar e inspecionar o cabeçalho.
- Resultado esperado: Minha conta, Painel admin e Sair; nenhum Painel admin para cliente comum.
- Resultado obtido: atalho apareceu somente para o administrador.
- Status: OK.

## Casos de validação

### AUTHNAV-03 — Estado de visitante

- Cenário: sessão ausente.
- Pré-condições: usuário desautenticado.
- Passos: abrir a home.
- Resultado esperado: Entrar e Cadastrar, sem Minha conta, Sair ou Painel admin.
- Resultado obtido: estado de visitante confirmado.
- Status: OK.

## Casos de autorização

### AUTHNAV-04 — Cliente sem atalho admin

- Cenário: cliente comum está autenticado.
- Pré-condições: `is_admin=false`.
- Passos: inspecionar o cabeçalho depois do login.
- Resultado esperado: Painel admin ausente.
- Resultado obtido: somente Minha conta e Sair foram apresentados.
- Status: OK.

## Casos de falha

### AUTHNAV-05 — Logout administrativo

- Cenário: administrador sai pelo painel.
- Pré-condições: painel administrativo aberto.
- Passos: acionar Sair.
- Resultado esperado: home pública com Entrar e Cadastrar.
- Resultado obtido: shell público foi restaurado já desautenticado.
- Status: OK.

## Casos de concorrência

### AUTHNAV-06 — Navegações sucessivas

- Cenário: respostas Inertia mudam rapidamente o tipo de página.
- Pré-condições: sessão administrativa ativa.
- Passos: loja, painel e logout em sequência.
- Resultado esperado: cada sucesso substitui integralmente o snapshot anterior.
- Resultado obtido: estado final correspondeu à última resposta.
- Status: OK.

## Casos de idempotência

### AUTHNAV-07 — Página autenticada repetida

- Cenário: usuário navega por várias páginas mantendo a sessão.
- Pré-condições: usuário autenticado.
- Passos: navegar sem alterar autenticação.
- Resultado esperado: ações permanecem estáveis, sem duplicação.
- Resultado obtido: shell usa um único listener e substituição de estado.
- Status: OK.

## Casos de regressão

### AUTHNAV-08 — Transição loja/painel

- Cenário: administrador usa Painel admin.
- Pré-condições: cabeçalho público visível.
- Passos: abrir `/admin` pelo atalho.
- Resultado esperado: cabeçalho e rodapé públicos desaparecem e sidebar administrativa aparece.
- Resultado obtido: transição correta, com navegação administrativa agrupada preservada.
- Status: OK.

## Casos responsivos

### AUTHNAV-09 — Ações no celular

- Cenário: administrador em viewport de 390 px.
- Pré-condições: sessão ativa.
- Passos: inspecionar a navegação horizontal.
- Resultado esperado: Minha conta, Painel admin e Sair disponíveis sem estouro horizontal do documento.
- Resultado obtido: ações visíveis; documento com 375 px úteis dentro da viewport de 390 px.
- Status: OK.

## Casos de acessibilidade

### AUTHNAV-10 — Ações textuais e botões

- Cenário: navegação assistida.
- Pré-condições: cabeçalho carregado.
- Passos: inspecionar links e botões por nome acessível.
- Resultado esperado: links nomeados e logout como botão Sair.
- Resultado obtido: controles localizados semanticamente no navegador.
- Status: OK.

## Evidências

Snapshots semânticos, inspeção textual do cabeçalho, navegação real cliente/admin, logout nas duas áreas e medição de viewport.

## Riscos conhecidos

Não há teste automatizado de componentes React; o comportamento visual depende do QA no navegador até essa infraestrutura existir.
