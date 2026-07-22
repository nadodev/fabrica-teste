# Segundo fator administrativo por e-mail

## Objetivo

Impedir que uma senha administrativa comprometida seja suficiente para acessar o painel, exigindo um código descartável enviado ao e-mail verificado da conta.

## Escopo

- desafio obrigatório após senha válida;
- código numérico de seis dígitos;
- expiração configurável, com padrão de 10 minutos;
- cinco tentativas por desafio;
- consumo único;
- opção “manter conectado” aplicada somente depois do segundo fator;
- invalidação de desafios anteriores;
- auditoria sanitizada;
- retenção e limpeza agendada.

## Fora do escopo

- aplicativos TOTP;
- WebAuthn ou chaves físicas;
- códigos de recuperação;
- SMS ou WhatsApp;
- dispositivo confiável que dispense o segundo fator.

## Regras de negócio

- A validação da senha nunca cria uma sessão autenticada.
- Apenas administrador com e-mail confirmado recebe o código.
- O código existe em texto puro somente durante o envio do e-mail.
- Banco e auditoria armazenam apenas HMAC do código.
- Código expirado, consumido ou bloqueado não pode ser reutilizado.
- A quinta tentativa incorreta encerra o desafio.
- Um novo login invalida desafios anteriores do mesmo usuário.
- Se a conta perder acesso administrativo entre os fatores, o login é rejeitado.
- Falha no envio invalida o desafio e não autentica o usuário.

## Fluxo principal

O administrador informa e-mail e senha. `StartAdminLogin` valida as credenciais, cria um desafio, persiste seu HMAC e envia o código. O navegador permanece visitante. Na segunda tela, `CompleteAdminLogin` bloqueia o registro, valida estado e código, consome o desafio e somente então permite que a Presentation crie a sessão Laravel.

## Fluxos alternativos

- credenciais inválidas, cliente ou e-mail não confirmado: resposta genérica;
- código incorreto: tentativa incrementada;
- desafio expirado, consumido ou bloqueado: retorno ao login;
- conta revogada durante o fluxo: desafio consumido e acesso negado;
- transporte de e-mail indisponível: desafio invalidado e erro seguro.

## Casos de uso

- `StartAdminLogin`;
- `CompleteAdminLogin`;
- `PruneAdminLoginChallenges`.

## Arquitetura

`AdminLoginChallenge` concentra expiração, tentativas, bloqueio e consumo. Os casos de uso dependem de portas para credenciais, geração, HMAC, persistência, notificação, transação e auditoria. Laravel, Eloquent, Query Builder e Mail ficam nos adaptadores.

## Portas e adaptadores

- `AdminCredentialVerifier` → `EloquentAdminCredentialVerifier`;
- `AdminChallengeCodeGenerator` → `SecureAdminChallengeCodeGenerator`;
- `AdminChallengeCodeHasher` → `HmacAdminChallengeCodeHasher`;
- `AdminLoginChallengeRepository` → `DatabaseAdminLoginChallengeRepository`;
- `AdminTwoFactorNotifier` → `LaravelAdminTwoFactorNotifier`.

## Persistência

`admin_login_challenges` guarda UUID, usuário, HMAC, preferência de lembrança, tentativas, limite, expiração e consumo. Não guarda código, senha, e-mail ou dados do navegador.

## Transações

Criação/invalidação anterior e verificação/consumo são transacionais. A leitura para validação usa lock pessimista, impedindo dois consumos simultâneos do mesmo desafio.

## Idempotência

O consumo é idempotente pelo campo `consumed_at`. Repetir o código depois do sucesso retorna desafio indisponível e não cria outra sessão válida a partir dele.

## Segurança

O código usa `random_int`, UUID aleatório e HMAC-SHA-256 com a chave da aplicação. A rota possui rate limit por desafio e IP. A sessão é regenerada depois da senha e novamente após autenticar. Respostas de primeiro fator não distinguem cliente, administrador não confirmado ou senha inválida.

## Eventos

O envio é síncrono para que a aplicação saiba se o código realmente foi entregue ao transporte configurado. Não há fila contendo código em texto puro.

## Interface

A nova tela mantém o padrão visual do login, aceita apenas seis números, usa `autocomplete="one-time-code"`, informa erros e bloqueia o botão enquanto o código estiver incompleto ou em processamento.

## Testes automatizados

Há cobertura de domínio, credenciais, armazenamento sem texto puro, sucesso, remember, repetição, tentativas, expiração, revogação, falha de e-mail, novo desafio, retenção, rate limit e renderização Inertia.

## Casos de QA

Consulte `docs/qa/2026-07-22-segundo-fator-administrativo.md`.

## Como validar

1. Configure um transporte de e-mail funcional.
2. Execute as migrations.
3. Entre em `/admin/login` com um administrador confirmado.
4. Confirme que o painel não abre antes do código.
5. Use o código recebido e confira a auditoria em **Sistema e segurança**.

## Riscos e limitações

O e-mail é um segundo passo de posse, mas não é resistente a phishing e pode compartilhar o mesmo canal usado na recuperação de senha. TOTP ou WebAuthn são endurecimentos recomendados depois do MVP. A indisponibilidade do e-mail impede novos logins administrativos por decisão fail-closed.
