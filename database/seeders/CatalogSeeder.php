<?php

namespace Database\Seeders;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use Illuminate\Database\Seeder;

final class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['id' => '0190f566-c399-79e3-a553-7e5fb8d83419', 'sku' => 'POLO-001', 'name' => 'Camisa Polo Empresarial', 'description' => 'Malha piquet premium com acabamento reforçado.', 'price_amount' => 7990],
            ['id' => '0190f566-c399-79e3-a553-7e5fb8d83420', 'sku' => 'BRIM-001', 'name' => 'Calça profissional em brim', 'description' => 'Brim resistente para rotinas operacionais.', 'price_amount' => 9990],
            ['id' => '0190f566-c399-79e3-a553-7e5fb8d83421', 'sku' => 'JALECO-001', 'name' => 'Jaleco profissional', 'description' => 'Acabamento profissional e tecido confortável.', 'price_amount' => 11990],
            ['id' => '0190f566-c399-79e3-a553-7e5fb8d83422', 'sku' => 'ESCOLAR-001', 'name' => 'Uniforme escolar manga curta', 'description' => 'Tecido leve e resistente a lavagens frequentes.', 'price_amount' => 4990],
        ];

        foreach ($products as $product) {
            ProductRecord::query()->updateOrCreate(['id' => $product['id']], $product + [
                'price_currency' => 'BRL',
                'status' => 'active',
            ]);
        }
    }
}
