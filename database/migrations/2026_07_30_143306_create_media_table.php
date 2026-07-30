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
        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index('media_user_id_foreign');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mime_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
