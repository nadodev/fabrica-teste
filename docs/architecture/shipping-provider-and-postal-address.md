# Provedores de frete e endereco postal

## Contexto

O calculo de frete e o preenchimento de endereco dependem de servicos externos com falhas e contratos diferentes. Credenciais de frete nao podem chegar ao navegador.

## Decisao

O Melhor Envio continua encapsulado no cliente de infraestrutura existente, usando a URL base oficial de cada ambiente, `Authorization: Bearer` e `User-Agent` com contato. O token e lido de `config/services.php`, alimentado por `MELHOR_ENVIO_TOKEN`, e normalizado somente no servidor.

A busca postal passa pela porta `PostalAddressLookup`. O adaptador ViaCEP executa rede no servidor, retorna somente os campos publicos necessarios e usa cache de 24 horas. A interface chama apenas a rota local limitada por taxa.

A elegibilidade do frete gratis e resolvida por `ResolveFreeShipping`, que depende da porta `ShippingSettingsRepository`. O valor minimo configurado em reais e convertido para centavos sem `float`. `ShowCart` usa a politica para apresentar a entrega gratuita e `CheckoutCart` recalcula a mesma regra antes de criar o pedido. Se a regra nao for aplicavel, uma cotacao selecionada na sessao e obrigatoria. Novos checkouts aceitam apenas `shipping`; valores historicos de retirada continuam legiveis nos pedidos antigos.

## Consequencias

O frontend nao depende de CORS nem conhece provedores ou credenciais. O banco nao guarda mais o token e o painel recebe somente um indicador booleano de configuracao. Uma futura troca do ViaCEP exige somente outro adaptador. A renovacao OAuth do Melhor Envio continua sendo uma responsabilidade operacional separada.

O diagnostico operacional e feito por `shipping:diagnose`. Ele le a mesma configuracao e o mesmo registro usados pelo adaptador, mas retorna somente estados booleanos e nunca o token. A opção `--verify` usa o mesmo cliente HTTP para consultar a identidade no domínio correspondente ao ambiente, descartando o corpo de sucesso e registrando somente estado HTTP e ambiente nas falhas. Falta de token e falta de CEP produzem mensagens distintas; o painel impede ativacao sem qualquer um deles.

O bloqueio existe em tres niveis: o carrinho nao renderiza o link ativo, a abertura direta do checkout redireciona para o carrinho e o caso de uso transacional recusa pedido sem frete. Assim, alterar o HTML ou chamar a rota diretamente nao contorna a regra.
