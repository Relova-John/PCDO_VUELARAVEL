<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {email} {--name=} {--password=} {--role=} {--verified : Mark email as verified}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->option('name') ?? 'New User';
        $password = $this->option('password') ?? 'secret123';
        $role = $this->option('role');
        $verified = $this->option('verified') ? now() : null;

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        if ($role) {
            Role::firstOrCreate(['name' => $role]);
            $user->assignRole($role);
        }

        if ($verified) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->info("User created: {$user->email} with role: {$role} and password: {$password}."."\n".'Email verified: '.($verified ? 'Yes' : 'No'));
    }
}
