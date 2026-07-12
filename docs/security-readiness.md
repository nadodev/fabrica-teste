# Auditoria de segurança e prontidão para e-commerce

- Data: 2026-07-12
- Escopo: código da aplicação, configuração versionada e arquitetura
- Estado: fundação em desenvolvimento

## Resumo executivo

O sistema atual é uma vitrine de catálogo, não um e-commerce operacional. O Catálogo possui separação arquitetural e leitura persistida, mas carrinho, estoque, pedidos, pagamentos e frete ainda não formam fluxos transacionais. A superfície atual é majoritariamente de leitura; os maiores riscos surgirão na introdução do checkout e do painel administrativo.

## Controles existentes

- Laravel aplica CSRF ao grupo `web`.
- Sessões usam cookie `HttpOnly`, `SameSite=Lax` e serialização JSON.
- Senhas usam cast `hashed` e custo configurável.
- Queries do catálogo usam Eloquent com parâmetros vinculados.
- IDs públicos de produtos são UUIDs e a rota valida o formato.
- React escapa conteúdo textual por padrão.
- Domínio não depende de Laravel ou Eloquent, protegido por teste arquitetural.
- Valores monetários usam centavos inteiros.
- Mass assignment do produto é limitado por `$fillable`.

## Lacunas priorizadas

### P0 — bloqueiam operação comercial

1. **Pedidos e cobranças não idempotentes:** retries do navegador, proxy ou gateway podem criar operações duplicadas.
2. **Estoque sem controle concorrente:** duas compras simultâneas podem vender a última unidade.
3. **Ausência de agregado de Pedido:** não existe snapshot imutável de itens, preços, cliente, endereço e totais.
4. **Ausência de persistência de Pagamento:** transações, tentativas, estornos e eventos do gateway não são auditáveis.
5. **Webhooks não implementados:** faltam assinatura, prevenção de replay, idempotência e reconciliação.

### P1 — necessários antes de administração pública

1. Autenticação, recuperação de senha e verificação de e-mail.
2. Autorização por policies/permissões para catálogo, estoque, pedidos e reembolsos.
3. Rate limiting específico para login, checkout, cupom, frete e webhooks.
4. Trilha de auditoria para ações administrativas e financeiras.
5. Validação por Form Requests e DTOs; controllers devem rejeitar campos desconhecidos.
6. Cabeçalhos CSP, HSTS, `X-Content-Type-Options`, política de referrer e permissões.
7. Upload seguro de imagens: MIME real, tamanho, dimensões, nomes aleatórios e storage isolado.

### P2 — confiabilidade e operação

1. Outbox transacional para eventos e jobs.
2. Dead-letter/retry controlado e jobs idempotentes.
3. Observabilidade de checkout, pagamentos, estoque e filas.
4. Backups testados e procedimento de restauração.
5. Política LGPD: minimização, retenção, exportação e anonimização.
6. Gestão de segredos fora do Git e rotação de chaves.

## Concorrência e consistência

### Estoque

- Saldo é alterado por um ledger imutável de movimentações.
- Reserva ocorre dentro de transação com lock pessimista na linha de saldo.
- Constraint impede duas reservas com a mesma chave externa.
- `available = on_hand - reserved`; nenhuma operação pode produzir valor negativo.
- Reservas possuem expiração e liberação idempotente.

### Checkout e pedido

- Cliente envia `Idempotency-Key` em operações mutáveis críticas.
- A chave é única por escopo e ator, com hash do payload.
- Repetição do mesmo payload devolve a resposta anterior.
- Reutilização da chave com payload diferente retorna conflito.
- Criação do pedido, reserva e evento de saída devem compartilhar uma transação local.

### Pagamento

- A chave interna do pedido é enviada como referência idempotente ao gateway.
- Status local é atualizado por máquina de estados, nunca por atribuição livre.
- Webhook é a fonte de confirmação assíncrona; retorno do navegador não confirma pagamento.
- Eventos do gateway têm identificador único e assinatura validada sobre o corpo bruto.

## Configuração segura de produção

```dotenv
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true
```

- Servir exclusivamente por HTTPS.
- Apontar o document root para `public/`.
- Não expor `.env`, `vendor`, storage privado ou arquivos de build.
- Usar usuário de banco exclusivo com privilégios mínimos.
- Executar filas sob supervisor e scheduler a cada minuto.
- Não executar seed demonstrativo em produção.

## Critérios mínimos para lançamento

- Fluxo pedido–estoque–pagamento idempotente e testado sob concorrência.
- Gateway em sandbox e reconciliação de webhooks.
- Painel protegido por autenticação, autorização e MFA quando disponível.
- Testes de checkout, falhas, retries, estorno e expiração de reservas.
- TLS, headers, logs, alertas, backups e restauração validados.
- Política de privacidade, termos, atendimento e regras de cancelamento publicados.

