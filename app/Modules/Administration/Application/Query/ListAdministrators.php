<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Query;

use App\Modules\Administration\Application\DTO\AdministratorAccount;
use App\Modules\Administration\Application\Port\AdministratorRepository;

final readonly class ListAdministrators
{
    public function __construct(private AdministratorRepository $administrators) {}

    /** @return list<AdministratorAccount> */
    public function handle(): array
    {
        return $this->administrators->all();
    }
}
