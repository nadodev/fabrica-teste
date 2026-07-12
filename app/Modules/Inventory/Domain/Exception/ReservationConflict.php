<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Exception;

use DomainException;

final class ReservationConflict extends DomainException {}
