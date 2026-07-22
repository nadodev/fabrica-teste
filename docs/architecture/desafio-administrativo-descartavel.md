# Desafio administrativo descartável

## Contexto

O login administrativo criava uma sessão completa imediatamente após validar a senha. A aplicação precisava adicionar um segundo passo sem armazenar segredos permanentes, sem introduzir dependência externa e sem permitir sessão parcial autenticada.

## Decisão

Usar um desafio de curta duração persistido em `admin_login_challenges`. A senha inicia o desafio, mas o usuário permanece desautenticado. A sessão guarda apenas o UUID opaco do desafio. O código é enviado por e-mail e persistido como HMAC vinculado ao UUID.

O agregado `AdminLoginChallenge` controla tentativas, expiração, bloqueio e consumo. `CompleteAdminLogin` executa a verificação sob transação e lock pessimista. A camada HTTP só cria a sessão Laravel depois de receber um resultado de sucesso do caso de uso.

O envio permanece síncrono. Colocar o código em uma fila ampliaria sua vida útil e deixaria texto puro no payload serializado. Se o transporte falhar, o desafio é invalidado.

## Consequências

- senha comprometida isoladamente não abre o painel;
- nenhum usuário parcialmente autenticado alcança middleware `auth`;
- indisponibilidade do e-mail bloqueia novos logins;
- códigos não aparecem no banco, sessão, auditoria ou logs;
- concorrência sobre o mesmo desafio resulta em apenas um consumo;
- TOTP e WebAuthn podem substituir o adaptador de notificação futuramente, preservando o caso de uso.

## Alternativas rejeitadas

- autenticar antes e marcar `mfa_pending` na sessão: aumenta o risco de rota esquecida;
- armazenar código criptografado reversível: não há necessidade de recuperar o código;
- código em cache sem persistência: reduziria rastreabilidade e segurança concorrente;
- fila de e-mail: serializaria o código em texto puro;
- desabilitar segundo fator quando o e-mail falhar: violaria fail-closed.
