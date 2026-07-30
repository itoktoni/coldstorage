<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tags = [
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'PHP', 'slug' => 'php'],
            ['name' => 'JavaScript', 'slug' => 'javascript'],
            ['name' => 'Tailwindcss', 'slug' => 'tailwindcss'],
            ['name' => 'Livewire', 'slug' => 'livewire'],
            ['name' => 'MySQL', 'slug' => 'mysql'],
            ['name' => 'API', 'slug' => 'api'],
            ['name' => 'REST', 'slug' => 'rest'],
            ['name' => 'Vue', 'slug' => 'vue'],
            ['name' => 'React', 'slug' => 'react'],
            ['name' => 'Beginner', 'slug' => 'beginner'],
            ['name' => 'Advanced', 'slug' => 'advanced'],
            ['name' => 'Tutorial', 'slug' => 'tutorial'],
            ['name' => 'Tips', 'slug' => 'tips'],
        ];

        foreach ($tags as $tag) {
            DB::table('tags')->insertOrIgnore(array_merge($tag, [
                'deleted_at' => null,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('tags')->whereIn('slug', [
            'laravel', 'php', 'javascript', 'tailwindcss', 'livewire',
            'mysql', 'api', 'rest', 'vue', 'react', 'beginner',
            'advanced', 'tutorial', 'tips',
        ])->delete();
    }
};
