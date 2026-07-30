<?php

namespace Tests\Unit\Services;

use App\Models\Content;
use App\Services\ContentEntryExtractor;
use Tests\TestCase;

class ContentEntryExtractorTest extends TestCase
{
    public function test_extract_returns_sections_with_field_values()
    {
        $entry = Content::whereHas('type', fn($q) => $q->where('slug', 'homepage'))->first();
        
        $result = ContentEntryExtractor::extract($entry);
        
        $this->assertArrayHasKey('sections', $result);
        $this->assertArrayHasKey('slider', $result['sections']);
        $this->assertArrayHasKey('carousel', $result['sections']['slider']);
        $this->assertIsArray($result['sections']['slider']['carousel']);
    }
    
    public function test_extract_container_field_returns_array_of_items()
    {
        $entry = Content::whereHas('type', fn($q) => $q->where('slug', 'homepage'))->first();
        
        $result = ContentEntryExtractor::extract($entry);
        
        $carousel = $result['sections']['slider']['carousel'];
        $this->assertIsArray($carousel);
        $this->assertArrayHasKey('text', $carousel[0]);
        $this->assertArrayHasKey('image', $carousel[0]);
        $this->assertArrayHasKey('button', $carousel[0]);
    }
    
    public function test_form_schema_returns_sections_with_fields()
    {
        $entry = Content::whereHas('type', fn($q) => $q->where('slug', 'homepage'))->first();
        
        $result = ContentEntryExtractor::formSchema($entry);
        
        $this->assertArrayHasKey('sections', $result);
        $this->assertArrayHasKey('slider', $result['sections']);
        $this->assertArrayHasKey('fields', $result['sections']['slider']);
    }
}
