# Módulo de identidade e credenciais

## Contexto

Cadastro, confirmação de e-mail e recuperação de senha pertencem à mesma capacidade de identidade, mas dependem de Eloquent, notificações, sessão e do broker de senhas do Laravel.

## Decisão

Os fluxos foram isolados em `app/Modules/Identity`. Application define casos de uso, DTOs, resultados explícitos e portas. Infrastructure adapta `User`, notifications e `Password`. Presentation mantém apenas validação, autorização HTTP, sessão e respostas Inertia.

O modelo `User` implementa `MustVerifyEmail` e seleciona notificações em português. As rotas da conta são protegidas por `auth` e `verified`; a tela de confirmação permanece disponível apenas com `auth`.

## Consequências

- O broker Laravel continua responsável por hashing, armazenamento, expiração e consumo do token de senha.
- A Application não depende de facades, Eloquent, Request ou Inertia.
- Uma futura troca de provedor de identidade exige novos adaptadores, sem reescrever os casos de uso.
- O login e a sessão continuam como adaptadores HTTP existentes; autenticação em dois fatores permanece como decisão futura.

## Segurança

A confirmação exige sessão, assinatura válida, validade temporal, identificador correspondente e hash do e-mail. A recuperação usa resposta indistinguível para contas existentes e inexistentes. O frontend recebe apenas id, nome, e-mail, instante de confirmação e indicador administrativo.

## Alternativas rejeitadas

Colocar toda a lógica diretamente nos Controllers foi rejeitado por acoplar regra, infraestrutura e HTTP. Adotar um pacote completo de autenticação também foi evitado para não substituir o layout e os fluxos existentes nem adicionar dependência sem necessidade.
