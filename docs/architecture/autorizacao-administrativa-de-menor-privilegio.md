# Autorização administrativa de menor privilégio

## Contexto

O painel utilizava apenas `is_admin`, concedendo acesso integral a qualquer administrador e sem uma trilha única para mutações administrativas.

## Decisão

Manter `is_admin` como marcador de entrada no painel, adicionar `is_super_admin` para o proprietário e persistir capacidades individuais em `admin_user_permissions`. O enum `AdminPermission` é a linguagem única usada por casos de uso, Gates, rotas, propriedades Inertia e sidebar.

Administradores existentes são convertidos em proprietários na migration. Novos administradores são sempre limitados e só podem ser promovidos a partir de uma conta de cliente com e-mail verificado. A capacidade `admin.administrators.manage` é exclusiva do proprietário e não é concedível.

Cada mutação administrativa passa por `AuditAdministrativeMutation`. O adaptador grava uma entrada `attempted` antes da execução e outra `completed` ou `rejected` ao final. Somente metadados mínimos são aceitos.

## Consequências

- o backend continua sendo a fonte de verdade, mesmo com o menu filtrado;
- novas rotas administrativas precisam declarar uma capacidade;
- novas capacidades precisam ser incluídas no enum e na matriz de navegação;
- revogação tem efeito imediato sobre sessões persistidas;
- auditoria cresce até a rotina diária aplicar a retenção configurada;
- o segundo fator por e-mail protege o MVP, enquanto TOTP ou WebAuthn permanecem como endurecimento contra phishing.

## Alternativas rejeitadas

- somente esconder menus: não protege requisições diretas;
- papéis em JSON no usuário: dificulta constraints, consulta e auditoria;
- pacote externo de RBAC: adicionaria dependência e abstração além do necessário para a matriz atual;
- registrar o payload completo: aumentaria exposição de dados pessoais e segredos.
