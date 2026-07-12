# Arquitetura de capacidade

## Premissas iniciais

Esta é uma arquitetura evolutiva para início de operação, não uma previsão de tráfego. As metas devem ser recalibradas com métricas reais.

| Métrica | Capacidade inicial planejada |
| --- | ---: |
| Produtos ativos | 10 mil |
| Visitantes simultâneos | 200 |
| Leituras de catálogo | 50 req/s sustentadas |
| Checkouts | 5 req/s sustentadas |
| Pedidos | 20 mil/mês |
| Webhooks de pagamento | 10 req/s em rajada |
| Disponibilidade alvo | 99,9% mensal |
| p95 catálogo | < 300 ms |
| p95 comando local de checkout | < 800 ms, sem contar redirecionamento externo |
| RPO | 15 minutos |
| RTO | 2 horas |

## Topologia inicial

```text
Browser
  │ HTTPS + CDN/cache de assets
  ▼
Nginx/LiteSpeed
  │
  ▼
Laravel/Inertia ───── Redis (cache, sessão, rate limit)
  │       │
  │       └────────── Workers de fila
  ▼                         │
MySQL primário ◄────────────┘
  │
  └── backup + restauração testada

Adaptadores externos: Pagamento, Frete, E-mail e Storage
```

## Estratégia por componente

### Aplicação

- Processos stateless; sessão e cache fora do filesystem local.
- Escala horizontal atrás de load balancer quando CPU ou latência saturarem.
- Assets versionados em CDN.
- Healthcheck superficial em `/up` e healthchecks profundos separados para operação.

### Banco

- MySQL com InnoDB, modo strict, UTC e índices guiados por queries.
- Transações curtas; chamadas externas nunca permanecem dentro de transações.
- Lock pessimista apenas em agregados de alta contenção, especialmente saldo de estoque.
- Pool de conexões dimensionado abaixo do limite do banco.
- Réplica de leitura somente quando métricas mostrarem necessidade; checkout lê do primário.

### Cache e filas

- Redis para cache, rate limit, locks distribuídos e sessão.
- Filas separadas: `critical`, `payments`, `default`, `notifications`.
- Webhooks persistem rapidamente e delegam processamento idempotente à fila.
- Retry com backoff exponencial e jitter; falhas permanentes seguem para dead letter.

### Integrações

- Timeout, retry limitado e circuit breaker por fornecedor.
- Chaves idempotentes propagadas quando o fornecedor suporta.
- Credenciais e endpoints selecionados por configuração.
- Métricas por adaptador permitem comparar latência e taxa de erro.

## Particionamento lógico

- Catálogo pode ser cacheado agressivamente e tolera consistência eventual em busca.
- Estoque, pedidos e pagamentos exigem leitura do primário e consistência forte local.
- E-mail, indexação, analytics e notificações são assíncronos.
- Nenhuma resposta de pagamento depende de e-mail ou analytics.

## Sinais para escalar

- CPU da aplicação acima de 70% de forma sustentada.
- p95 acima da meta por 15 minutos.
- Uso de conexões do banco acima de 70%.
- Fila crítica com idade do job superior a 30 segundos.
- Cache hit ratio de catálogo abaixo de 80%.
- Lock wait/deadlocks crescendo após normalização por volume.

## Testes de capacidade

1. Baseline de catálogo com banco frio e cache quente.
2. Checkout com chaves idempotentes repetidas.
3. Corrida de múltiplas reservas pela última unidade.
4. Rajada de webhooks duplicados e fora de ordem.
5. Indisponibilidade simulada de gateway e transportadora.
6. Drenagem e recuperação de filas após interrupção.

