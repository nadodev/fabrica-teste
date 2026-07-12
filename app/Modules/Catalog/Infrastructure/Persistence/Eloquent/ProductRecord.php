<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ProductRecord extends Model
{
    use HasUuids;

    protected $table = 'catalog_products';

    protected $fillable = ['id', 'sku', 'name', 'description', 'price_amount', 'price_currency', 'status', 'image_url'];

    protected function casts(): array
    {
        return ['price_amount' => 'integer'];
    }
}
