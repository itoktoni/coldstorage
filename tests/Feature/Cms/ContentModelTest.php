<?php

use App\Models\Content;
use App\Models\Field;
use App\Models\Section;
use App\Models\Type;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->type = Type::create([
        'name' => 'Post',
        'slug' => 'post',
        'type' => 'custom',
    ]);

    $this->field = Field::create([
        'name' => 'subtitle',
        'label' => 'Subtitle',
        'type' => 'text',
    ]);

    $this->section = Section::create([
        'name' => 'Post Meta',
        'content_type_id' => $this->type->id,
        'field_ids' => [$this->field->id],
        'is_active' => true,
    ]);

    $this->entry = Content::create([
        'content_type_id' => $this->type->id,
        'title' => 'Test Post',
        'slug' => 'test-post',
        'content' => 'Body text',
        'status' => 'published',
        'published_at' => now(),
        'meta' => ['_active_field_groups' => [$this->section->id]],
    ]);

    DB::table('content_fields')->insert([
        'content_entry_id' => $this->entry->id,
        'custom_field_id' => $this->field->id,
        'value' => 'A subtitle',
    ]);
});

it('has type relationship', function () {
    expect($this->entry->type)->toBeInstanceOf(Type::class);
    expect($this->entry->type->id)->toBe($this->type->id);
});

it('retrieves a single meta field by name', function () {
    $value = $this->entry->getMeta('subtitle');
    expect($value)->toBe('A subtitle');
});

it('returns null for missing meta field', function () {
    $value = $this->entry->getMeta('nonexistent');
    expect($value)->toBeNull();
});

it('returns default_value when meta has no value', function () {
    $field = Field::create([
        'name' => 'reading_time',
        'label' => 'Reading Time',
        'type' => 'number',
        'default_value' => '5',
    ]);

    Section::create([
        'name' => 'Extra',
        'content_type_id' => $this->type->id,
        'field_ids' => [$field->id],
        'is_active' => true,
    ]);

    $value = $this->entry->getMeta('reading_time');
    expect($value)->toBe('5');
});

it('retrieves all meta as array', function () {
    $allMeta = $this->entry->getAllMeta();
    expect($allMeta)->toHaveKey('subtitle');
    expect($allMeta['subtitle'])->toBe('A subtitle');
});

it('returns normalized data with sections', function () {
    $this->entry->meta = array_merge($this->entry->meta ?? [], [
        'page_builder' => [
            ['_layout' => 'hero', 'title' => 'Hi'],
        ],
    ]);
    $this->entry->save();

    $normalized = $this->entry->getNormalizedData();
    expect($normalized['title'])->toBe('Test Post');
    expect($normalized['type'])->toBe('post');
    expect($normalized)->toHaveKey('sections');
    expect($normalized['sections']['page_builder'][0]['_type'])->toBe('hero');
    expect($normalized['sections']['page_builder'][0])->not->toHaveKey('_layout');
});

it('scopes published entries', function () {
    $draft = Content::create([
        'content_type_id' => $this->type->id,
        'title' => 'Draft',
        'slug' => 'draft',
        'status' => 'draft',
    ]);

    $published = Content::published()->get();
    expect($published->pluck('id'))->toContain($this->entry->id);
    expect($published->pluck('id'))->not->toContain($draft->id);
});

it('checks is_published attribute', function () {
    expect($this->entry->is_published)->toBeTrue();

    $draft = Content::create([
        'content_type_id' => $this->type->id,
        'title' => 'Draft',
        'slug' => 'draft-2',
        'status' => 'draft',
    ]);
    expect($draft->is_published)->toBeFalse();
});

it('returns blueprint schema', function () {
    $schema = $this->entry->getBlueprintSchema();
    expect($schema)->toHaveKey('content_type');
    expect($schema['content_type'])->toBe('post');
    expect($schema)->toHaveKey('sections');
    expect($schema['sections'])->toHaveKey('Post Meta');
});
