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
            ['email' => 'junimhs10@gmail.com'],
            [
                'name' => 'Luis Henrique',
                'password' => '3010Rpwt28@',
                'status' => 'active',
            ]
        );

        $user->assignRole('admin');
    }
}
