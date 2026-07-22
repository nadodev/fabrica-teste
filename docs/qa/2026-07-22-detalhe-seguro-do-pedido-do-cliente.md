# QA — Detalhe seguro do pedido do cliente

## Objetivo

Validar o acesso ao detalhe, o isolamento entre clientes e a orientação segura após pagamento recusado.

## Pré-condições

Aplicação migrada; cliente verificado com pedido vinculado; segundo cliente; pedido recusado com carrinho restaurado.

## Dados de teste

Pedido com item, frete, total e pagamento; UUID válido de outro proprietário; usuário não verificado.

## Ambiente

Testes automatizados com banco de teste. QA visual previsto no navegador local após as verificações automatizadas.

## Casos de sucesso

| ID | Cenário | Pré-condições | Passos | Resultado esperado | Resultado obtido | Status |
|---|---|---|---|---|---|---|
| SUC-01 | Proprietário abre o pedido | Cliente verificado e dono | Entrar, abrir conta e selecionar pedido | Exibir itens, totais, frete e pagamento | Coberto por teste de feature | OK |
| SUC-02 | Acesso pela listagem | Pedido listado | Selecionar número ou “Ver pedido” | Navegar para o detalhe correto | Implementado; validação automatizada de props | OK |

## Casos de validação

| ID | Cenário | Pré-condições | Passos | Resultado esperado | Resultado obtido | Status |
|---|---|---|---|---|---|---|
| VAL-01 | UUID inválido | Cliente autenticado | Informar identificador fora do formato | Rota não encontrada | Restrição `whereUuid` aplicada | OK |

## Casos de autorização

| ID | Cenário | Pré-condições | Passos | Resultado esperado | Resultado obtido | Status |
|---|---|---|---|---|---|---|
| AUT-01 | Outro cliente | Pedido de terceiro | Abrir URL autenticado como outro cliente | 404 sem dados | Coberto por teste de feature | OK |
| AUT-02 | Visitante | Sem sessão | Abrir detalhe | Redirecionar ao login | Coberto por teste de feature | OK |
| AUT-03 | E-mail não verificado | Sessão não verificada | Abrir detalhe | Redirecionar à verificação | Coberto por teste de feature | OK |

## Casos de falha

| ID | Cenário | Pré-condições | Passos | Resultado esperado | Resultado obtido | Status |
|---|---|---|---|---|---|---|
| FAL-01 | Pedido inexistente | Cliente autenticado | Abrir UUID inexistente | 404 uniforme | Mesmo retorno do adaptador para ausência e divergência | OK |

## Casos de concorrência

Não aplicável: operação somente leitura e sem transição de estado.

## Casos de idempotência

| ID | Cenário | Pré-condições | Passos | Resultado esperado | Resultado obtido | Status |
|---|---|---|---|---|---|---|
| IDE-01 | Atualizar a página | Pedido acessível | Repetir GET | Nenhuma escrita ou duplicidade | Endpoint somente leitura | OK |

## Casos de regressão

| ID | Cenário | Pré-condições | Passos | Resultado esperado | Resultado obtido | Status |
|---|---|---|---|---|---|---|
| REG-01 | Lista isolada | Pedidos de dois clientes e convidado | Abrir conta | Mostrar apenas pedidos vinculados | Teste existente permanece aprovado | OK |
| REG-02 | Pagamento recusado | Carrinho restaurado | Abrir pedido cancelado | Orientar retorno ao carrinho, sem reutilizar cobrança | Implementado na interface | OK |

## Casos responsivos

| ID | Cenário | Pré-condições | Passos | Resultado esperado | Resultado obtido | Status |
|---|---|---|---|---|---|---|
| RES-01 | Tela estreita | Largura móvel | Abrir detalhe | Colunas empilhadas, sem perda de conteúdo | Classes responsivas implementadas | OK |

## Casos de acessibilidade

| ID | Cenário | Pré-condições | Passos | Resultado esperado | Resultado obtido | Status |
|---|---|---|---|---|---|---|
| A11Y-01 | Alerta de recusa | Pedido recusado | Navegar com leitor de tela | Alerta anunciado e ação com texto explícito | `role=alert` e link textual implementados | OK |

## Evidências

- Teste focado: 4 testes e 45 asserções aprovados.
- Suíte completa após limpeza do cache local: 136 testes e 905 asserções aprovados.
- PHPStan, Pint, ESLint, TypeScript e Prettier aprovados.
- Build de produção aprovado com 2.311 módulos transformados.

## Riscos conhecidos

Pedidos convidados não podem ser reivindicados. O fluxo não tenta reusar uma cobrança recusada; ele exige novo checkout do carrinho restaurado. O bundle principal mantém o aviso preexistente por superar 500 kB.
