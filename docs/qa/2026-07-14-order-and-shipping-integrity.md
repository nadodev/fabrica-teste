# QA — Integridade de frete e ciclo do pedido

## Objetivo

Validar valores logisticos, revalidacao, transicoes, auditoria e recuperacao da inbox.

## Pre-condicoes

Migrations aplicadas, Melhor Envio ativo e scheduler configurado.

## Dados de teste

Produto de 1200 g, 25 x 10 x 40 cm; pedido pago; webhook processing antigo.

## Ambiente

Automacao local com SQLite; repetir concorrencia em homologacao MySQL/InnoDB.

## Casos de sucesso

- `QA-FRETE-01`: cadastrar, calcular e finalizar; esperado: medidas e preco atuais; obtido: automatizado; status: OK.
- `QA-STATUS-01`: Pago -> Em producao -> Enviado -> Entregue; esperado: passos permitidos e auditados; obtido: automatizado; status: OK.

## Casos de validacao

- `QA-FRETE-02`: peso zero; esperado: recusa; obtido: automatizado; status: OK.
- `QA-STATUS-02`: Aguardando -> Entregue; esperado: erro; obtido: automatizado; status: OK.

## Casos de autorizacao

- `QA-STATUS-03`: nao administrador chama rota; esperado: acesso negado; obtido: middleware existente; status: OK.

## Casos de falha

- `QA-FRETE-03`: servico desaparece; esperado: carrinho preservado; obtido: automatizado; status: OK.
- `QA-WEBHOOK-01`: processo morre apos claim; esperado: lease recupera; obtido: automatizado; status: OK.

## Casos de concorrencia

- `QA-STATUS-04`: duas mudancas simultaneas; esperado: lock serializa; obtido: lock implementado; status: PENDENTE MYSQL.

## Casos de idempotencia

- `QA-STATUS-05`: repetir chave; esperado: uma mudanca; obtido: middleware existente; status: OK.

## Casos de regressao

- `QA-REG-01`: webhook atualiza pagamento; esperado: data e itens preservados; obtido: automatizado; status: OK.
- `QA-REG-02`: frete gratis continua funcional; obtido: automatizado; status: OK.

## Casos responsivos

- `QA-UI-01`: novos campos em 360 px; esperado: sem corte; obtido: pendente navegador; status: PENDENTE MANUAL.

## Casos de acessibilidade

- `QA-A11Y-01`: teclado nos inputs; esperado: rotulos e foco; obtido: padrao existente; status: OK CODIGO.

## Evidencias

Resultados das verificacoes sao registrados no fechamento.

## Riscos conhecidos

Medidas padrao precisam ser conferidas; concorrencia definitiva depende de MySQL/InnoDB.
