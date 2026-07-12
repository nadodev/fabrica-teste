<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('site_banners')->exists()) {
            return;
        }

        $now = now();

        DB::table('site_banners')->insert([
            [
                'id' => (string) Str::uuid(),
                'eyebrow' => 'Destaque da loja',
                'title' => 'Uniformes profissionais para sua equipe',
                'subtitle' => 'Compre fardamentos com acabamento reforcado, estoque organizado e atendimento para empresas em todo o Brasil.',
                'button_label' => 'Ver produtos',
                'link_url' => '/produtos',
                'image_url' => null,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'eyebrow' => 'Compra corporativa',
                'title' => 'Monte o pedido de uniformes da empresa',
                'subtitle' => 'Escolha produtos, variacoes e quantidades direto pelo carrinho, com checkout sem pagamento online por enquanto.',
                'button_label' => 'Comprar agora',
                'link_url' => '/produtos',
                'image_url' => null,
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('site_banners')
            ->whereIn('title', [
                'Uniformes profissionais para sua equipe',
                'Monte o pedido de uniformes da empresa',
            ])
            ->delete();
    }
};
