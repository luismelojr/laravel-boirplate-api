<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForgotPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.url').'/reset-password?token='.$this->token.'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Redefinição de senha')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Você solicitou a redefinição de senha da sua conta.')
            ->action('Redefinir senha', $url)
            ->line('Este link expira em 1 hora.')
            ->line('Se você não solicitou a redefinição, ignore este e-mail.');
    }
}
