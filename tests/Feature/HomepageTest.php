<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_loads_successfully()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('hero-main-slider');
    }

    public function test_homepage_contains_hero_data()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Ketelitian dalam');
        $response->assertSee('Institusi IPFK Terakreditasi');
    }

    public function test_api_returns_homepage_data()
    {
        $response = $this->get('/api/content');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'title',
            'sections' => [
                'hero',
                'certifications',
                'verification',
                'services',
                'competency',
                'news',
                'cta',
                'clients',
            ],
        ]);
    }
}
