<?php

namespace App\Console\Commands;

use App\Services\CollectionService;
use App\Services\CollectionGroupService;
use App\Models\Collection;
use Lunar\Models\CollectionGroup;
use Illuminate\Console\Command;

class ManageCollections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lunar:collections {action} {--group=} {--collection=} {--name=} {--handle=} {--products=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage product collections and collection groups';

    public function __construct(
        protected CollectionService $collectionService,
        protected CollectionGroupService $groupService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        match ($action) {
            'create-group' => $this->createGroup(),
            'create-collection' => $this->createCollection(),
            'list-groups' => $this->listGroups(),
            'list-collections' => $this->listCollections(),
            'add-products' => $this->addProducts(),
            'collection-stats' => $this->showCollectionStats(),
            'collection-tree' => $this->showCollectionTree(),
            default => $this->error("Unknown action: {$action}")
        };
    }

    protected function createGroup(): void
    {
        $name = $this->option('name') ?: $this->ask('Enter collection group name');
        $handle = $this->option('handle') ?: $this->ask('Enter collection group handle');

        try {
            $group = $this->groupService->createCollectionGroup([
                'name' => $name,
                'handle' => $handle,
            ]);

            $this->info("Collection group created successfully with ID: {$group->id}");
        } catch (\Exception $e) {
            $this->error("Failed to create collection group: {$e->getMessage()}");
        }
    }

    protected function createCollection(): void
    {
        $groupId = $this->option('group');
        if (!$groupId) {
            $this->listGroups();
            $groupId = $this->ask('Enter collection group ID');
        }

        $group = CollectionGroup::find($groupId);
        if (!$group) {
            $this->error("Collection group with ID {$groupId} not found");
            return;
        }

        try {
            $collection = $this->collectionService->createCollection([
                'collection_group_id' => $group->id,
                'sort' => 0,
            ]);

            $this->info("Collection created successfully with ID: {$collection->id}");
        } catch (\Exception $e) {
            $this->error("Failed to create collection: {$e->getMessage()}");
        }
    }

    protected function listGroups(): void
    {
        $groups = $this->groupService->getAllGroups();

        if ($groups->isEmpty()) {
            $this->info('No collection groups found');
            return;
        }

        $this->info('Collection Groups:');
        $this->table(
            ['ID', 'Name', 'Handle', 'Collections Count'],
            $groups->map(function ($group) {
                return [
                    $group->id,
                    $group->name,
                    $group->handle,
                    $group->collections->count(),
                ];
            })
        );
    }

    protected function listCollections(): void
    {
        $groupId = $this->option('group');
        
        if ($groupId) {
            $group = CollectionGroup::find($groupId);
            if (!$group) {
                $this->error("Collection group with ID {$groupId} not found");
                return;
            }
            $collections = $this->groupService->getCollectionsInGroup($group);
        } else {
            $collections = $this->collectionService->searchCollections([]);
        }

        if ($collections->isEmpty()) {
            $this->info('No collections found');
            return;
        }

        $this->info('Collections:');
        $this->table(
            ['ID', 'Group', 'Sort', 'Products Count'],
            $collections->map(function ($collection) {
                return [
                    $collection->id,
                    $collection->group->name ?? 'N/A',
                    $collection->sort,
                    $collection->products->count(),
                ];
            })
        );
    }

    protected function addProducts(): void
    {
        $collectionId = $this->option('collection');
        if (!$collectionId) {
            $this->listCollections();
            $collectionId = $this->ask('Enter collection ID');
        }

        $collection = Collection::find($collectionId);
        if (!$collection) {
            $this->error("Collection with ID {$collectionId} not found");
            return;
        }

        $productIds = $this->option('products');
        if (!$productIds) {
            $productIds = $this->ask('Enter product IDs (comma-separated)');
        }

        $productIds = array_map('trim', explode(',', $productIds));
        $productIds = array_map('intval', $productIds);

        try {
            $updatedCollection = $this->collectionService->addProducts($collection, $productIds);
            $this->info("Products added to collection successfully. Total products: {$updatedCollection->products->count()}");
        } catch (\Exception $e) {
            $this->error("Failed to add products: {$e->getMessage()}");
        }
    }

    protected function showCollectionStats(): void
    {
        $collectionId = $this->option('collection');
        if (!$collectionId) {
            $this->listCollections();
            $collectionId = $this->ask('Enter collection ID');
        }

        $collection = Collection::find($collectionId);
        if (!$collection) {
            $this->error("Collection with ID {$collectionId} not found");
            return;
        }

        $stats = $this->collectionService->getCollectionStats($collection);

        $this->info("Collection Statistics for Collection ID: {$collection->id}");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Products', $stats['total_products']],
                ['Published Products', $stats['published_products']],
                ['Draft Products', $stats['draft_products']],
                ['Total Variants', $stats['total_variants']],
                ['Total Stock', $stats['total_stock']],
                ['Child Collections', $stats['child_collections']],
            ]
        );
    }

    protected function showCollectionTree(): void
    {
        $groupId = $this->option('group');
        if (!$groupId) {
            $this->listGroups();
            $groupId = $this->ask('Enter collection group ID');
        }

        $tree = $this->collectionService->getCollectionTree((int) $groupId);

        if ($tree->isEmpty()) {
            $this->info('No collections found in this group');
            return;
        }

        $this->info("Collection Tree for Group ID: {$groupId}");
        $this->displayTree($tree);
    }

    protected function displayTree($collections, $indent = 0): void
    {
        foreach ($collections as $collection) {
            $prefix = str_repeat('  ', $indent) . '├─ ';
            $this->line($prefix . "ID: {$collection->id} (Products: {$collection->products->count()})");
            
            if ($collection->children->isNotEmpty()) {
                $this->displayTree($collection->children, $indent + 1);
            }
        }
    }
}
