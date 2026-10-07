<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:make-superadmin {email : The existing user email address}')]
#[Description('Assign the Superadmin role to an existing user.')]
class MakeUserSuperadmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }

        $role = Role::query()->firstOrCreate(['name' => Role::SUPERADMIN]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->info("{$user->email} is now a Superadmin.");

        return self::SUCCESS;
    }
}
