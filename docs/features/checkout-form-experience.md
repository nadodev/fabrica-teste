# Experiência do formulário de checkout

## Objetivo

Tornar a finalização do carrinho mais clara, rápida e confiável sem alterar as regras financeiras do checkout.

## Escopo

Organização visual em dados, entrega e pagamento; resumo antecipado no celular; escolhas visuais de entrega, finalidade e pagamento; mensagens de erro; preenchimento assistido; estados de processamento; apresentação segura do cartão.

## Fora do escopo

Alterações de preço, frete, estoque, cupom, estados de pedido, contrato com o Asaas ou validações definitivas do servidor.

## Regras de negócio

- Quando existe uma forma de pagamento habilitada, compra é a opção inicial; orçamento permanece disponível sem cobrança.
- Apenas formas habilitadas pela administração aparecem.
- O botão final descreve a ação real: Pix, cartão, boleto ou orçamento.
- Número e código de segurança do cartão continuam transitórios e são limpos após recusa conforme o fluxo existente.
- Valores e totais exibidos continuam vindo do servidor.

## Fluxo principal

O cliente confere o resumo, informa seus dados, escolhe entrega, seleciona compra ou orçamento e, em compra, escolhe a forma de pagamento. Cartão revela seus campos somente quando selecionado.

## Fluxos alternativos

Sem formas de pagamento, orçamento é selecionado. Erros do servidor aparecem em um resumo e junto ao campo correspondente. Retirada continua solicitando os dados necessários ao pedido.

## Casos de uso

Nenhum caso de uso foi alterado. A interface continua submetendo ao checkout transacional existente.

## Arquitetura

A composição permanece na página Inertia `checkout.tsx`; regras e valores autoritativos continuam no backend.

## Portas e adaptadores

Sem alteração. Asaas, frete, catálogo, carrinho e persistência usam as portas existentes.

## Persistência

Sem alteração.

## Transações

Sem alteração; a transação do checkout permanece responsável pela consistência do pedido.

## Idempotência

A submissão continua enviando uma chave de idempotência por tentativa.

## Segurança

Campos de cartão mantêm `autocomplete` semântico, não são persistidos no cliente e continuam fora do flash do servidor. Erros não expõem dados sensíveis.

## Eventos

Sem alteração.

## Interface

Três blocos numerados, cartões selecionáveis com estado visível, resumo com quantidade e miniatura, instruções contextuais, foco reforçado e botão final específico.

## Testes automatizados

São usados os testes existentes do checkout e pagamentos, além de TypeScript, ESLint, Prettier e build.

## Casos de QA

Consulte `docs/qa/2026-07-13-checkout-form-experience.md`.

## Como validar

Adicionar um item, abrir `/checkout`, alternar compra/orçamento e Pix/cartão/boleto, confirmar os textos e verificar o formulário em desktop e celular.

## Riscos e limitações

Não existe suíte automatizada de componentes React no projeto. A responsividade e as interações foram verificadas estruturalmente e no navegador local.
