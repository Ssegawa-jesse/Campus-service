<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Create a new administrator, or promote an existing user to admin:
|   php artisan pearl360:make-admin jane@uwa.go.ug --name="Jane Namuli" --phone=0772123456
| A password is asked for (or pass --password=...). Run it as many times as you need admins.
*/
Artisan::command('pearl360:make-admin {email} {--name=} {--phone=} {--password=}', function (string $email) {
    $email = Illuminate\Support\Str::lower(trim($email));
    $user = App\Models\User::where('email', $email)->first();

    if ($user) {
        $user->update(['role' => App\Models\User::ROLE_ADMIN, 'is_active' => true]);
        if ($this->option('password')) {
            $user->update(['password' => $this->option('password')]);
        }
        $this->info("{$user->name} <{$email}> is now an administrator.");

        return;
    }

    $password = $this->option('password') ?: $this->secret('Password for the new admin (min 8 characters)');
    if (strlen((string) $password) < 8) {
        $this->error('Password must be at least 8 characters.');

        return 1;
    }

    App\Models\User::create([
        'name' => $this->option('name') ?: Illuminate\Support\Str::of($email)->before('@')->replace('.', ' ')->title(),
        'email' => $email,
        'phone' => $this->option('phone'),
        'country' => 'Uganda',
        'role' => App\Models\User::ROLE_ADMIN,
        'password' => $password,
    ]);
    $this->info("Administrator {$email} created. They can log in at ".url('/login'));
})->purpose('Create a Pearl360 administrator or promote an existing user');
