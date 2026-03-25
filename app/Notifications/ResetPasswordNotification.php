<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject(__('passwords.reset_subject'))
            ->greeting(__('passwords.greeting'))
            ->line(__('passwords.receiving_email'))
            ->action(__('passwords.reset_action'), $url)
            ->line(__('passwords.expire', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(__('passwords.no_action'))
			->salutation(__('passwords.salutation', ['app' => env('APP_NAME')]));
    }

    protected function resetUrl($notifiable): string
    {
        // This matches Laravel’s default behavior (you can customize if needed)
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
