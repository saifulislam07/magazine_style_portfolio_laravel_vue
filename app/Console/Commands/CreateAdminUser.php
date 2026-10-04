<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {name?} {email?}';

    protected $description = 'Create a user account with access to the admin panel';

    public function handle(): int
    {
        $name = $this->argument('name') ?: $this->ask('Admin name');
        $name = trim($name);
        $email = strtolower(trim($this->argument('email') ?: $this->ask('Admin email')));

        if ($name === '' || strlen($name) > 255) {
            $this->error('Enter a name between 1 and 255 characters.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('A user with that email already exists.');

            return self::FAILURE;
        }

        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');

        if (strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
            $this->error('Passwords must match and be at least 12 characters long.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info("Admin account created for {$email}.");

        return self::SUCCESS;
    }
}
