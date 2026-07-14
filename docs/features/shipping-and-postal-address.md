# Frete e preenchimento de endereco por CEP

## Objetivo

Calcular frete pelo Melhor Envio em producao e reduzir erros de digitacao no carrinho e checkout.

## Escopo

Correcao da URL e dos cabecalhos do Melhor Envio, token exclusivo no ambiente do servidor, mascara de CEP, telefone e CPF/CNPJ, e preenchimento de logradouro, cidade e UF pelo ViaCEP.

## Fora do escopo

Compra e impressao de etiquetas, renovacao OAuth automatica e persistencia de bairro no pedido.

## Regras de negocio

- CEP deve conter exatamente 8 digitos.
- Telefone brasileiro deve conter 10 ou 11 digitos.
- O endereco retornado e uma ajuda de preenchimento; o cliente pode corrigi-lo antes de finalizar.
- CEP inexistente nao inventa endereco e indisponibilidade externa nao impede preenchimento manual.
- Token de sandbox nao pode ser usado em producao e vice-versa.
- A credencial e lida somente de `MELHOR_ENVIO_TOKEN`; requisicoes administrativas nao recebem nem persistem o token.
- Linhas duplicadas no `.env` devem ser removidas: uma definicao vazia posterior pode sobrescrever o token valido.
- `php artisan shipping:diagnose` informa token, ativacao, ambiente e CEP apenas como estado, sem exibir a credencial. Com `--verify`, consulta `/api/v2/me` no ambiente selecionado e confirma se a API aceita a autenticação.
- O frete nao pode ser ativado enquanto a variavel de ambiente estiver vazia.
- Novos pedidos aceitam somente entrega por frete; retirada na loja nao faz parte do checkout.
- Sem uma cotacao selecionada, o carrinho nao libera a finalizacao e o acesso direto ao checkout volta ao carrinho.
- Frete gratis e aplicado automaticamente quando estiver habilitado e o valor dos produtos, depois do desconto, atingir o minimo configurado em reais.
- Peso e dimensoes do produto embalado sao enviados por item ao provedor.
- O servico escolhido e consultado novamente no checkout; preco e prazo da sessao nao sao fonte definitiva.

## Fluxo principal

No carrinho, a loja envia origem, destino e produtos ao endpoint oficial do Melhor Envio. O cliente seleciona uma cotacao antes de abrir o checkout. Quando o frete gratis configurado for aplicavel, a politica interna cria automaticamente a opcao de custo zero. No checkout, ao completar 8 digitos de CEP, o navegador chama uma rota local; a aplicacao consulta o ViaCEP, guarda o resultado em cache por 24 horas e preenche os campos existentes.

## Fluxos alternativos

Resposta 401/403 orienta a gerar e salvar um novo token no ambiente escolhido. CEP ausente retorna 404. Timeout do ViaCEP retorna 503 e mantem o formulario editavel.

## Casos de uso

`MelhorEnvioClient::quote`, `LookupPostalAddress` e `ResolveFreeShipping`.

## Arquitetura

`MelhorEnvioClient` le a credencial de `config/services.php`. `PostalAddressLookup` isola o provedor ViaCEP. A politica `ResolveFreeShipping` depende de uma porta de configuracao, mantendo banco e regras comerciais separados.

## Portas e adaptadores

Portas `PostalAddressLookup` e `ShippingSettingsRepository`; adaptadores HTTP para ViaCEP e Melhor Envio e adaptador de banco para configuracoes de frete.

## Persistencia

Nao ha persistencia de endereco consultado. O cache expira em 24 horas. A coluna antiga de token foi removida de `shipping_settings`; o segredo existe somente no ambiente do servidor.

## Transacoes

A consulta externa ocorre antes da transacao de escrita. Uma fingerprint do carrinho e confirmada dentro da transacao; se itens, quantidades, variacoes ou precos mudarem, a compra exige novo calculo.

## Idempotencia

Consultas sao somente leitura. O cache reduz repeticoes ao ViaCEP.

## Seguranca

O token nunca e enviado ao navegador ou banco. `MELHOR_ENVIO_TOKEN` aceita o valor com ou sem prefixo `Bearer`. A rota de CEP valida entrada e possui rate limit.

## Eventos

Nao aplicavel.

## Interface

O painel informa apenas se `MELHOR_ENVIO_TOKEN` esta configurado. O valor minimo do frete gratis e informado em reais. A opcao de retirada foi removida. CEP usa mascara `00000-000`, telefone usa mascara brasileira e CPF/CNPJ alterna conforme a quantidade de digitos. O checkout informa busca, sucesso e erro e move o foco para o numero apos preencher o endereco.

## Testes automatizados

Contrato do endpoint de producao, Authorization, erro 401, token via configuracao, ausencia da coluna antiga, nao exposicao no admin, ViaCEP encontrado e inexistente.

## Casos de QA

Consulte `docs/qa/2026-07-13-shipping-and-postal-address.md`.

## Como validar

Definir uma unica vez `MELHOR_ENVIO_TOKEN` no `.env`, executar `php artisan optimize:clear`, selecionar o ambiente correspondente no painel e salvar o CEP de origem. Executar `php artisan shipping:diagnose --verify` antes de calcular um frete com CEP valido.

## Riscos e limitacoes

Tokens OAuth do Melhor Envio expiram e precisam ser renovados. O ViaCEP pode limitar uso excessivo; por isso existe cache e limite de requisicoes.
