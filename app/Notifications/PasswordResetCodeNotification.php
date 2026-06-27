<?php

namespace App\Notifications;

use App\Services\PasswordResetOtp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password reset code')
            ->greeting('Reset your password')
            ->line('Your password reset code is:')
            ->line($this->code)
            ->line('It expires in '.PasswordResetOtp::TTL_MINUTES.' minutes.');
    }
}
