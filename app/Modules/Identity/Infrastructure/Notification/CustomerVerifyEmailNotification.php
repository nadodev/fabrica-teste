<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Notification;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

final class CustomerVerifyEmailNotification extends VerifyEmail
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirme seu e-mail')
            ->greeting('Ola, '.$notifiable->name.'!')
            ->line('Confirme seu e-mail para acessar pedidos, enderecos e dados da sua conta.')
            ->action('Confirmar e-mail', $this->verificationUrl($notifiable))
            ->line('Este link expira em '.config('auth.verification.expire', 60).' minutos.')
            ->line('Se voce nao criou esta conta, ignore esta mensagem.');
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
