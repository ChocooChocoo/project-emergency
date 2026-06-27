<?php

namespace App\Notifications;

use App\Services\EmailOtp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpNotification extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your verification code')
            ->greeting('Verify your email')
            ->line('Your verification code is:')
            ->line($this->code)
            ->line('It expires in '.EmailOtp::TTL_MINUTES.' minutes.');
    }
}
