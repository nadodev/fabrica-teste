<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Port;

interface EventBus
{
    public function publish(object ...$events): void;
}
