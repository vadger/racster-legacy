<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\VerifyEmail;

class VerifyEmailNotification extends VerifyEmail
{
    use Queueable;

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {

		$verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
			->subject(__('passwords.verification_subject'))
			->greeting(__('passwords.greeting'))
            ->line(__('passwords.click_button'))
            ->action(__('passwords.verify_button'), $verificationUrl)
            ->line(__('passwords.no_account'))
			->salutation(__('passwords.salutation', ['app' => env('APP_NAME')]));

    }

}
