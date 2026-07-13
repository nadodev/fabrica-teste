# QA - Frete e endereco por CEP

## Objetivo

Validar cotacao de frete, seguranca do token, mascaras e preenchimento postal.

## Pre-condicoes

Migrations aplicadas, `MELHOR_ENVIO_TOKEN` configurado e ambiente correspondente selecionado.

## Dados de teste

CEPs `01001-000`, `89600-000` e `99999-999`; respostas HTTP simuladas.

## Ambiente

Automatizado local com SQLite e HTTP simulado; validacao real de producao depende de token valido.

## Casos de sucesso

- SHIP-01: cotacao usa `https://melhorenvio.com.br`, Bearer e User-Agent; obtido conforme esperado; OK.
- SHIP-02: CEP valido preenche logradouro, cidade e UF; obtido conforme esperado; OK.
- SHIP-03: token vem somente da configuracao, nao aparece no HTML/Inertia e nao existe coluna de token no banco; obtido conforme esperado; OK.
- SHIP-03A: frete gratis habilitado com minimo atingido; carrinho e checkout recebem opcao gratuita automaticamente; obtido conforme esperado; OK.

## Casos de validacao

- SHIP-04: CEP com menos ou mais de 8 digitos; servidor recusa; obtido conforme esperado; OK.
- SHIP-05: telefone fora de 10/11 digitos ou UF fora de 2 letras; checkout recusa; obtido conforme esperado; OK.
- SHIP-05A: ativar o Melhor Envio sem `MELHOR_ENVIO_TOKEN`; painel recusa e orienta configurar o servidor; obtido conforme esperado; OK.
- SHIP-05B: ativar sem CEP de origem; painel aponta especificamente o campo ausente; obtido conforme esperado; OK.
- SHIP-05C: token e CEP ausentes durante cotacao; adaptador retorna mensagens distintas e acionaveis; obtido conforme esperado; OK.
- SHIP-05D: executar `shipping:diagnose`; comando confirma os estados sem imprimir o token; obtido conforme esperado; OK.
- SHIP-05E: executar `shipping:diagnose --verify` com resposta autenticada; comando confirma acesso no ambiente de produção sem imprimir token ou dados da conta; obtido conforme esperado; OK.
- SHIP-05F: abrir checkout sem frete selecionado; servidor redireciona ao carrinho com erro; obtido conforme esperado; OK.
- SHIP-05G: enviar `deliveryMethod=pickup`; validacao recusa o novo pedido; obtido conforme esperado; OK.
- SHIP-05H: frete gratis habilitado abaixo do minimo; checkout continua bloqueado; obtido conforme esperado; OK.

## Casos de autorizacao

- SHIP-06: token invalido ou de outro ambiente; mensagem orienta gerar novo token; obtido conforme esperado; OK.
- SHIP-06A: API retorna 401 no diagnóstico remoto; comando falha, informa autenticação recusada e não expõe a credencial; obtido conforme esperado; OK.

## Casos de falha

- SHIP-07: CEP inexistente; rota retorna 404 e formulario continua manual; obtido conforme esperado; OK.
- SHIP-08: ViaCEP indisponivel; rota retorna 503 sem apagar dados digitados; implementado, teste manual pendente.

## Casos de concorrencia

Nao aplicavel a consultas somente leitura.

## Casos de idempotencia

- SHIP-09: consultas repetidas ao mesmo CEP usam cache por 24 horas; implementado.

## Casos de regressao

- SHIP-10: checkout aceita telefone, documento e CEP mascarados; coberto por teste de checkout.
- SHIP-10A: opcao de retirada nao aparece no checkout nem no painel de frete; obtido conforme esperado; OK.

## Casos responsivos

- SHIP-11: carrinho bloqueado, frete gratis e checkout somente com entrega permanecem responsivos em viewport movel; validacao visual concluida sem rolagem horizontal; OK.

## Casos de acessibilidade

- SHIP-12: mensagem de erro usa `alert`, progresso usa `status` e inputs informam teclado/autocomplete; revisao estrutural concluida.

## Evidencias

Suite completa com 89 testes e 557 assercoes; PHPStan, Pint, ESLint, TypeScript, Prettier e build aprovados. QA visual confirmou bloqueio sem frete, redirecionamento do checkout direto, frete gratis automatico, ausencia de retirada e console sem erros.

## Riscos conhecidos

Token real pode estar vencido ou pertencer ao ambiente incorreto. Renovacao OAuth automatica permanece fora do escopo.
