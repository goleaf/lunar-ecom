<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\SearchSynonymResource;
use App\Filament\Resources\SearchSynonymResource\Pages\CreateSearchSynonym;
use App\Models\SearchSynonym;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSearchSynonymResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_synonym_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $synonym = SearchSynonym::factory()->create();

        $slug = SearchSynonymResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $synonym->getKey(),
        ]))->assertOk();
    }

    public function test_search_synonym_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $term = 'term-' . Str::lower(Str::random(10));

        Livewire::test(CreateSearchSynonym::class)
            ->set('data.term', $term)
            ->set('data.synonyms', ['foo', 'bar'])
            ->set('data.is_active', true)
            ->set('data.priority', 10)
            ->call('create');

        $this->assertDatabaseHas((new SearchSynonym())->getTable(), [
            'term' => $term,
        ]);
    }
}

