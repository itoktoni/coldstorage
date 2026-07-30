<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')->insertOrIgnore([
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Tech articles and tutorials',
                'parent_id' => null,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'name' => 'Design',
                'slug' => 'design',
                'description' => 'Design articles and inspiration',
                'parent_id' => null,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Business and entrepreneurship',
                'parent_id' => null,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'name' => 'PHP',
                'slug' => 'php',
                'description' => 'PHP programming language',
                'parent_id' => null,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'name' => 'Laravel',
                'slug' => 'laravel',
                'description' => 'Laravel framework',
                'parent_id' => null,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('categories')->whereIn('slug', ['technology', 'design', 'business', 'php', 'laravel'])->delete();
    }
};
