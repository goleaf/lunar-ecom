<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductComingSoonTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_coming_soon_returns_true_when_flag_is_set(): void
    {
        $product = Product::factory()->create([
            'is_coming_soon' => true,
            'expected_available_at' => null,
        ]);

        $this->assertTrue($product->isComingSoon());
    }

    public function test_is_coming_soon_returns_true_when_expected_available_at_is_in_future(): void
    {
        $product = Product::factory()->create([
            'is_coming_soon' => false,
            'expected_available_at' => now()->addDays(3),
        ]);

        $this->assertTrue($product->isComingSoon());
    }

    public function test_is_coming_soon_returns_false_when_not_marked_coming_soon_and_no_future_date(): void
    {
        $product = Product::factory()->create([
            'is_coming_soon' => false,
            'expected_available_at' => null,
        ]);

        $this->assertFalse($product->isComingSoon());
    }
}

