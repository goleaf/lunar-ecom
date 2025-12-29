<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_tree_endpoint_returns_nested_children(): void
    {
        $root = Category::factory()->create([
            'slug' => 'root-category',
            'display_order' => 0,
        ]);

        $child = Category::factory()
            ->withParent($root)
            ->create([
                'slug' => 'child-category',
                'display_order' => 0,
            ]);

        $this->getJson(route('categories.tree'))
            ->assertOk()
            ->assertJsonPath('data.0.slug', $root->slug)
            ->assertJsonPath('data.0.children.0.slug', $child->slug);
    }

    public function test_category_flat_endpoint_returns_indented_names(): void
    {
        $root = Category::factory()->create([
            'display_order' => 0,
        ]);

        $child = Category::factory()->withParent($root)->create([
            'display_order' => 0,
        ]);

        $response = $this->getJson(route('categories.flat'))
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);

        $rows = $response->json('data');

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);

        $childRow = collect($rows)->firstWhere('id', $child->id);

        $this->assertNotNull($childRow, 'Expected flat list to include the child category row.');
        $this->assertSame($child->slug, $childRow['slug']);
        $this->assertSame(1, $childRow['depth']);
        $this->assertStringStartsWith('— ', $childRow['name']);
    }

    public function test_category_navigation_endpoint_returns_root_categories_in_navigation(): void
    {
        $root = Category::factory()->create([
            'slug' => 'nav-root',
            'display_order' => 0,
            'show_in_navigation' => true,
        ]);

        $this->getJson(route('categories.navigation'))
            ->assertOk()
            ->assertJsonPath('data.0.slug', $root->slug);
    }

    public function test_category_breadcrumb_endpoint_returns_breadcrumb_trail(): void
    {
        $root = Category::factory()->create([
            'slug' => 'crumb-root',
            'display_order' => 0,
        ]);

        $child = Category::factory()->withParent($root)->create([
            'slug' => 'crumb-child',
            'display_order' => 0,
        ]);

        $response = $this->getJson(route('categories.breadcrumb', $child))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'slug', 'url'],
                ],
            ]);

        $breadcrumb = $response->json('data');

        $this->assertCount(2, $breadcrumb);
        $this->assertSame($root->id, $breadcrumb[0]['id']);
        $this->assertSame($child->id, $breadcrumb[1]['id']);
    }
}

