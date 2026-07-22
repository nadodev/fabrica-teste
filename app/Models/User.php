<?php

namespace App\Models;

use App\Modules\Identity\Infrastructure\Notification\CustomerResetPasswordNotification;
use App\Modules\Identity\Infrastructure\Notification\CustomerVerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $document
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_admin
 * @property bool $is_super_admin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'phone', 'document', 'password'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomerVerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPasswordNotification((string) $token));
    }
}
