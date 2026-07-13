# QA — Perfil e endereços do cliente

## Objetivo

Validar manutenção segura dos dados do cliente e seu preenchimento no checkout.

## Pré-condições

Migrations aplicadas, usuário cliente ativo e ao menos um produto disponível.

## Dados de teste

Cliente `cliente@example.com`; CEP `01001-000`; endereços “Casa” e “Trabalho”.

## Ambiente

Aplicação local Laravel/Inertia com banco configurado e navegador desktop.

## Casos de sucesso

### CPF-01 — Atualizar perfil

- Cenário: cliente salva nome, telefone e documento.
- Pré-condições: cliente autenticado.
- Passos: abrir Minha conta, preencher os dados e salvar.
- Resultado esperado: mensagem de sucesso e dados persistidos.
- Resultado obtido: coberto por teste automatizado e validação local.
- Status: OK.

### CPF-02 — Cadastrar e selecionar endereço

- Cenário: cliente cadastra endereço de entrega padrão.
- Pré-condições: cliente autenticado.
- Passos: cadastrar “Casa”, adicionar produto e abrir checkout.
- Resultado esperado: “Casa” selecionada e campos preenchidos.
- Resultado obtido: coberto por teste automatizado e validação local.
- Status: OK.

### CPF-03 — Usar outro endereço

- Cenário: cliente não quer usar um endereço salvo.
- Pré-condições: checkout com endereço salvo.
- Passos: selecionar “Preencher outro endereço”.
- Resultado esperado: campos de endereço ficam vazios e editáveis.
- Resultado obtido: validado na interface.
- Status: OK.

## Casos de validação

### CPF-04 — CEP e UF inválidos

- Cenário: submissão com CEP curto e UF extensa.
- Pré-condições: cliente autenticado.
- Passos: enviar os valores inválidos.
- Resultado esperado: erros nos campos e nenhum endereço criado.
- Resultado obtido: coberto por teste automatizado.
- Status: OK.

## Casos de autorização

### CPF-05 — Endereço de outro cliente

- Cenário: cliente tenta editar ou excluir UUID alheio.
- Pré-condições: dois clientes e um endereço do primeiro.
- Passos: chamar alteração e exclusão autenticado como o segundo.
- Resultado esperado: 404 e endereço preservado.
- Resultado obtido: coberto por teste automatizado.
- Status: OK.

### CPF-06 — Visitante

- Cenário: visitante tenta cadastrar endereço.
- Pré-condições: sessão sem autenticação.
- Passos: enviar o formulário.
- Resultado esperado: redirecionamento para login.
- Resultado obtido: coberto por teste automatizado.
- Status: OK.

## Casos de falha

### CPF-07 — CEP externo indisponível

- Cenário: consulta automática de CEP falha.
- Pré-condições: serviço de CEP indisponível.
- Passos: informar CEP completo.
- Resultado esperado: formulário permanece editável para preenchimento manual.
- Resultado obtido: tratamento existente preservado.
- Status: OK.

## Casos de concorrência

### CPF-08 — Alterações simultâneas de padrão

- Cenário: duas gravações disputam o padrão da mesma conta.
- Pré-condições: banco transacional.
- Passos: executar gravações concorrentes.
- Resultado esperado: bloqueio por usuário serializa as operações e o último comando válido define o padrão.
- Resultado obtido: regra implementada; não executado com concorrência real nesta rodada.
- Status: Pendente em MySQL/InnoDB.

## Casos de idempotência

### CPF-09 — Repetir edição

- Cenário: o mesmo conteúdo de perfil/endereço é salvo novamente.
- Pré-condições: registro existente.
- Passos: repetir a edição.
- Resultado esperado: estado final igual, sem duplicação na edição.
- Resultado obtido: comportamento determinístico do caso de uso.
- Status: OK.

## Casos de regressão

### CPF-10 — Pedidos em Minha conta

- Cenário: nova área de dados não remove pedidos do cliente.
- Pré-condições: cliente com pedido.
- Passos: abrir Minha conta.
- Resultado esperado: pedidos próprios continuam listados.
- Resultado obtido: suíte de propriedade de pedidos aprovada.
- Status: OK.

## Casos responsivos

### CPF-11 — Tela estreita

- Cenário: formulários e cartões em viewport móvel.
- Pré-condições: largura aproximada de 390 px.
- Passos: abrir Minha conta e checkout.
- Resultado esperado: campos empilhados, sem corte horizontal.
- Resultado obtido: validado visualmente.
- Status: OK.

## Casos de acessibilidade

### CPF-12 — Rótulos e seleção por teclado

- Cenário: navegação sem mouse.
- Pré-condições: formulário visível.
- Passos: percorrer controles com Tab e selecionar endereço.
- Resultado esperado: rótulos acessíveis, foco visível e radios operáveis.
- Resultado obtido: estrutura semântica conferida na interface.
- Status: OK.

## Evidências

Testes de feature, verificação estática, build e inspeção visual registrados no relatório da implementação.

## Riscos conhecidos

Validação fiscal externa e teste concorrente real em MySQL/InnoDB permanecem fora desta rodada.
