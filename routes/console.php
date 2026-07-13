<?php

use App\Modules\Inventory\Application\Command\ExpireStockReservations;
use App\Modules\Ordering\Application\Command\ProcessOrderOutbox;
use App\Modules\Payment\Application\Command\ProcessAsaasWebhooks;
use App\Modules\Payment\Application\Command\ProcessPaymentOutbox;
use App\Modules\Payment\Application\Command\ReconcileAsaasPayments;
use App\Modules\Payment\Application\Command\RecoverStuckPayment;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('shipping:diagnose', function (): int {
    $tokenConfigured = trim((string) config('services.melhor_envio.token')) !== '';
    $settings = DB::table('shipping_settings')->where('id', 1)->first();
    $enabled = (bool) ($settings->is_enabled ?? false);
    $environment = (string) ($settings->environment ?? 'nao configurado');
    $originConfigured = preg_replace('/\D+/', '', (string) ($settings->origin_zip ?? '')) !== '';

    $this->line('Cache de configuracao: '.(app()->configurationIsCached() ? 'ATIVO' : 'INATIVO'));
    $this->line('MELHOR_ENVIO_TOKEN: '.($tokenConfigured ? 'CONFIGURADO' : 'AUSENTE'));
    $this->line('Configuracao de frete: '.($settings === null ? 'AUSENTE' : 'ENCONTRADA'));
    $this->line('Melhor Envio ativo: '.($enabled ? 'SIM' : 'NAO'));
    $this->line('Ambiente: '.$environment);
    $this->line('CEP de origem: '.($originConfigured ? 'CONFIGURADO' : 'AUSENTE'));

    if (! $tokenConfigured || $settings === null || ! $enabled || ! $originConfigured) {
        $this->error('A integracao de frete ainda nao esta pronta. Corrija os itens marcados como AUSENTE ou NAO.');

        return Command::FAILURE;
    }

    $this->info('Configuracao local do Melhor Envio pronta para cotacao.');

    return Command::SUCCESS;
})->purpose('Check Melhor Envio readiness without exposing its token');

Artisan::command('outbox:process-orders {--limit=50}', function (ProcessOrderOutbox $processor): void {
    $processed = $processor->handle((int) $this->option('limit'));
    $this->info("{$processed} notificacao(oes) de pedido processada(s).");
})->purpose('Process pending order notifications from the transactional outbox');

Schedule::command('outbox:process-orders --limit=50')->everyMinute()->withoutOverlapping();

Artisan::command('inventory:expire-reservations {--limit=100}', function (ExpireStockReservations $expirer): void {
    $expired = $expirer->handle((int) $this->option('limit'));
    $this->info("{$expired} reserva(s) de estoque expirada(s).");
})->purpose('Expire due stock reservations and release their reserved balance');

Schedule::command('inventory:expire-reservations --limit=100')->everyMinute()->withoutOverlapping();

Artisan::command('payments:process {--limit=50}', function (ProcessPaymentOutbox $processor): void {
    $processed = $processor->handle((int) $this->option('limit'));
    $this->info("{$processed} pagamento(s) processado(s).");
})->purpose('Process payment intents from the transactional outbox');

Schedule::command('payments:process --limit=50')->everyMinute()->withoutOverlapping();

Artisan::command('payments:process-asaas-webhooks {--limit=100}', function (ProcessAsaasWebhooks $processor): void {
    $processed = $processor->handle((int) $this->option('limit'));
    $this->info("{$processed} evento(s) do Asaas processado(s).");
})->purpose('Process authenticated Asaas payment webhook events');

Schedule::command('payments:process-asaas-webhooks --limit=100')->everyMinute()->withoutOverlapping();

Artisan::command('payments:reconcile-asaas {--limit=100}', function (ReconcileAsaasPayments $reconciler): void {
    $processed = $reconciler->handle((int) $this->option('limit'));
    $this->info("{$processed} pagamento(s) do Asaas reconciliado(s).");
})->purpose('Reconcile local payments with the current Asaas payment state');

Schedule::command('payments:reconcile-asaas --limit=100')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('payments:recover {order}', function (RecoverStuckPayment $recover): void {
    $order = $this->argument('order');
    if (! is_string($order) || $order === '') {
        $this->error('Informe um numero de pedido valido.');

        return;
    }
    $recovered = $recover->handle($order);
    $recovered
        ? $this->info('Pagamento devolvido para a fila com seguranca.')
        : $this->warn('Pedido inexistente ou pagamento nao esta preso antes do envio ao Asaas.');
})->purpose('Recover a payment stuck before receiving its provider transaction ID');
