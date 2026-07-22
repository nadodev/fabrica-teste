# QA — Identidade segura do cliente

## Objetivo

Validar confirmação de e-mail e recuperação de senha com segurança e sem regressão de autenticação.

## Pré-condições

Banco migrado, `APP_URL` público correto, HTTPS e mailer real configurado.

## Dados de teste

Conta nova, conta confirmada, conta não confirmada e endereço de e-mail inexistente.

## Ambiente

Testes automatizados em Laravel 12, PHP 8.3 e SQLite em memória. Entrega real de e-mail e validação visual em produção controlada permanecem manuais.

## Casos de sucesso

### ID-01 — Cadastro e confirmação

- Cenário: cliente cria uma conta nova.
- Pré-condições: e-mail ainda não cadastrado.
- Passos: cadastrar, abrir o link recebido e acessar Minha conta.
- Resultado esperado: conta não verificada antes do link e liberada depois da confirmação.
- Resultado obtido: coberto por teste de feature com notificação e URL assinada.
- Status: OK.

### ID-02 — Redefinição válida

- Cenário: cliente esqueceu a senha.
- Pré-condições: conta cadastrada.
- Passos: solicitar link, definir senha nova e entrar.
- Resultado esperado: senha alterada e token consumido.
- Resultado obtido: teste confirma hash novo e rejeição do segundo uso.
- Status: OK.

## Casos de validação

### ID-03 — Política de senha

- Cenário: senha fraca ou confirmação divergente.
- Pré-condições: formulário de cadastro ou redefinição aberto.
- Passos: enviar senha fora da política.
- Resultado esperado: erro no campo sem alterar credenciais.
- Resultado obtido: regras centralizadas em `Password::defaults` e Form Requests.
- Status: OK.

## Casos de autorização

### ID-04 — Confirmação de outra conta

- Cenário: usuário autenticado abre link pertencente a outra conta.
- Pré-condições: duas contas não verificadas.
- Passos: autenticar a primeira e abrir URL assinada da segunda.
- Resultado esperado: HTTP 403 e nenhuma conta alterada.
- Resultado obtido: teste de feature aprovado.
- Status: OK.

### ID-05 — Conta não verificada

- Cenário: usuário tenta acessar perfil, pedidos ou endereços.
- Pré-condições: sessão ativa e `email_verified_at` vazio.
- Passos: abrir Minha conta.
- Resultado esperado: redirecionamento para confirmação.
- Resultado obtido: teste de feature aprovado.
- Status: OK.

## Casos de falha

### ID-06 — Links inválidos e expirados

- Cenário: token ou assinatura expirou ou foi adulterado.
- Pré-condições: conta cadastrada.
- Passos: abrir confirmação expirada ou enviar token de senha inválido/expirado.
- Resultado esperado: rejeição sem alterar conta ou senha.
- Resultado obtido: testes de feature aprovados.
- Status: OK.

### ID-07 — Falha do provedor de e-mail

- Cenário: mailer está indisponível no primeiro envio.
- Pré-condições: falha simulada no transporte.
- Passos: cadastrar e tentar reenviar.
- Resultado esperado: conta preservada, falha registrada e mensagem segura no reenvio.
- Resultado obtido: comportamento implementado; simulação operacional pendente.
- Status: PENDENTE EM HOMOLOGAÇÃO.

## Casos de concorrência

### ID-08 — Cadastros simultâneos

- Cenário: duas requisições usam o mesmo e-mail.
- Pré-condições: índice único de `users.email`.
- Passos: enviar cadastros concorrentes.
- Resultado esperado: somente uma conta criada.
- Resultado obtido: protegido por constraint; teste concorrente MySQL pendente.
- Status: PENDENTE MYSQL.

## Casos de idempotência

### ID-09 — Token de senha reutilizado

- Cenário: repetir a redefinição após sucesso.
- Pré-condições: primeira troca concluída.
- Passos: enviar novamente o mesmo token.
- Resultado esperado: segunda tentativa rejeitada.
- Resultado obtido: teste de feature aprovado.
- Status: OK.

## Casos de regressão

### ID-10 — Login e logout existentes

- Cenário: cliente ou administrador usa autenticação normal.
- Pré-condições: conta confirmada.
- Passos: entrar, navegar e sair.
- Resultado esperado: sessão e cabeçalho continuam reativos.
- Resultado obtido: suíte existente de autenticação aprovada.
- Status: OK.

## Casos responsivos

### ID-11 — Formulários móveis

- Cenário: telas em 390 × 844 px.
- Pré-condições: navegador de homologação.
- Passos: percorrer confirmação e redefinição.
- Resultado esperado: controles legíveis, sem rolagem horizontal e com teclado adequado.
- Resultado obtido: pendente de inspeção no navegador.
- Status: PENDENTE EM HOMOLOGAÇÃO.

## Casos de acessibilidade

### ID-12 — Teclado e mensagens

- Cenário: navegação sem mouse e leitor de tela.
- Pré-condições: páginas carregadas.
- Passos: navegar por campos e provocar erros.
- Resultado esperado: labels, foco, nomes acessíveis e alertas compreensíveis.
- Resultado obtido: marcação semântica implementada; auditoria manual pendente.
- Status: PENDENTE EM HOMOLOGAÇÃO.

## Evidências

Testes de feature do módulo Identity, suíte de navegação autenticada, análise estática, lint, tipos e build registrados na entrega.

## Riscos conhecidos

Entregabilidade precisa ser confirmada com domínio de produção, SPF, DKIM e DMARC. Concorrência deve ser repetida em MySQL/InnoDB.
