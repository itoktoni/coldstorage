<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->enum('user_agama', ['islam', 'kristen_protestan', 'kristen_katolik', 'hindu', 'buddha', 'konghucu'])->nullable();
            $table->string('role')->default('user');
            $table->integer('subscribe')->nullable();
            $table->string('affiliate_code', 30)->nullable()->unique();
            $table->string('affiliate_reff', 30)->nullable();
            $table->integer('affiliate_discount')->default(0);
            $table->string('rekening_nama')->nullable();
            $table->string('rekening_bank')->nullable();
            $table->string('rekening_nomor')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
