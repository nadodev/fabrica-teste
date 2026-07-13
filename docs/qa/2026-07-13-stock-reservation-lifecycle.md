# QA - ciclo de vida das reservas de estoque

## Objetivo

Validar confirmacao, liberacao, expiracao, idempotencia e auditoria das reservas.

## Pre-condicoes

- Migrations aplicadas.
- Produto com nivel de estoque positivo.
- Scheduler configurado no ambiente.

## Dados de teste

- Produto simples `STOCK-001`.
- Saldos entre 2 e 5 unidades.
- Reservas UUID distintas com 1 a 4 unidades.

## Ambiente

- Data: 2026-07-13.
- Banco automatizado: SQLite isolado por teste.
- Banco local: SQLite do projeto.

## Casos de sucesso

### RES-001 - confirmar reserva ativa

- Pre-condicao: 5 fisicas e 4 reservadas.
- Passos: confirmar a reserva.
- Esperado: 1 fisica, zero reservada e status `confirmed`.
- Obtido: conforme esperado no teste automatizado.
- Status: OK.

### RES-002 - liberar reserva ativa

- Pre-condicao: reserva ativa.
- Passos: liberar duas vezes.
- Esperado: saldo reservado liberado uma vez.
- Obtido: conforme esperado no teste automatizado.
- Status: OK.

## Casos de validacao

### RES-003 - limite do lote

- Pre-condicao: duas reservas vencidas.
- Passos: executar lotes com limite 1.
- Esperado: uma reserva por chamada e zero na terceira chamada.
- Obtido: 1, 1 e 0.
- Status: OK.

## Casos de autorizacao

### RES-004 - ausencia de endpoint publico

- Pre-condicao: aplicacao carregada.
- Passos: revisar rotas HTTP.
- Esperado: nenhuma rota publica de confirmacao ou liberacao.
- Obtido: casos de uso disponiveis apenas internamente.
- Status: OK.

## Casos de falha

### RES-005 - confirmar reserva vencida

- Pre-condicao: reserva ativa com validade no passado.
- Passos: tentar confirmar.
- Esperado: reserva expirada, saldo liberado e conflito retornado.
- Obtido: conforme esperado no teste automatizado.
- Status: OK.

### RES-006 - saldo inconsistente

- Pre-condicao: corrupcao entre reserva e nivel.
- Passos: confirmar ou liberar.
- Esperado: transacao falha sem corrigir silenciosamente.
- Obtido: protegido por invariantes no adaptador; teste destrutivo nao executado no banco local.
- Status: OK por inspecao e cobertura da regra.

## Casos de concorrencia

### RES-007 - confirmacao contra expiracao

- Pre-condicao: mesma reserva disputada por dois processos.
- Passos: executar em paralelo.
- Esperado: somente uma transicao terminal.
- Obtido: locks e revalidacao cobertos em SQLite; execucao paralela real em MySQL/InnoDB pendente.
- Status: PENDENTE MYSQL.

## Casos de idempotencia

### RES-008 - confirmar duas vezes

- Pre-condicao: reserva ativa.
- Passos: confirmar duas vezes.
- Esperado: um consumo e uma movimentacao de confirmacao.
- Obtido: conforme esperado, uma movimentacao.
- Status: OK.

### RES-009 - expurgar novamente

- Pre-condicao: reservas ja expiradas.
- Passos: repetir o expurgador.
- Esperado: zero alteracoes.
- Obtido: zero na repeticao.
- Status: OK.

## Casos de regressao

### RES-010 - disponibilidade por variacao

- Pre-condicao: niveis por SKU existentes.
- Passos: executar a suite de Inventory.
- Esperado: saldos independentes preservados.
- Obtido: 8 testes e 41 assercoes aprovados antes da validacao final.
- Status: OK.

## Casos responsivos

### RES-011 - historico administrativo

- Pre-condicao: painel de estoque aberto em viewport pequeno.
- Passos: conferir a linha de movimentacao.
- Esperado: informacoes fisica e reservada permanecem legiveis.
- Obtido: painel real exibiu `Fisico 20`, `Reservado 1 (+1)` e `Reservado 0 (-1)` sem alterar a grade existente.
- Status: OK.

## Casos de acessibilidade

### RES-012 - leitura textual dos saldos

- Pre-condicao: movimentacao de reserva existente.
- Passos: navegar pelo conteudo textual.
- Esperado: deltas e saldos nao dependem apenas de cor.
- Obtido: valores possuem rotulos `Fisico` e `Reservado`.
- Status: OK.

## Evidencias

- Suite direcionada: 8 testes, 41 assercoes.
- Suite completa: 38 testes, 246 assercoes.
- Migration `2026_07_13_020000_add_reservation_audit_to_inventory_movements` aplicada localmente.
- `schedule:list` exibiu outbox e expiracao a cada minuto.
- QA visual no painel administrativo confirmou os deltas reservado e fisico; usuario e dados temporarios foram removidos.

## Riscos conhecidos

- Concorrencia real ainda precisa ser validada em MySQL/InnoDB.
- Confirmacao por aprovacao e liberacao por cancelamento dependem dos proximos casos de uso de pagamento e pedido.
