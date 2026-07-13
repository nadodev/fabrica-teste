# QA - gateway de pagamento falso

## Objetivo

Validar o ciclo persistido de pagamento e seus efeitos em pedido e estoque.

## Pre-condicoes

Migrations aplicadas, gateway `fake`, scheduler disponivel e estoque positivo.

## Dados de teste

Pedidos PIX de R$ 100,00, produto com 3 unidades e reserva de 2.

## Ambiente

Laravel em `testing`, SQLite isolado e data 2026-07-13.

## Casos de sucesso

- PAY-001: `approved` resulta em pagamento/pedido pagos, fisico 1 e reservado 0. Obtido: OK.
- PAY-002: estorno total resulta em pagamento/pedido estornados. Obtido: OK.

## Casos de validacao

- PAY-003: valor, moeda, metodo e chave sao persistidos. Obtido: OK.

## Casos de autorizacao

- PAY-004: gateway falso em `production` e rejeitado pelo provider. Obtido: protegido em configuracao; nao ativado no ambiente local. Status: OK.

## Casos de falha

- PAY-005: `declined` cancela e libera reserva. Obtido: OK.
- PAY-006: `timeout` mantem pagamento pendente e reserva ativa. Obtido: OK.
- PAY-007: aprovacao com reserva vencida gera estorno compensatorio e cancelamento. Obtido: OK.

## Casos de concorrencia

- PAY-008: duas execucoes disputam a mesma intencao. Locks, estado e chaves unicas protegem o fluxo; teste paralelo MySQL/InnoDB pendente.

## Casos de idempotencia

- PAY-009: reprocessar pago nao cria tentativa/cobranca. Obtido: uma tentativa e uma transacao.
- PAY-010: retry de timeout nao cria segunda transacao falsa. Obtido: duas tentativas e uma transacao.
- PAY-011: estornar duas vezes nao duplica valor. Obtido: um estorno.

## Casos de regressao

- PAY-012: checkout ainda cria pedido, reserva, carrinho convertido e outbox atomicamente. Obtido: suite direcionada aprovada.

## Casos responsivos

- PAY-013: confirmacao reutiliza o card responsivo existente. Build de producao aprovado.

## Casos de acessibilidade

- PAY-014: mensagem de proxima etapa e textual e nao depende de cor. Obtido: OK.

## Evidencias

- Testes de Payment: 5 testes e 32 assercoes.
- Testes direcionados Payment, Checkout e Order: 11 testes e 58 assercoes antes da rodada final.
- Suite completa: 43 testes e 284 assercoes.
- PHPStan direcionado: zero erros depois da compensacao final.
- Pint, Prettier, ESLint direcionado e TypeScript: aprovados.
- Build de producao: aprovado com aviso conhecido de bundle JavaScript de aproximadamente 646 kB.

## Riscos conhecidos

- SQLite nao substitui prova concorrente em MySQL/InnoDB.
- Simulador nao representa disponibilidade ou contrato do Asaas.
- Webhook e reconciliacao ainda nao existem.
