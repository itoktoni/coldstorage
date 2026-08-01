<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create new categories table with slug as primary key
        Schema::create('categories_new', function (Blueprint $table) {
            $table->string('slug')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        // Migrate data
        $categories = DB::table('categories')->orderBy('id')->get();
        foreach ($categories as $cat) {
            DB::table('categories_new')->insert([
                'slug' => $cat->slug,
                'name' => $cat->name,
                'description' => $cat->description,
                'parent_id' => $cat->parent_id,
                'sort_order' => $cat->sort_order,
                'created_at' => $cat->created_at,
                'updated_at' => $cat->updated_at,
                'deleted_at' => $cat->deleted_at,
            ]);
        }

        // Drop old table and rename
        Schema::dropIfExists('categories');
        Schema::rename('categories_new', 'categories');
    }

    public function down(): void
    {
        Schema::create('categories_old', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        $categories = DB::table('categories')->orderBy('slug')->get();
        foreach ($categories as $cat) {
            DB::table('categories_old')->insert([
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'parent_id' => $cat->parent_id,
                'sort_order' => $cat->sort_order,
                'created_at' => $cat->created_at,
                'updated_at' => $cat->updated_at,
                'deleted_at' => $cat->deleted_at,
            ]);
        }

        Schema::dropIfExists('categories');
        Schema::rename('categories_old', 'categories');
    }
};
