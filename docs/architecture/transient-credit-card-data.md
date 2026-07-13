# Dados transitorios de cartao

## Contexto

O checkout transparente do Asaas precisa receber o cartao, os dados do titular e o IP do comprador na criacao da cobranca. Numero completo e codigo de seguranca nao podem virar estado persistente da loja.

## Decisao

`CheckoutRequest` valida os campos condicionais. O controller cria `CreditCardData` e o entrega diretamente a `ProcessPayment` na chamada sincrona. `PaymentRequest` transporta esse DTO apenas ate `AsaasPaymentGateway`. O agregado do pedido, os repositorios, as instrucoes de pagamento e a outbox nunca recebem esses campos.

O adaptador consulta primeiro `externalReference`. Assim, depois de um timeout incerto, uma repeticao pode recuperar uma cobranca que o Asaas ja criou sem reenviar o cartao. Uma recusa HTTP 400 vira `PaymentCardDeclined`; a aplicacao cancela o pedido e libera a reserva sem persistir identificador ficticio.

## Seguranca

- HTTPS e requisito de producao.
- `cardNumber` e `cardCcv` estao na lista global `dontFlash`.
- Parametros que transportam o DTO usam `SensitiveParameter`.
- Numero e CVV nao sao registrados, persistidos nem enviados a jobs.
- Excecoes de recusa sao saneadas e nao mantem a excecao HTTP anterior.
- O IP enviado e o IP do request do comprador; a configuracao de proxy confiavel do servidor deve preservar esse valor.

## Consequencias

O primeiro processamento de cartao e sincrono. A fila continua adequada para recuperar uma cobranca possivelmente criada antes de um timeout, mas nao tem acesso a dados para iniciar uma nova tentativa com outro cartao. Uma recusa definitiva exige um novo checkout.
