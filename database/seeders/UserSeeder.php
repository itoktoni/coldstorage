<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
                'email' => 'admin@kepompong.id',
                'name' => 'Admin User',
                'role' => 'developer',
                'password' => bcrypt(env('PASSWORD', 'password')),
                'subscribe' => 1,
                'verified_at' => date('Y-m-d H:i:s'),
                'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        User::create([
                'email' => 'user@kepompong.id',
                'name' => 'Admin User',
                'role' => 'user',
                'password' => bcrypt(env('PASSWORD', 'password')),
                'subscribe' => 1,
                'verified_at' => date('Y-m-d H:i:s'),
                'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
