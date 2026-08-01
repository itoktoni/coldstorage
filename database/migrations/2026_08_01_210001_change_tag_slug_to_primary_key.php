<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create new tags table with slug as primary key
        Schema::create('tags_new', function (Blueprint $table) {
            $table->string('slug')->primary();
            $table->string('name')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        // Migrate data
        $tags = DB::table('tags')->orderBy('id')->get();
        foreach ($tags as $tag) {
            DB::table('tags_new')->insert([
                'slug' => $tag->slug,
                'name' => $tag->name,
                'deleted_at' => $tag->deleted_at,
            ]);
        }

        // Drop old table and rename
        Schema::dropIfExists('tags');
        Schema::rename('tags_new', 'tags');
    }

    public function down(): void
    {
        Schema::create('tags_old', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index();
            $table->timestamp('deleted_at')->nullable();
        });

        $tags = DB::table('tags')->orderBy('slug')->get();
        foreach ($tags as $tag) {
            DB::table('tags_old')->insert([
                'name' => $tag->name,
                'slug' => $tag->slug,
                'deleted_at' => $tag->deleted_at,
            ]);
        }

        Schema::dropIfExists('tags');
        Schema::rename('tags_old', 'tags');
    }
};
