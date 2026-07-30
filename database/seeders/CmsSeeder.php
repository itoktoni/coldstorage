<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\CustomField;
use App\Models\FieldGroup;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        // Create Content Types
        $post = ContentType::create(['name' => 'Post', 'slug' => 'post', 'description' => 'Blog posts', 'menu_icon' => 'article', 'menu_position' => 1, 'is_active' => true]);
        $page = ContentType::create(['name' => 'Page', 'slug' => 'page', 'description' => 'Static pages', 'menu_icon' => 'description', 'menu_position' => 2, 'is_active' => true]);

        // Create Custom Fields
        $subtitle = CustomField::create(['label' => 'Subtitle', 'name' => 'subtitle', 'type' => 'text', 'sort_order' => 1, 'is_required' => false]);
        $authorBio = CustomField::create(['label' => 'Author Bio', 'name' => 'author_bio', 'type' => 'textarea', 'sort_order' => 2, 'is_required' => false]);
        $readingTime = CustomField::create(['label' => 'Reading Time (min)', 'name' => 'reading_time', 'type' => 'number', 'sort_order' => 3, 'is_required' => false]);
        $seoTitle = CustomField::create(['label' => 'SEO Title', 'name' => 'seo_title', 'type' => 'text', 'sort_order' => 4, 'is_required' => false]);
        $seoDescription = CustomField::create(['label' => 'SEO Description', 'name' => 'seo_description', 'type' => 'textarea', 'sort_order' => 5, 'is_required' => false]);

        // Create Field Groups
        $postMeta = FieldGroup::create(['name' => 'Post Meta', 'description' => 'Additional metadata for posts', 'content_type_id' => $post->id, 'sort_order' => 1, 'is_active' => true]);
        $postMeta->fields()->attach([$subtitle->id, $authorBio->id, $readingTime->id]);

        $seoGroup = FieldGroup::create(['name' => 'SEO', 'description' => 'Search engine optimization fields', 'content_type_id' => $post->id, 'sort_order' => 2, 'is_active' => true]);
        $seoGroup->fields()->attach([$seoTitle->id, $seoDescription->id]);

        // Create Categories
        $tech = Category::create(['name' => 'Technology', 'slug' => 'technology', 'description' => 'Tech articles', 'sort_order' => 1]);
        $design = Category::create(['name' => 'Design', 'slug' => 'design', 'description' => 'Design articles', 'sort_order' => 2]);
        $business = Category::create(['name' => 'Business', 'slug' => 'business', 'description' => 'Business articles', 'sort_order' => 3]);
        $php = Category::create(['name' => 'PHP', 'slug' => 'php', 'parent_id' => $tech->id, 'sort_order' => 1]);
        $laravel = Category::create(['name' => 'Laravel', 'slug' => 'laravel', 'parent_id' => $tech->id, 'sort_order' => 2]);

        // Create Tags
        $tags = [];
        $tagNames = ['laravel', 'php', 'javascript', 'tailwindcss', 'livewire', 'mysql', 'api', 'rest', 'vue', 'react', 'beginner', 'advanced', 'tutorial', 'tips'];
        foreach ($tagNames as $tagName) {
            $tags[] = Tag::create(['name' => ucfirst($tagName), 'slug' => $tagName]);
        }

        // Create Content Entries (Posts)
        $posts = [
            ['title' => 'Getting Started with Laravel 11', 'slug' => 'getting-started-laravel-11', 'content' => 'Laravel 11 brings exciting new features and improvements. In this guide, we explore the latest changes and how to upgrade your existing applications.', 'excerpt' => 'A comprehensive guide to Laravel 11', 'category_ids' => [$laravel->id], 'tag_ids' => [$tags[0]->id, $tags[2]->id]],
            ['title' => 'Building RESTful APIs with Laravel', 'slug' => 'building-restful-apis-laravel', 'content' => 'Learn how to build robust and scalable RESTful APIs using Laravel. We cover authentication, rate limiting, versioning, and best practices.', 'excerpt' => 'Master REST API development with Laravel', 'category_ids' => [$tech->id, $php->id], 'tag_ids' => [$tags[0]->id, $tags[6]->id, $tags[7]->id]],
            ['title' => 'Tailwind CSS Best Practices', 'slug' => 'tailwind-css-best-practices', 'content' => 'Discover the best practices for using Tailwind CSS in your projects. From utility-first approach to custom configurations and responsive design.', 'excerpt' => 'Optimize your Tailwind CSS workflow', 'category_ids' => [$design->id], 'tag_ids' => [$tags[3]->id]],
            ['title' => 'PHP 8.3 New Features', 'slug' => 'php-83-new-features', 'content' => 'PHP 8.3 introduces readonly classes, typed class constants, json_validate function, and many more exciting features for developers.', 'excerpt' => 'Explore what PHP 8.3 has to offer', 'category_ids' => [$tech->id, $php->id], 'tag_ids' => [$tags[1]->id, $tags[2]->id]],
            ['title' => 'Livewire vs Inertia.js', 'slug' => 'livewire-vs-inertia', 'content' => 'A detailed comparison between Laravel Livewire and Inertia.js. Both are excellent choices for building modern web applications with Laravel.', 'excerpt' => 'Which frontend approach is right for you?', 'category_ids' => [$tech->id], 'tag_ids' => [$tags[4]->id, $tags[10]->id]],
        ];

        foreach ($posts as $postData) {
            $categoryIds = $postData['category_ids'] ?? [];
            $tagIds = $postData['tag_ids'] ?? [];
            unset($postData['category_ids'], $postData['tag_ids']);

            $entry = ContentEntry::create(array_merge($postData, [
                'content_type_id' => $post->id,
                'status' => 'published',
                'published_at' => now(),
            ]));

            $entry->categories()->attach($categoryIds);
            $entry->tags()->attach($tagIds);

            $entry->setMeta('subtitle', $postData['excerpt']);
        }

        // Create Pages
        $pages = [
            ['title' => 'About Us', 'slug' => 'about-us', 'content' => 'We are a team of passionate developers building amazing things.', 'excerpt' => 'Learn about our team'],
            ['title' => 'Contact', 'slug' => 'contact', 'content' => 'Get in touch with us via email or phone.', 'excerpt' => 'Contact information'],
        ];

        foreach ($pages as $pageData) {
            ContentEntry::create(array_merge($pageData, [
                'content_type_id' => $page->id,
                'status' => 'published',
                'published_at' => now(),
            ]));
        }
    }
}