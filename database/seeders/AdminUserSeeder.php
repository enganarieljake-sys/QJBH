<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Jackie Salon Pepito',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'tenant_approved_at' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'tenant@example.com'],
            [
                'name' => 'Jake Engaña',
                'password' => Hash::make('password'),
                'role' => 'tenant',
                'tenant_approved_at' => now(),
            ]
        );
    }
}
