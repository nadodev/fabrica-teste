# Escopo das ferramentas de qualidade

## Decisao

O repositorio pai ignora `fardamentos-loja/**` em Pint, ESLint e Prettier porque o caminho e um gitlink para outro repositorio completo, com dependencias, builds e configuracoes proprias.

## Motivo

Inspecionar artefatos compilados, `vendor` e fontes do repositorio filho produzia milhares de falsos erros e misturava duas unidades de entrega. O codigo do repositorio raiz continua integralmente verificado.

## Garantias

Nao foram adicionados ignores de regras, erros ou arquivos do projeto raiz. PHPStan permanece sem baseline e todas as verificacoes obrigatorias passam.
