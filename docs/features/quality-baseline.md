# Base de qualidade automatizada

## Objetivo

Restabelecer todas as verificacoes obrigatorias do repositorio sem esconder erros ou reduzir regras.

## Escopo

PHPStan, Pint, ESLint, Prettier, TypeScript, testes e build; correcoes de tipagem, normalizacao de respostas externas e escopo das ferramentas.

## Fora do escopo

Otimizacao do tamanho do bundle, testes de carga e validacao concorrente em MySQL/InnoDB.

## Regras de negocio

Nenhuma regra financeira ou comercial foi alterada. Dados externos de catalogo e frete passam a ser normalizados antes de entrar nos tipos de dominio.

## Fluxo principal

As ferramentas inspecionam somente o repositorio raiz, executam sem suppressions e falham diante de erro real.

## Fluxos alternativos

O gitlink `fardamentos-loja` possui suas proprias dependencias e configuracoes, portanto e excluido das ferramentas do repositorio pai.

## Casos de uso

Nao aplicavel; foram corrigidos contratos e adaptadores existentes.

## Arquitetura

Tipos de retorno foram tornados explicitos, Controllers deixaram de depender de propriedades dinamicas incertas e adaptadores validam arrays externos.

## Portas e adaptadores

Sem portas novas nesta etapa.

## Persistencia

Sem alteracao de schema nesta etapa de qualidade.

## Transacoes

Sem alteracao.

## Idempotencia

Sem alteracao.

## Seguranca

Nenhum ignore, baseline ou reducao de nivel foi adicionado. Payloads externos malformados sao descartados com seguranca.

## Eventos

Sem alteracao.

## Interface

Somente formatacao automatica; layout e comportamento visual preservados.

## Testes automatizados

56 testes e 374 assercoes aprovados.

## Casos de QA

Consulte `docs/qa/2026-07-13-quality-baseline.md`.

## Como validar

Executar PHPStan, Pint, ESLint, Prettier, TypeScript, testes e build conforme `AGENTS.md`.

## Riscos e limitacoes

O build ainda alerta que o chunk JavaScript principal supera 500 kB; e uma melhoria de desempenho, nao uma falha de compilacao.
