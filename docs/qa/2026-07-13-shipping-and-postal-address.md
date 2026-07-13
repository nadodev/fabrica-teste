# QA - Frete e endereco por CEP

## Objetivo

Validar cotacao de frete, seguranca do token, mascaras e preenchimento postal.

## Pre-condicoes

Migrations aplicadas, Melhor Envio ativo e token correspondente ao ambiente.

## Dados de teste

CEPs `01001-000`, `89600-000` e `99999-999`; respostas HTTP simuladas.

## Ambiente

Automatizado local com SQLite e HTTP simulado; validacao real de producao depende de token valido.

## Casos de sucesso

- SHIP-01: cotacao usa `https://melhorenvio.com.br`, Bearer e User-Agent; obtido conforme esperado; OK.
- SHIP-02: CEP valido preenche logradouro, cidade e UF; obtido conforme esperado; OK.
- SHIP-03: token fica criptografado e nao aparece no HTML/Inertia do admin; obtido conforme esperado; OK.

## Casos de validacao

- SHIP-04: CEP com menos ou mais de 8 digitos; servidor recusa; obtido conforme esperado; OK.
- SHIP-05: telefone fora de 10/11 digitos ou UF fora de 2 letras; checkout recusa; obtido conforme esperado; OK.

## Casos de autorizacao

- SHIP-06: token invalido ou de outro ambiente; mensagem orienta gerar novo token; obtido conforme esperado; OK.

## Casos de falha

- SHIP-07: CEP inexistente; rota retorna 404 e formulario continua manual; obtido conforme esperado; OK.
- SHIP-08: ViaCEP indisponivel; rota retorna 503 sem apagar dados digitados; implementado, teste manual pendente.

## Casos de concorrencia

Nao aplicavel a consultas somente leitura.

## Casos de idempotencia

- SHIP-09: consultas repetidas ao mesmo CEP usam cache por 24 horas; implementado.

## Casos de regressao

- SHIP-10: checkout aceita telefone, documento e CEP mascarados; coberto por teste de checkout.

## Casos responsivos

- SHIP-11: mascaras e mensagens permanecem dentro dos campos em celular e desktop; revisao estrutural concluida, validacao visual publicada pendente.

## Casos de acessibilidade

- SHIP-12: mensagem de erro usa `alert`, progresso usa `status` e inputs informam teclado/autocomplete; revisao estrutural concluida.

## Evidencias

Testes de feature, verificacoes estaticas e build registrados na entrega.

## Riscos conhecidos

Token real pode estar vencido ou pertencer ao ambiente incorreto. Renovacao OAuth automatica permanece fora do escopo.
