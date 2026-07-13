<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class AdminOperationsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/operations', [
            'checks' => [
                ['label' => 'Rate limit catalogo', 'value' => '120 req/min por IP'],
                ['label' => 'Rate limit comercio', 'value' => '30 req/min por usuario/IP'],
                ['label' => 'Rate limit login', 'value' => '5 req/min por email/IP'],
                ['label' => 'Ambiente', 'value' => app()->environment()],
                ['label' => 'Mailer', 'value' => config('mail.default')],
            ],
            'logs' => $this->recentLogs(),
            'backups' => collect(Storage::disk('local')->files('backups'))
                ->sortDesc()
                ->take(10)
                ->values()
                ->all(),
        ]);
    }

    public function backup(): RedirectResponse
    {
        $database = database_path('database.sqlite');

        if (! File::exists($database)) {
            return back()->withErrors(['backup' => 'Backup automatico disponivel apenas para SQLite local.']);
        }

        $name = 'backups/database-'.now()->format('Ymd-His').'.sqlite';
        Storage::disk('local')->put($name, File::get($database));

        return back()->with('success', 'Backup criado em storage/app/'.$name);
    }

    /** @return list<string> */
    private function recentLogs(): array
    {
        $log = storage_path('logs/laravel.log');

        if (! File::exists($log)) {
            return [];
        }

        $lines = collect(explode("\n", File::get($log)))
            ->filter()
            ->take(-80)
            ->values()
            ->all();

        return array_values($lines);
    }
}
