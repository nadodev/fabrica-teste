<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 80);
            $table->string('description')->default('');
            $table->string('image_url')->nullable();
            $table->string('link_url')->default('/produtos');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_banners', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('eyebrow', 80)->default('Oferta da loja');
            $table->string('title', 160);
            $table->string('subtitle')->default('');
            $table->string('button_label', 80)->default('Ver produto');
            $table->string('link_url')->default('/produtos');
            $table->string('image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_stores', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 40)->default('Loja');
            $table->string('city', 120);
            $table->string('address');
            $table->string('phone', 120)->default('');
            $table->string('hours', 160)->default('');
            $table->string('map_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_history_sections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('eyebrow', 80)->default('Nossa historia');
            $table->string('title', 160);
            $table->text('body');
            $table->string('mission')->default('');
            $table->string('vision')->default('');
            $table->string('values')->default('');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('site_stores')->insert([
            ['id' => (string) Str::uuid(), 'type' => 'Matriz', 'city' => 'Pernambuco', 'address' => 'Av. Doutor Julio Maranhao, 7, Guararapes, Jaboatao dos Guararapes - PE', 'phone' => '(81) 97910-6667 / (81) 3074-2933', 'hours' => 'Segunda a Sexta, 7h as 17h', 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'type' => 'Filial', 'city' => 'Sao Paulo', 'address' => 'Estrada do Rufino, 850, Serraria, Diadema - SP', 'phone' => '(11) 94211-0729 / (11) 4057-3202', 'hours' => 'Segunda a Sexta, 8h as 18h / Sabado 8h as 13h', 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('site_history_sections')->insert([
            'id' => (string) Str::uuid(),
            'eyebrow' => 'Nossa historia',
            'title' => 'Mais de 18 anos de tradicao e excelencia',
            'body' => 'Desde 2007, a Fabrica de Fardamentos e sinonimo de excelencia na fabricacao de uniformes profissionais. Nossa jornada e marcada pela busca por inovacao, qualidade e satisfacao do cliente.',
            'mission' => 'Oferecer uniformes que unam funcionalidade, conforto e estilo.',
            'vision' => 'Ser referencia no mercado de uniformes profissionais.',
            'values' => 'Integridade, respeito, qualidade e sustentabilidade.',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_history_sections');
        Schema::dropIfExists('site_stores');
        Schema::dropIfExists('site_banners');
        Schema::dropIfExists('site_categories');
    }
};
