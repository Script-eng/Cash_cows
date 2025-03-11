<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
{
    User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);
    
    // You could also add some sample regular users
    User::create([
        'name' => 'Regular User',
        'email' => 'user@example.com',
        'password' => Hash::make('password'),
        'role' => 'user',
        'email_verified_at' => now(),
    ]);
}
}