<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    protected $signature = 'cv:admin
        {--email= : Admin email (prompted for when omitted)}
        {--password= : Password, min 12 characters (generated when omitted in a non-interactive shell)}';

    protected $description = 'Create the admin user, or reset its password';

    public function handle(): int
    {
        $interactive = $this->input->isInteractive() && stream_isatty(STDIN);

        $email = $this->option('email')
            ?? ($interactive ? text('Admin email', required: true, validate: ['email' => 'email']) : null);

        $password = $this->option('password')
            ?? ($interactive ? password('Password (min 12 characters)', required: true, validate: ['password' => 'min:12']) : null);

        if (! $email) {
            $this->error('Pass --email=you@example.com when running without a terminal.');

            return self::FAILURE;
        }

        $generated = $password === null;
        $password ??= Str::password(20, symbols: false);

        $validator = Validator::make(compact('email', 'password'), ['email' => 'email', 'password' => 'min:12']);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::updateOrCreate(['email' => $email], ['name' => 'Admin', 'password' => $password]);

        $this->info("Admin {$email} is ready. Log in at /admin/login");
        if ($generated) {
            $this->line("Generated password: <comment>{$password}</comment>  (store it in your password manager)");
        }

        return self::SUCCESS;
    }
}
