<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    public function test_robots_txt_renders_plain_text(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->assertSee('User-agent: *');
        $response->assertSee('Disallow: /admin/');
        $response->assertSee('Sitemap:');
    }
}

