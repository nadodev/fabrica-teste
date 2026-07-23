# Implantação Docker na VPS

## Objetivo

Executar a loja em produção na VPS com containers reproduzíveis, HTTPS automático e dados persistentes.

## Escopo

PHP-FPM, Nginx, Caddy, MySQL, Redis, worker, scheduler, migration isolada, healthchecks, volumes, configuração de produção, backup e roteiro de implantação.

## Fora do escopo

Provisionar a VPS sem acesso SSH, alterar DNS sem acesso ao provedor, contratar serviço de e-mail e transferir dados da instalação anterior.

## Regras de negócio

- Somente Caddy publica portas no host.
- MySQL e Redis permanecem em rede interna.
- A aplicação só inicia depois da migration concluída.
- Worker e scheduler usam a mesma imagem e o mesmo armazenamento da aplicação.
- Segredos existem apenas em `.env.production`, nunca na imagem ou no Git.
- Todo deploy começa com backup de banco e arquivos.

## Fluxo principal

O servidor clona `nadodev/fabrica-teste`, cria `.env.production`, aponta o DNS para a VPS e executa `deploy/deploy.sh`. O Compose constrói as imagens, valida dependências, migra o banco e Caddy emite o certificado TLS.

## Fluxos alternativos

Se a migration falhar, aplicação, worker e scheduler não sobem. Se o DNS ainda não apontar para a VPS, Caddy mantém a tentativa de certificado registrada em log. O rollback usa o commit anterior e preserva os volumes.

## Casos de uso

Implantação operacional, backup pré-deploy e inicialização dos processos contínuos.

## Arquitetura

Caddy → Nginx → PHP-FPM. Aplicação, worker e scheduler acessam MySQL e Redis pela rede interna `backend`.

## Portas e adaptadores

Portas públicas 80/443; FastCGI interno em 9000; MySQL e Redis sem publicação no host.

## Persistência

Volumes `mysql_data`, `redis_data`, `app_storage`, `caddy_data` e `caddy_config`.

## Transações

Mantidas pelo MySQL/InnoDB. O backup usa snapshot lógico com `--single-transaction`.

## Idempotência

Compose e migrations Laravel podem ser repetidos. O script usa `git pull --ff-only` para impedir merges inesperados no servidor.

## Segurança

HTTPS obrigatório, cookies seguros, debug desativado, headers de transporte, banco e cache internos, segredos fora do Git e extensões PHP mínimas.

## Eventos

Worker processa filas Redis e scheduler executa outbox, expiração de estoque, webhooks e reconciliação.

## Interface

Sem alteração visual.

## Testes automatizados

A composição é validada com `docker compose config`. A suíte Laravel e o build continuam obrigatórios antes do deploy.

## Casos de QA

Consulte `docs/qa/2026-07-22-implantacao-docker-na-vps.md`.

## Como validar

1. Copiar `.env.production.example` para `.env.production` e preencher todos os segredos.
2. Executar `docker compose --env-file .env.production -f compose.production.yml config`.
3. Executar `sh deploy/deploy.sh`.
4. Conferir `docker compose ... ps`, `/up`, checkout, fila, scheduler e webhooks.

## Riscos e limitações

O build frontend usa `npm install` porque o repositório não possui lockfile JavaScript na raiz. A implantação real depende de acesso SSH, DNS e hostname definitivo.

