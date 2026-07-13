# Composição da interface de checkout

## Contexto

O checkout reunia todos os campos em um único painel longo, escondia pagamento em um seletor e posicionava o resumo somente depois do formulário no celular.

## Decisão

Manter uma única submissão transacional, mas dividir visualmente a página em três seções sem criar estado de etapas ou navegação intermediária. O resumo usa ordenação responsiva: aparece primeiro em telas pequenas e permanece fixo na lateral em telas grandes.

As opções de entrega, finalidade e pagamento usam radios nativos ocultos apenas visualmente. Assim, teclado, leitor de tela e semântica de formulário permanecem disponíveis. Componentes locais `SectionHeading` e `PaymentOption` reduzem repetição sem criar uma camada compartilhada prematura.

## Limites arquiteturais

O React controla apenas apresentação e escolha. Preço, frete, desconto, estoque, validação e autorização continuam autoritativos no servidor. O DTO transitório de cartão e o caso de uso do checkout não foram alterados.

## Consequências

A interface fica mais escaneável e a ação final mais previsível. A página continua sendo um formulário único, evitando pedidos parciais e preservando idempotência, tratamento de recusa e recuperação do carrinho.
