<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Exception;

use RuntimeException;

final class AdminLoginChallengeFailed extends RuntimeException
{
    public function __construct(string $message, public readonly bool $restart)
    {
        parent::__construct($message);
    }
}
