<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@boirplate.test')],
            [
                'name' => 'Admin',
                'password' => env('ADMIN_PASSWORD', 'password'),
                'status' => 'active',
            ]
        );

        $user->assignRole('admin');
    }
}
