# QA — Experiência do formulário de checkout

## Objetivo

Validar clareza, interação, responsividade, acessibilidade e regressão do checkout reorganizado.

## Pré-condições

Produto ativo no carrinho e ao menos uma forma de pagamento habilitada.

## Dados de teste

Carrinho local com um produto de R$ 99,90; Pix, cartão e boleto habilitados.

## Ambiente

Aplicação local em `uniform-crafted.test`, Chrome integrado e banco SQLite local.

## Casos de sucesso

- `UX-01` — Abrir checkout com pagamento habilitado; adicionar produto e acessar `/checkout`; compra e Pix aparecem selecionados, resumo mostra item e total; obtido conforme esperado; **OK**.
- `UX-02` — Selecionar cartão; clicar em Cartão; campos de titular, número, validade e CVV aparecem e botão muda para “Pagar com cartão”; obtido conforme esperado; **OK**.
- `UX-03` — Selecionar orçamento; clicar em Solicitar orçamento; meios de pagamento são ocultos e a interface informa que não haverá cobrança; verificação estrutural concluída; **OK**.

## Casos de validação

- `UX-04` — Submeter dados inválidos; servidor deve manter erros por campo e a página deve mostrar resumo de pendências; coberto pela estrutura existente e revisão do DOM; **OK**.

## Casos de autorização

- `UX-05` — Checkout público; usar sessão anônima com carrinho próprio; nenhuma informação de outro cliente deve aparecer; fluxo existente preservado; **OK**.

## Casos de falha

- `UX-06` — Recusa de cartão; manter dados não sensíveis, limpar número/CVV e retornar ao formulário; coberto pelos testes de pagamento existentes; **OK**.

## Casos de concorrência

- `UX-07` — Duas submissões simultâneas; middleware e caso de uso devem preservar idempotência; nenhuma regra foi alterada; cobertura existente preservada; **OK**.

## Casos de idempotência

- `UX-08` — Repetir tentativa com a mesma chave; não criar pedido duplicado; cobertura automatizada existente; **OK**.

## Casos de regressão

- `UX-09` — Executar suíte completa; 72 testes e 452 asserções aprovados; **OK**.

## Casos responsivos

- `UX-10` — Tela pequena; resumo deve preceder o formulário e opções devem ocupar uma coluna; regras responsivas revisadas no CSS gerado; **OK**.
- `UX-11` — Desktop; formulário e resumo devem ocupar duas colunas e o resumo deve permanecer visível ao rolar; validado no navegador local; **OK**.

## Casos de acessibilidade

- `UX-12` — Navegação semântica; headings em ordem, `fieldset` para pagamento, radios e checkbox com nomes acessíveis e alertas com `role=alert`; DOM acessível inspecionado; **OK**.

## Evidências

Snapshot semântico do navegador, captura visual desktop, 72 testes com 452 asserções, PHPStan sem erros, Pint, TypeScript, ESLint, Prettier e build aprovados.

## Riscos conhecidos

O projeto ainda não possui testes automatizados de componentes React nem teste manual com leitor de tela real.
