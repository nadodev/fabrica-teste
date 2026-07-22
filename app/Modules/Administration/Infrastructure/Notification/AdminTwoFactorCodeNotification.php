<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Notification;

use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AdminTwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly DateTimeImmutable $expiresAt,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de acesso administrativo')
            ->greeting('Confirmação de acesso')
            ->line('Use o código abaixo para concluir o acesso ao painel administrativo:')
            ->line($this->code)
            ->line('O código é válido por poucos minutos e pode ser usado apenas uma vez.')
            ->line('Se você não tentou entrar, altere sua senha e avise o responsável pela loja.');
    }
}
