<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifique seu endereço de e-mail')
            ->greeting('Olá!')
            ->line('Clique no botão abaixo para verificar seu endereço de e-mail.')
            ->action('Verificar e-mail', $url)
            ->line('Este link expira em 60 minutos.')
            ->line('Se você não criou uma conta, ignore este e-mail.');
    }
}
