# Provedores de frete e endereco postal

## Contexto

O calculo de frete e o preenchimento de endereco dependem de servicos externos com falhas e contratos diferentes. Credenciais de frete nao podem chegar ao navegador.

## Decisao

O Melhor Envio continua encapsulado no cliente de infraestrutura existente, usando a URL base oficial de cada ambiente, `Authorization: Bearer` e `User-Agent` com contato. O token e lido de `config/services.php`, alimentado por `MELHOR_ENVIO_TOKEN`, e normalizado somente no servidor.

A busca postal passa pela porta `PostalAddressLookup`. O adaptador ViaCEP executa rede no servidor, retorna somente os campos publicos necessarios e usa cache de 24 horas. A interface chama apenas a rota local limitada por taxa.

## Consequencias

O frontend nao depende de CORS nem conhece provedores ou credenciais. O banco nao guarda mais o token e o painel recebe somente um indicador booleano de configuracao. Uma futura troca do ViaCEP exige somente outro adaptador. A renovacao OAuth do Melhor Envio continua sendo uma responsabilidade operacional separada.
