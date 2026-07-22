# Permissões e auditoria administrativa

## Objetivo

Aplicar menor privilégio ao painel administrativo, permitir que o proprietário conceda acessos específicos e manter uma trilha sanitizada de todas as tentativas de alteração.

## Escopo

- proprietário administrativo com acesso integral;
- permissões granulares por capacidade;
- promoção de cliente verificado para administrador;
- atualização e revogação de acesso;
- encerramento das sessões ao revogar;
- menu e rotas filtrados pela mesma fonte de autorização;
- auditoria de tentativas, sucessos e rejeições;
- retenção configurável da auditoria.

## Fora do escopo

- autenticação em dois fatores;
- criação de usuários sem cadastro prévio;
- papéis nomeados ou hierarquias customizáveis;
- exportação da trilha de auditoria.

## Regras de negócio

- Todo administrador visualiza o dashboard.
- Permissão de gerenciamento inclui a respectiva permissão de visualização.
- Somente proprietário pode gerenciar administradores.
- Proprietário não pode ser rebaixado pela interface.
- Uma conta não altera ou revoga o próprio acesso.
- Só um cliente com e-mail confirmado pode ser promovido.
- Um administrador não concede uma permissão que não possui.
- A revogação encerra sessões persistidas e invalida o token de lembrança.
- O corpo submetido nunca é armazenado na auditoria.

## Fluxo principal

O proprietário abre **Usuários e permissões**, informa o e-mail de um cliente verificado, escolhe capacidades e confirma. O caso de uso valida ator e candidato, normaliza dependências, persiste tudo em transação e o middleware registra tentativa e conclusão.

## Fluxos alternativos

- candidato inexistente ou não verificado: solicitação rejeitada;
- tentativa de autogerenciamento ou alteração de proprietário: rejeitada;
- acesso sem permissão: HTTP 403 e auditoria com resultado `rejected`;
- revogação: permissões e sessões são removidas atomicamente.

## Casos de uso

- `PromoteAdministrator`;
- `UpdateAdministratorPermissions`;
- `RevokeAdministrator`;
- `ListAdministrators`;
- `ListAdminAudit`;
- `PruneAdminAuditLogs`.

## Arquitetura

O enum e o conjunto normalizado de permissões ficam no Domain. Os casos de uso dependem de portas de repositório, verificação, auditoria, retenção e transação. Query Builder, Gates, middleware e scheduler são adaptadores de Infrastructure e Presentation.

## Portas e adaptadores

- `AdministratorRepository` → `DatabaseAdministratorRepository`;
- `AdminPermissionChecker` → `DatabaseAdministratorRepository`;
- `AdminAuditRecorder` e `AdminAuditRetention` → `DatabaseAdminAuditRecorder`;
- `AdminAuditReadModel` → `DatabaseAdminAuditReadModel`.

## Persistência

`admin_user_permissions` possui chave primária composta por usuário e permissão. `admin_audit_logs` usa UUID e índices por ação, assunto, data e resultado. `users.is_super_admin` diferencia proprietários dos operadores limitados.

## Transações

Promoção, sincronização de permissões e revogação são executadas na unidade de trabalho compartilhada.

## Idempotência

As mutações HTTP exigem `Idempotency-Key` e reutilizam o middleware de idempotência administrativa existente. A chave composta impede duplicação de permissões.

## Segurança

Gates protegem todas as rotas administrativas por capacidade. Administradores existentes são migrados como proprietários para evitar bloqueio no deploy. Contas limitadas não recebem a permissão de gerenciar administradores, mesmo que uma linha inválida seja inserida diretamente no banco. IP é armazenado somente como HMAC; payload, senha, token e dados de cartão não entram na auditoria.

## Eventos

Não há efeito externo. A auditoria é gravada de forma síncrona antes da mutação para que uma falha de registro interrompa a operação.

## Interface

A sidebar mostra apenas os grupos autorizados. A tela **Usuários e permissões** reaproveita o layout administrativo e apresenta permissões agrupadas, estados de processamento, erros de validação e proteção visual para proprietário e conta atual.

## Testes automatizados

Há cobertura de menor privilégio, menu compartilhado, promoção, dependências, candidato não verificado, autogerenciamento, proteção do proprietário, permissão forjada, revogação de sessões, auditoria sanitizada e retenção.

## Casos de QA

Consulte `docs/qa/2026-07-22-permissoes-e-auditoria-administrativa.md`.

## Como validar

1. Execute as migrations.
2. Entre com um administrador já existente, agora proprietário.
3. Promova um cliente com e-mail confirmado.
4. Entre com a conta limitada e confira menu e respostas 403.
5. Consulte **Sistema e segurança** para conferir os registros estruturados.

## Riscos e limitações

O controle é granular por capacidade e o login agora exige código descartável por e-mail. TOTP ou WebAuthn continuam recomendados contra phishing. A retenção deve ser alinhada à política LGPD da operação.
