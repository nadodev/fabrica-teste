<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Notification;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

final class CustomerResetPasswordNotification extends ResetPassword
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Redefinicao de senha')
            ->greeting('Ola, '.$notifiable->name.'!')
            ->line('Recebemos uma solicitacao para redefinir a senha da sua conta.')
            ->action('Criar nova senha', $url)
            ->line('Este link expira em '.config('auth.passwords.users.expire', 60).' minutos.')
            ->line('Se voce nao solicitou a redefinicao, ignore esta mensagem.');
    }
}
