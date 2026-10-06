<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class SiteMailer
{
    /**
     * Point Laravel's mailer at the SMTP server saved in the admin panel.
     */
    public function configure(): void
    {
        $mail = SiteSetting::valuesFor('mail');

        if ($mail['mailer'] === 'smtp' && filled($mail['host'])) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.scheme' => $mail['encryption'] === 'ssl' ? 'smtps' : 'smtp',
                'mail.mailers.smtp.url' => null,
                'mail.mailers.smtp.host' => $mail['host'],
                'mail.mailers.smtp.port' => (int) $mail['port'],
                'mail.mailers.smtp.username' => $mail['username'] ?: null,
                'mail.mailers.smtp.password' => self::decryptPassword($mail['password']),
            ]);
        } elseif ($mail['mailer'] === 'log') {
            config(['mail.default' => 'log']);
        }

        if (filled($mail['from_address'])) {
            config([
                'mail.from.address' => $mail['from_address'],
                'mail.from.name' => $mail['from_name'] ?: config('app.name'),
            ]);
        }

        Mail::purge(config('mail.default'));
    }

    /**
     * The address that receives contact form messages.
     */
    public function recipient(): ?string
    {
        $contact = SiteSetting::valuesFor('contact');

        return $contact['recipient_email'] ?: (SiteSetting::valuesFor('mail')['from_address'] ?: null);
    }

    public static function encryptPassword(string $password): string
    {
        return $password === '' ? '' : Crypt::encryptString($password);
    }

    public static function decryptPassword(?string $encrypted): ?string
    {
        if (blank($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }
}
