# ADR 0002: Dinheiro e identificadores no domínio

- Status: aceito
- Data: 2026-07-12

## Contexto

Valores monetários com ponto flutuante causam erros de precisão. IDs sequenciais expostos acoplam integrações ao banco e facilitam enumeração de recursos.

## Decisão

Representar dinheiro em centavos inteiros com moeda explícita e usar UUIDs nas entidades de domínio.

## Consequências

- Operações monetárias permanecem determinísticas.
- Conversão e formatação ficam nas bordas da aplicação.
- URLs e eventos usam identificadores estáveis e não sequenciais.

