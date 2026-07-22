# Identidade segura do cliente

## Objetivo

Permitir que clientes confirmem o e-mail e recuperem a senha sem exposição de contas, dados pessoais ou tokens.

## Escopo

Cadastro com e-mail pendente, envio e reenvio de confirmação, link assinado e expirável, bloqueio da área do cliente enquanto o e-mail não estiver confirmado, solicitação e redefinição de senha, invalidação do token utilizado e compartilhamento mínimo da identidade com o frontend.

## Fora do escopo

Autenticação em dois fatores para clientes, login social, troca do e-mail cadastrado e encerramento da conta. Papéis administrativos e segundo fator do painel são documentados separadamente.

## Regras de negócio

- Toda nova conta de cliente começa com e-mail não verificado.
- Pedidos, perfil e endereços da conta somente ficam acessíveis após confirmação.
- O link de confirmação é assinado, vinculado ao usuário autenticado e expira em 60 minutos por padrão.
- Solicitar recuperação sempre retorna a mesma mensagem, exista ou não uma conta.
- O token de redefinição expira em 60 minutos e só pode ser usado uma vez.
- Senhas novas possuem pelo menos 12 caracteres, letras maiúsculas e minúsculas e números.
- Em produção, a senha também é consultada na lista de credenciais comprometidas do validador Laravel.
- Falha no primeiro envio da confirmação não desfaz a conta; o cliente pode reenviar pela tela protegida.

## Fluxo principal

O cliente se cadastra, é autenticado e recebe a tela de confirmação. O e-mail contém URL temporária assinada. Após confirmar, o servidor marca `email_verified_at` e libera a conta. Na recuperação de senha, o cliente solicita o link, recebe um token do broker Laravel e define uma nova senha; o token é removido após sucesso.

## Fluxos alternativos

Links inválidos, expirados, adulterados ou pertencentes a outro usuário são rejeitados. Reenvios e solicitações repetidas são limitados. Um e-mail inexistente produz a mesma resposta de um e-mail cadastrado.

## Casos de uso

- `RegisterCustomer`
- `SendVerificationEmail`
- `VerifyCustomerEmail`
- `SendPasswordResetLink`
- `ResetCustomerPassword`

## Arquitetura

O módulo `Identity` mantém os casos de uso em Application, contratos para identidade, notificações e senhas, e adaptadores Laravel/Eloquent em Infrastructure. Controllers recebem Form Requests, criam DTOs, chamam os casos de uso e traduzem os resultados para HTTP/Inertia.

## Portas e adaptadores

- `CustomerIdentityRepository`: criação e confirmação da identidade.
- `CustomerNotificationSender`: envio resiliente da confirmação.
- `PasswordResetter`: adaptação do password broker Laravel.
- `EloquentCustomerIdentityRepository`, `LaravelCustomerNotificationSender` e `LaravelPasswordResetter`: implementações concretas.

## Persistência

Usa `users.email_verified_at` e `password_reset_tokens`, já existentes. Não houve migration.

## Transações

Cadastro consiste em uma inserção atômica protegida pela restrição única de e-mail. O envio externo ocorre depois da criação e sua falha é registrada sem apagar a conta.

## Idempotência

Confirmar um e-mail já verificado não altera novamente o estado. O token de senha é invalidado no primeiro sucesso. Reenvio pode ser repetido dentro do limite de frequência.

## Segurança

As URLs de confirmação são assinadas e temporárias. O hash é comparado ao e-mail da sessão autenticada. Recuperação não revela existência de conta. Rotas possuem rate limiting, senhas e tokens não são compartilhados nem colocados em sessão e a área do cliente usa middleware `verified`.

## Eventos

Uma confirmação inédita publica o evento Laravel `Verified`.

## Interface

Foram adicionadas telas de recuperar senha, redefinir senha e confirmar e-mail, reutilizando cartões, cores, campos e feedbacks já empregados no login e cadastro.

## Testes automatizados

Cobrem cadastro não verificado, notificação, bloqueio da conta, confirmação válida, link expirado, vínculo incorreto, reenvio, não enumeração de conta, redefinição válida, token de uso único, expiração e rate limiting.

## Casos de QA

Consulte `docs/qa/2026-07-22-identidade-segura-do-cliente.md`.

## Como validar

Configure um mailer real, crie uma conta, abra o link recebido, acesse Minha conta, saia, solicite recuperação, redefina a senha e confirme que o token não funciona novamente.

## Riscos e limitações

Entrega depende da reputação e configuração do provedor de e-mail. A funcionalidade não substitui monitoramento de bounce, SPF, DKIM e DMARC. Usuários antigos sem `email_verified_at` precisarão confirmar o endereço no próximo acesso à conta.
