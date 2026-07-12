# Roadmap para e-commerce operacional

## Estado de prontidão

| Capacidade | Estado | Necessário para MVP |
| --- | --- | --- |
| Arquitetura modular | Implementada | Sim |
| Catálogo público | Parcial | Sim |
| Idempotência | Fundação implementada | Sim |
| Estoque e reservas | Fundação implementada | Sim |
| Carrinho persistido | Não implementado | Sim |
| Checkout | Não implementado | Sim |
| Pedidos | Não implementado | Sim |
| Pagamentos | Apenas porta | Sim |
| Frete | Apenas porta | Sim |
| Clientes e endereços | Não implementado | Sim |
| Painel administrativo | Não implementado | Sim |
| Autenticação/autorização | Não implementado | Sim |
| Webhooks e reconciliação | Não implementado | Sim |
| Observabilidade operacional | Não implementado | Sim |
| LGPD e políticas comerciais | Não implementado | Sim |

## Fase 1 — núcleo de venda

### Catálogo

- Categorias, variantes, tamanho, cor, imagens e slug.
- Preço vigente, preço promocional e histórico de alteração.
- Produtos simples e personalizados com regras distintas.
- Busca, filtros, paginação e cache com invalidação.
- Área administrativa protegida por policies.

### Estoque

- Saldo por SKU/variante e, futuramente, por depósito.
- Entrada, ajuste, reserva, confirmação e liberação.
- Job idempotente para expirar reservas.
- Alertas de estoque baixo e reconciliação física.
- Testes concorrentes em MySQL/InnoDB.

### Carrinho

- Carrinho anônimo com token seguro e migração para cliente autenticado.
- Itens referenciando variante, quantidade e opções de personalização.
- Reprecificação no checkout; nunca confiar em preço enviado pelo browser.
- Expiração, merge e persistência server-side.

### Checkout e pedidos

- Cliente, contato, endereço, entrega, itens e totais validados no servidor.
- Snapshot imutável de nome, SKU, preço e endereço no pedido.
- Máquina de estados: `pending`, `awaiting_payment`, `paid`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`.
- Número público de pedido separado do UUID interno.
- Idempotência obrigatória e outbox transacional.

## Fase 2 — integrações financeiras e logísticas

### Pagamentos

- Adaptador sandbox inicial e gateway falso determinístico para testes.
- Registro de tentativas, transações, falhas, captura e estorno.
- Webhook com assinatura sobre corpo bruto, timestamp e prevenção de replay.
- Reconciliação agendada entre gateway e estado local.
- Nunca armazenar número completo de cartão ou CVV; utilizar tokenização do provedor.

### Frete

- Endereço normalizado e validação de CEP.
- Peso e dimensões por variante.
- Cotação por múltiplos adaptadores com timeout e fallback.
- Snapshot da opção escolhida no pedido.
- Rastreio e atualização assíncrona de expedição.

## Fase 3 — operação e experiência

- Cadastro, login, recuperação, verificação de e-mail e gestão de endereços.
- Papéis administrativos e autorização de menor privilégio.
- Cupons, promoções e regras de uso concorrente.
- E-mails transacionais e templates versionados.
- Cancelamento, devolução, troca e reembolso.
- Nota fiscal conforme operação e localidade.
- Dashboard de pedidos, estoque, pagamentos e falhas.
- Busca e SEO: metadata, sitemap, canonical e dados estruturados.
- Acessibilidade e testes em dispositivos móveis.

## Fase 4 — produção e governança

- Redis para sessão, cache, locks e rate limit.
- Workers separados por criticidade e scheduler supervisionado.
- Logs estruturados com correlation ID, sem dados sensíveis.
- Métricas de conversão, erro de pagamento, overselling, fila e latência.
- Alertas, runbooks, backups e simulação de restauração.
- Gestão de segredos, rotação de chaves e ambientes separados.
- Termos, privacidade, cookies, retenção e atendimento a titulares conforme LGPD.
- Teste de carga, DAST e revisão independente antes do lançamento.

## Ordem de implementação recomendada

1. Variantes de catálogo e saldo por SKU.
2. Carrinho persistido server-side.
3. Agregado Pedido e checkout transacional com outbox.
4. Gateway falso e fluxo de pagamento completo.
5. Primeiro adaptador real e webhooks.
6. Frete e endereços.
7. Área administrativa e operação.
8. Observabilidade, carga, segurança e lançamento controlado.

## Definition of Done de uma capacidade comercial

- Regras no domínio e contratos públicos documentados.
- Entrada valida payload e autorização.
- Operação crítica é idempotente.
- Concorrência e transação foram consideradas explicitamente.
- Logs não expõem segredo ou dado pessoal desnecessário.
- Testes cobrem sucesso, falha, retry e limites.
- Métricas e procedimento de recuperação existem.
- Migration possui rollback e deploy compatível.

