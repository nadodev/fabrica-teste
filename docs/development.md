# Guia de desenvolvimento

## Requisitos

- PHP 8.3 ou superior
- Composer 2
- Node.js 22
- MySQL 8 ou SQLite

## Instalação

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

Para desenvolvimento:

```bash
composer dev
```

## Qualidade

```bash
php artisan test
composer lint:check
npm run types:check
npm run lint:check
npm run format:check
```

O projeto possui testes em três níveis:

- `tests/Unit`: domínio e value objects sem framework.
- `tests/Feature`: adaptadores, banco e páginas Inertia.
- `tests/Architecture`: limites de dependência entre camadas.

## Dados demonstrativos

```bash
php artisan migrate:fresh --seed
```

O `CatalogSeeder` cria produtos demonstrativos idempotentes. Em produção, seeds demonstrativos devem ser executados apenas de forma intencional.

## Adicionando um gateway

1. Implemente `PaymentGateway` em `Payment/Infrastructure`.
2. Converta DTOs internos para o formato do fornecedor dentro do adaptador.
3. Registre a implementação em um service provider usando configuração.
4. Não exponha SDKs ou tipos do fornecedor ao domínio e à aplicação.
5. Cubra o adaptador com testes de contrato.

O mesmo fluxo se aplica a `ShippingQuoteGateway` e `StockGateway`.

## Idempotência

Endpoints mutáveis críticos devem usar os middlewares de idempotência e rate limit:

```php
Route::post('/checkout', CheckoutController::class)
    ->middleware(['throttle:commerce', 'idempotent']);
```

O cliente deve gerar uma chave nova por intenção comercial e repetir a mesma chave somente ao reenviar a mesma operação:

```http
Idempotency-Key: 0190f566-c399-79e3-a553-7e5fb8d83419
```

Reutilizar a chave com outro payload retorna `409 Conflict`. Respostas `5xx` liberam a chave para retry; respostas concluídas ficam disponíveis para replay por 24 horas.

## Concorrência de estoque

O adaptador de estoque utiliza transações e `lockForUpdate`. Testes rápidos rodam em SQLite, mas a garantia de concorrência deve ser validada no mesmo MySQL/InnoDB usado em produção:

1. cadastrar uma unidade disponível;
2. disparar duas reservas simultâneas com chaves diferentes;
3. confirmar que apenas uma reserva foi aceita;
4. confirmar `reserved <= on_hand` e ausência de saldo negativo;
5. repetir após deadlock/retry.

Não faça chamadas de gateway durante a transação do banco.

## Commits

O histórico segue Conventional Commits:

```text
feat(catalog): add product creation use case
fix(inventory): prevent duplicate stock reservation
test(payment): cover declined transaction
docs(architecture): record checkout orchestration decision
```

Cada commit deve representar uma etapa coesa, manter os testes verdes e evitar misturar refatorações não relacionadas.

## Deploy na Hostinger

Configure o domínio com PHP 8.3 ou superior e execute:

```bash
/opt/alt/php83/usr/bin/php /usr/local/bin/composer2 install --no-dev --optimize-autoloader --no-interaction
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan optimize
```

Compile os assets antes do envio ou no ambiente que possuir Node.js. O diretório público do domínio deve apontar para `public/`.
