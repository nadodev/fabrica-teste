<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
