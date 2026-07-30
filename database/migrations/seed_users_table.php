<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->insertOrIgnore([
            [
                'name' => 'Admin User',
                'email' => 'admin@kepompong.id',
                'phone' => '081234567890',
                'verified_at' => now(),
                'role' => 'developer',
                'subscribe' => 1,
                'affiliate_code' => 'ADMIN001',
                'affiliate_reff' => null,
                'affiliate_discount' => 0,
                'rekening_nama' => 'Admin User',
                'rekening_bank' => 'BCA',
                'rekening_nomor' => '1234567890',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@kepompong.id',
                'phone' => '089876543210',
                'verified_at' => now(),
                'role' => 'user',
                'subscribe' => 1,
                'affiliate_code' => 'USER001',
                'affiliate_reff' => 'ADMIN001',
                'affiliate_discount' => 0,
                'rekening_nama' => 'Regular User',
                'rekening_bank' => 'BNI',
                'rekening_nomor' => '0987654321',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('users')->whereIn('email', ['admin@kepompong.id', 'user@kepompong.id'])->delete();
    }
};
