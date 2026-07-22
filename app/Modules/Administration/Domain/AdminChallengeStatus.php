<?php

declare(strict_types=1);

namespace App\Modules\Administration\Domain;

enum AdminChallengeStatus: string
{
    case Success = 'success';
    case Invalid = 'invalid';
    case Expired = 'expired';
    case Locked = 'locked';
    case Consumed = 'consumed';
}
