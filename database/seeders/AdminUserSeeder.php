<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'مدير النظام',
                'role' => 'admin',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'teacher@school.com'],
            [
                'name' => 'معلم النظام',
                'role' => 'teacher',
                'password' => Hash::make('teacher123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
