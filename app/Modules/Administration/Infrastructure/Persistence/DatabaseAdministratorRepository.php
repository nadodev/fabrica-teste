<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Modules\Administration\Application\DTO\AdministratorAccount;
use App\Modules\Administration\Application\Port\AdministratorRepository;
use App\Modules\Administration\Application\Port\AdminPermissionChecker;
use App\Modules\Administration\Domain\AdminPermission;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use stdClass;

final readonly class DatabaseAdministratorRepository implements AdministratorRepository, AdminPermissionChecker
{
    public function __construct(private ConnectionInterface $database) {}

    public function findByEmail(string $email): ?AdministratorAccount
    {
        $record = $this->database->table('users')->where('email', $email)->first();

        return $record === null ? null : $this->map($record, $this->permissionValues((int) $record->id));
    }

    public function findById(int $userId): ?AdministratorAccount
    {
        $record = $this->database->table('users')->where('id', $userId)->first();

        return $record === null ? null : $this->map($record, $this->permissionValues($userId));
    }

    public function all(): array
    {
        $records = $this->database->table('users')
            ->where('is_admin', true)
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();
        $permissionRows = $this->database->table('admin_user_permissions')
            ->whereIn('user_id', $records->pluck('id'))
            ->orderBy('permission')
            ->get(['user_id', 'permission'])
            ->groupBy('user_id');

        return array_values($records->map(function (stdClass $record) use ($permissionRows): AdministratorAccount {
            $permissions = (bool) $record->is_super_admin
                ? array_map(static fn (AdminPermission $permission): string => $permission->value, AdminPermission::cases())
                : array_values($permissionRows->get($record->id, collect())->pluck('permission')->map(static fn (mixed $value): string => (string) $value)->all());

            return $this->map($record, $permissions);
        })->all());
    }

    public function grant(int $userId, array $permissions, int $grantedBy): void
    {
        $now = now();
        $this->database->table('users')->where('id', $userId)->update([
            'is_admin' => true,
            'is_super_admin' => false,
            'updated_at' => $now,
        ]);
        $this->database->table('admin_user_permissions')->where('user_id', $userId)->delete();

        if ($permissions !== []) {
            $this->database->table('admin_user_permissions')->insert(array_map(
                static fn (AdminPermission $permission): array => [
                    'user_id' => $userId,
                    'permission' => $permission->value,
                    'granted_by' => $grantedBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $permissions,
            ));
        }
    }

    public function revoke(int $userId): void
    {
        $this->database->table('admin_user_permissions')->where('user_id', $userId)->delete();
        $this->database->table('sessions')->where('user_id', $userId)->delete();
        $this->database->table('users')->where('id', $userId)->update([
            'is_admin' => false,
            'is_super_admin' => false,
            'remember_token' => Str::random(60),
            'updated_at' => now(),
        ]);
    }

    public function allows(int $userId, AdminPermission $permission): bool
    {
        $administrator = $this->database->table('users')
            ->where('id', $userId)
            ->first(['is_admin', 'is_super_admin']);

        if ($administrator === null || ! (bool) $administrator->is_admin) {
            return false;
        }
        if ((bool) $administrator->is_super_admin) {
            return true;
        }
        if ($permission === AdminPermission::AdministratorsManage) {
            return false;
        }

        return $this->database->table('admin_user_permissions')
            ->where('user_id', $userId)
            ->where('permission', $permission->value)
            ->exists();
    }

    public function isSuperAdministrator(int $userId): bool
    {
        return $this->database->table('users')
            ->where('id', $userId)
            ->where('is_admin', true)
            ->where('is_super_admin', true)
            ->exists();
    }

    public function permissionValues(int $userId): array
    {
        if ($this->isSuperAdministrator($userId)) {
            return array_map(static fn (AdminPermission $permission): string => $permission->value, AdminPermission::cases());
        }

        return array_values($this->database->table('admin_user_permissions')
            ->where('user_id', $userId)
            ->orderBy('permission')
            ->pluck('permission')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all());
    }

    /** @param list<string> $permissions */
    private function map(stdClass $record, array $permissions): AdministratorAccount
    {
        return new AdministratorAccount(
            (int) $record->id,
            (string) $record->name,
            (string) $record->email,
            $record->email_verified_at !== null,
            (bool) $record->is_admin,
            (bool) $record->is_super_admin,
            $permissions,
        );
    }
}
