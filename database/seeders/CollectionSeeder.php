<?php

namespace Database\Seeders;

use App\Models\Collection;
use Illuminate\Database\Seeder;
use Lunar\Models\CollectionGroup;

class CollectionSeeder extends Seeder
{
    /**
     * Seed collections.
     * 
     * Creates collections organized by collection groups.
     */
    public function run(): void
    {
        $this->command->info('Seeding collections...');

        // Get or create collection group
        $collectionGroup = CollectionGroup::firstOrCreate(
            ['handle' => 'default'],
            [
                'name' => [
                    'en' => 'Default',
                ],
            ]
        );

        // Create collections
        $collections = Collection::factory()
            ->count(10)
            ->create([
                'collection_group_id' => $collectionGroup->id,
            ]);

        $this->command->info("Created {$collections->count()} collections.");
    }
}

