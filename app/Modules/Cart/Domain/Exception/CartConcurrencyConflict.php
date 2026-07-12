<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Exception;

use RuntimeException;

final class CartConcurrencyConflict extends RuntimeException {}
