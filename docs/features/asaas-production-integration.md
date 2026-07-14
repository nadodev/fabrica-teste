# Integracao Asaas em producao

## Objetivo

Criar e acompanhar cobrancas Asaas sem marcar um pedido como pago antes da confirmacao financeira.

## Escopo

Clientes, PIX com QR Code e copia e cola, boleto, cartao, estorno total e parcial, chargeback, webhook autenticado, inbox idempotente, processamento imediato com retry e reconciliacao agendada.

## Fora do escopo

Execucao automatica de uma cobranca real durante testes locais. A primeira validacao financeira deve ser iniciada conscientemente no site publicado.

## Regras de negocio

- Documento e obrigatorio para pedidos pagos pelo Asaas.
- Criacao de cobranca resulta em `pending`; somente webhook confirma.
- PIX confirma em `PAYMENT_RECEIVED`; cartao e boleto podem confirmar em `PAYMENT_CONFIRMED`.
- Recusa, exclusao ou cancelamento de boleto liberam reserva e cancelam o pedido.
- Evento duplicado nao repete transicao.
- O evento autenticado e persistido antes de ser processado imediatamente; falha local mantem o evento pendente para retry.
- Reserva vencida apos recebimento provoca estorno compensatorio.
- Somente estornos com estado `DONE` entram no valor devolvido.
- Estorno parcial e cumulativo e nunca pode reduzir o valor ja registrado.
- Chargeback permanece visivel no pagamento e no snapshot do pedido.
- Checkout PIX cria a cobranca imediatamente, consulta `/payments/{id}/pixQrCode` e apresenta as instrucoes somente a sessao compradora ou ao dono autenticado.
- Falha antes de receber o ID do provedor devolve o pagamento para `pending`, permitindo retry seguro.
- Cartao exige nome impresso, numero, validade e codigo de seguranca validos, alem dos dados do titular e IP real do comprador.
- Numero completo e codigo de seguranca existem apenas durante a requisicao: nao entram no pedido, sessao, banco, outbox ou logs.
- Recusa HTTP 400 do cartao encerra o pagamento como recusado, cancela o pedido e libera a reserva sem inventar um ID de cobranca Asaas.
- Recusa de cartao recria um carrinho ativo com os mesmos itens, devolve o uso do cupom e retorna ao checkout; numero e CVV sao limpos e precisam ser informados novamente.
- O checkout valida a prontidao local do gateway antes de converter o carrinho. Integracao desabilitada ou chave invalida nao cria pedido, nao reserva estoque e preserva o carrinho.
- Chaves de producao salvas como `aact_prod_...` por perda do caractere especial sao normalizadas para `$aact_prod_...` apenas no servidor antes do envio.
- Falha transitoria persistida e exibida como indisponibilidade, sem manter a interface indefinidamente no estado "Gerando pagamento".

## Fluxo principal

No checkout pago, `EnsurePaymentGatewayReady` valida a configuracao e, somente depois, o pedido e criado. A cobranca e criada imediatamente. PIX retorna QR Code e copia e cola. Cartao abre os campos somente quando selecionado e envia os dados transitorios diretamente ao Asaas junto com titular e IP. O outbox permanece como fallback, consulta por `externalReference` antes de criar e nao duplica uma cobranca ja vinculada. O endpoint `/webhooks/asaas` autentica, reduz, grava e tenta aplicar imediatamente o evento especifico. A cada minuto, o scheduler retenta eventos pendentes e, a cada 15 minutos, pagamentos abertos sao consultados no Asaas para recuperar webhooks perdidos.

## Fluxos alternativos

Recusa de cartao mantem o token de sessao, arquiva o carrinho convertido ligado ao pedido cancelado e cria outro carrinho ativo para a nova tentativa. Timeout permanece retryable. Eventos informativos sao auditados sem alterar pedido. Estorno total atualiza pagamento e pedido; estorno parcial preserva o pedido pago e registra o valor devolvido; chargeback registra o estado financeiro sem alterar indevidamente a expedicao.

## Casos de uso

`EnsurePaymentGatewayReady`, `ProcessPayment`, `ReceiveAsaasWebhook`, `ProcessAsaasWebhooks::handleEvent` e `ReconcileAsaasPayments`. `ProcessPayment` recebe `CreditCardData` somente na chamada sincrona do checkout.

## Arquitetura

`AsaasPaymentGateway` implementa as portas de cobranca e consulta. `PaymentWebhookInbox` separa recepcao HTTP de processamento financeiro e tambem recebe snapshots da reconciliacao.

## Portas e adaptadores

`PaymentGatewayReadiness` isola a validacao de configuracao. HTTP do Laravel para Asaas; persistencia SQL para clientes e inbox; portas de Ordering, Cart e Inventory para efeitos locais. `CouponGateway::release` devolve atomicamente o uso consumido pela tentativa recusada.

## Persistencia

`payment_provider_customers` evita clientes duplicados, `payment_webhook_events` guarda eventos saneados e seus estados, e `payment_payments.refunded_amount` guarda o acumulado devolvido em centavos.

## Transacoes

Rede ocorre fora da transacao. Na recusa, pedido, pagamento, estoque, cupom e recuperacao do carrinho mudam juntos depois do lock.

## Idempotencia

Referencia externa do pedido, ID do evento, claim atomico, fingerprint da reconciliacao e transicoes monotonicamente crescentes impedem repeticoes ou regressao do valor estornado.

## Seguranca

Chave e token ficam no ambiente. Webhook usa `asaas-access-token`, comparacao constante, limite de requisicoes e exclusao CSRF apenas na rota dedicada. HTTPS e obrigatorio para cartao. Numero e CVV sao excluidos do flash de validacao, limpos no navegador apos recusa, marcados como sensiveis e nunca persistidos ou encadeados em excecoes HTTP.

## Eventos

Somente eventos de cobrancas selecionados no Asaas sao aceitos e novos campos desconhecidos sao descartados.

## Interface

A URL publica configurada e `https://fabricafardamento.gejalabs.com.br/webhooks/asaas`. A pagina de confirmacao exibe QR Code, copia e cola, vencimento e atualiza o estado automaticamente. Quando existe falha persistida, mostra a indisponibilidade e oculta o bloco enganoso de geracao. Cartao recusado volta diretamente ao formulario, preserva dados nao sensiveis e solicita novo numero e CVV.

## Testes automatizados

Contrato HTTP simulado, trava de producao, token invalido, duplicacao, confirmacao de estoque/pedido, estorno parcial, chargeback e reconciliacao sem acesso real.

## Casos de QA

Consulte `docs/qa/2026-07-13-asaas-production-integration.md` e `docs/qa/2026-07-13-asaas-refund-synchronization.md`.

## Como validar

Publicar, configurar `PAYMENT_GATEWAY=asaas`, `ASAAS_BASE_URL=https://api.asaas.com/v3`, `ASAAS_LIVE_ENABLED=true` e a chave integral de producao entre aspas simples, limpar/recriar o cache de configuracao, manter o scheduler ativo e validar uma primeira cobranca controlada em HTTPS.

## Riscos e limitacoes

Sem sandbox, a validacao final movimentara dinheiro real. Pedidos antigos com falha antes do ID Asaas precisam de `payments:process` depois da correcao da configuracao. Eventos aplicados imediatamente reduzem a dependencia operacional, mas retries e webhooks perdidos ainda dependem do scheduler ativo. A validacao de concorrencia ainda precisa ocorrer em MySQL/InnoDB no ambiente publicado.
