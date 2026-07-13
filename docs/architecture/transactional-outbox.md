# Outbox transacional de pedidos

## Decisao

Notificacoes de pedido sao representadas por registros duraveis em `shared_outbox`, gravados na mesma transacao do pedido. O envio de e-mail acontece fora da transacao comercial.

## Componentes

- `OutboxStore`: porta usada pelo checkout para registrar o evento.
- `OutboxQueue`: porta de reivindicacao, conclusao e retentativa.
- `ProcessOrderOutbox`: caso de uso que processa mensagens `ordering.order_placed`.
- `OrderNotificationGateway`: porta substituivel para notificacoes.
- `MailOrderNotificationGateway`: adaptador atual de e-mail Laravel.

## Concorrencia e recuperacao

A mensagem muda de `pending` para `processing` sob bloqueio de banco. Em sucesso, muda para `processed`. Em falha, volta a `pending` com nova disponibilidade em cinco minutos. Registros presos em `processing` por mais de quinze minutos perdem a concessao e voltam para a fila.

## Operacao

O scheduler executa `outbox:process-orders --limit=50` a cada minuto com protecao contra sobreposicao. Producao deve manter `php artisan schedule:work` ou uma chamada cron equivalente ativa.

## Garantia

O desenho garante entrega pelo menos uma vez. O evento possui UUID deterministico por pedido, impedindo duas mensagens de criacao para o mesmo agregado. Adaptadores futuros devem usar uma chave idempotente no provedor sempre que disponivel.
