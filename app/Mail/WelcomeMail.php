<?php

namespace App\Mail;

use App\Models\User;
use App\Services\Email\EmailTemplateOverrides;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user)
    {
        $this->onQueue('mail');
    }

    public function build(): self
    {
        $name = (string) ($this->user->name ?? '');
        $email = (string) ($this->user->email ?? '');
        $url = route('user.shop.home');

        $viewData = [
            'title' => __('Welcome to YallaSpare'),
            'name' => $name,
            'email' => $email,
            'actionUrl' => $url,
            'actionText' => __('Open Your Account'),
            'intro' => __('Your account is ready. You can now browse thousands of auto parts, place orders, track deliveries, and manage your account — all in one place.'),
        ];

        $templateVars = ['brand' => 'YallaSpare', 'name' => $name, 'email' => $email, 'url' => $url];
        $overrides = app(EmailTemplateOverrides::class);
        $viewData = $overrides->viewData('welcome', $templateVars) + $viewData;

        return $this
            ->subject($overrides->subject('welcome', __('Welcome to YallaSpare'), $templateVars))
            ->view('emails.auth.welcome', $viewData)
            ->text('emails.text.generic', $viewData);
    }
}
