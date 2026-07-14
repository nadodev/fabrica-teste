# Integridade de frete e ciclo do pedido

## Objetivo

Impedir que preco de frete em sessao, medidas genericas ou alteracoes administrativas invalidas produzam pedidos incorretos.

## Escopo

Peso e dimensoes no produto, cotacao real, revalidacao no checkout, maquina de estados administrativa, historico do operador, preservacao da data original e recuperacao de webhooks interrompidos.

## Fora do escopo

Compra de etiqueta, rastreamento, recuperacao de senha, paginacao e devolucao iniciada pelo painel.

## Regras de negocio

- Peso fica entre 1 e 30000 gramas; cada dimensao entre 1 e 200 centimetros.
- Preco e prazo usados no pedido sao os retornados na revalidacao.
- Servico indisponivel exige novo calculo e nao cria pedido.
- Administrador nao marca manualmente pedido como pago ou estornado.
- Atualizar pedido nao altera `created_at` nem recria itens.
- Webhook interrompido volta a pendente; ao atingir o limite fica em `failed`.

## Fluxo principal

O carrinho e carregado, os perfis logisticos compoem a consulta, o servico e consultado novamente e uma fingerprint confirma que o carrinho nao mudou antes da gravacao.

## Fluxos alternativos

API indisponivel preserva o carrinho. Frete gratis usa a politica interna. Transicao invalida volta com erro sem alterar o pedido.

## Casos de uso

`QuoteCartShipping`, `CheckoutCart` e `ChangeOrderStatus`.

## Arquitetura

Catalog fornece `ShippingProfile`; Shipping monta `ShippingQuoteRequest`; `ShippingQuoteGateway` isola o Melhor Envio. A transicao permanece no agregado `Order`.

## Portas e adaptadores

`ShippingQuoteGateway` e implementada por `MelhorEnvioClient`. `OrderStatusHistoryRecorder` e implementada pelo banco.

## Persistencia

Quatro colunas logisticas foram adicionadas ao produto. `ordering_order_status_history` guarda origem, destino, administrador, observacao e horario.

## Transacoes

A mudanca de status bloqueia o pedido e grava pedido e historico juntos. A chamada externa precede a transacao do checkout, que confirma a fingerprint.

## Idempotencia

Checkout continua unico por carrinho; rota administrativa usa chave de idempotencia; webhooks continuam unicos pelo ID do provedor.

## Seguranca

O servidor ignora valores de frete da sessao. Somente administrador autenticado muda status e sua identidade e auditada.

## Eventos

Eventos Asaas continuam na inbox; registros travados sao recuperados pelo processador agendado.

## Interface

Produto exibe peso e dimensoes. Seletores mostram somente transicoes permitidas. Detalhe mostra historico e Operacao mostra a saude da inbox.

## Testes automatizados

Payload dimensional, preco revalidado, persistencia historica, transicoes, auditoria e recuperacao/dead-letter.

## Casos de QA

Consulte `docs/qa/2026-07-14-order-and-shipping-integrity.md`.

## Como validar

Cadastrar medidas, calcular e finalizar. No admin, percorrer os estados permitidos e conferir o historico.

## Riscos e limitacoes

Produtos existentes recebem valores padrao e devem ser revisados. Concorrencia final deve ser repetida em MySQL/InnoDB.
