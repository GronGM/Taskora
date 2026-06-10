<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Локальный заказчик', 'email' => 'customer@taskora.local', 'role' => User::ROLE_CUSTOMER],
            ['name' => 'Локальный исполнитель', 'email' => 'performer@taskora.local', 'role' => User::ROLE_PERFORMER],
            ['name' => 'Локальный модератор', 'email' => 'moderator@taskora.local', 'role' => User::ROLE_MODERATOR],
            ['name' => 'Локальный администратор', 'email' => 'admin@taskora.local', 'role' => User::ROLE_ADMIN],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->call([
            CategorySeeder::class,
            ServiceSeeder::class,
            TaskSeeder::class,
        ]);
    }
}
