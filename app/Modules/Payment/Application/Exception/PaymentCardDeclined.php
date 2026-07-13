<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Exception;

use RuntimeException;

final class PaymentCardDeclined extends RuntimeException {}
