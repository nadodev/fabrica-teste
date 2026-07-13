# QA - estoque por SKU e variacao

Data: 2026-07-13

## Dados locais antes da migracao

- 4 produtos.
- 1 produto com 2 variacoes.
- 46 unidades fisicas: 20 de produto simples e 26 distribuidas entre variacoes.
- Total agregado e total das variacoes eram iguais.
- 2 reservas antigas vencidas, sem saldo ainda reservado.

## Resultado da migracao local

- 5 niveis de estoque criados.
- 46 unidades fisicas preservadas.
- Zero unidades reservadas.
- Zero reservas ativas; 2 reservas antigas classificadas como expiradas.
- Tabela agregada antiga removida.
- Nenhuma variacao possui `stock`, `lowStockThreshold`, `purchasable` ou `lowStock` no JSON.

## Cenarios automatizados

- Recebimento e reserva permanecem idempotentes.
- Nao e possivel reservar mais do que o saldo da variacao.
- Duas variacoes mantem saldos e reservas independentes.
- O total do produto e a soma dos niveis disponiveis.
- Criacao de produto salva metadados no catalogo e saldo somente no Inventory.
- Checkout reserva a variacao selecionada.
- Suite completa: 34 testes, 229 assercoes.

## Migration reversivel

Executado em banco SQLite isolado:

1. Todas as migrations aplicadas.
2. Migration de estoque revertida com sucesso.
3. Migration de estoque reaplicada com sucesso.

## Validacao visual

- Catalogo publico carregou os quatro produtos.
- Detalhe do jaleco exibiu as duas variacoes como disponiveis.
- Formulario administrativo exibiu SKU, estoque e alerta independentes para cada variacao.
- Painel de estoque exibiu cinco niveis, incluindo 10 e 16 unidades nos dois SKUs do jaleco.
- Nenhum erro foi registrado no console do navegador.
- Usuario administrativo temporario de QA removido ao final.

## Verificacoes de qualidade

- PHPStan na area alterada: zero erros.
- Pint: aprovado.
- TypeScript: aprovado.
- ESLint direcionado aos arquivos alterados: aprovado.
- Build de producao: aprovado, com aviso ja conhecido de bundle JavaScript de aproximadamente 646 kB.
