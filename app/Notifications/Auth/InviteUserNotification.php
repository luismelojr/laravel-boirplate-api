<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $tenantName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.url').'/invite/accept?token='.$this->token;

        return (new MailMessage)
            ->subject('Você foi convidado para '.$this->tenantName)
            ->greeting('Olá!')
            ->line('Você foi convidado para acessar '.$this->tenantName.'.')
            ->line('Clique no botão abaixo para criar sua senha e ativar sua conta.')
            ->action('Aceitar convite', $url)
            ->line('Este convite expira em 24 horas.')
            ->line('Se você não esperava este convite, ignore este e-mail.');
    }
}
