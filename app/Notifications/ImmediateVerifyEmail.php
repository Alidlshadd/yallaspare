<?php

namespace App\Notifications;

use App\Support\EmailVerificationCode;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImmediateVerifyEmail extends Notification
{
    public function __construct(private ?string $code = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationCode = $this->code ?? EmailVerificationCode::generateFor($notifiable);
        $expiresIn = EmailVerificationCode::expiresIn();
        $email = (string) $notifiable->getEmailForVerification();

        $viewData = [
            'title' => __('Verify your email address'),
            'preheader' => __('Use your YallaSpare verification code to protect your account.'),
            'intro' => __('Enter this verification code on the YallaSpare verification screen to protect your account.'),
            'email' => $email,
            'expiresIn' => $expiresIn,
            'verificationCode' => $verificationCode,
        ];

        return (new MailMessage)
            ->subject(__('Verify your YallaSpare email address'))
            ->greeting(__('Welcome to YallaSpare'))
            ->line(__('Enter this verification code on the YallaSpare verification screen to protect your account and unlock checkout, orders, saved addresses, and account settings.'))
            ->line(__('Your verification code is :code.', ['code' => $verificationCode]))
            ->line(__('This verification code expires in :count minutes.', ['count' => $expiresIn]))
            ->line(__('If you did not create a YallaSpare account, you can ignore this email.'))
            ->view('emails.auth.verify-email', $viewData)
            ->text('emails.text.generic', $viewData);
    }
}
