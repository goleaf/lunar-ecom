<?php

namespace App\Services;

use Lunar\Models\CollectionGroup;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CollectionGroupService
{
    /**
     * Create a new collection group
     */
    public function createCollectionGroup(array $data): CollectionGroup
    {
        return CollectionGroup::create([
            'name' => $data['name'],
            'handle' => $data['handle'],
        ]);
    }

    /**
     * Get all collection groups with their collections
     */
    public function getAllGroups(): EloquentCollection
    {
        return CollectionGroup::with(['collections'])->get();
    }

    /**
     * Get collection group by handle
     */
    public function getGroupByHandle(string $handle): ?CollectionGroup
    {
        return CollectionGroup::where('handle', $handle)
            ->with(['collections'])
            ->first();
    }

    /**
     * Update collection group
     */
    public function updateCollectionGroup(CollectionGroup $group, array $data): CollectionGroup
    {
        $group->update([
            'name' => $data['name'] ?? $group->name,
            'handle' => $data['handle'] ?? $group->handle,
        ]);

        return $group->fresh();
    }

    /**
     * Delete collection group
     */
    public function deleteCollectionGroup(CollectionGroup $group): bool
    {
        // Check if group has collections
        if ($group->collections()->count() > 0) {
            throw new \Exception('Cannot delete collection group that has collections');
        }

        return $group->delete();
    }

    /**
     * Get collections in group
     */
    public function getCollectionsInGroup(CollectionGroup $group): EloquentCollection
    {
        return $group->collections()
            ->with(['products'])
            ->orderBy('sort')
            ->get();
    }

    /**
     * Reorder collections in group
     */
    public function reorderCollections(CollectionGroup $group, array $collectionOrder): void
    {
        foreach ($collectionOrder as $index => $collectionId) {
            $group->collections()
                ->where('id', $collectionId)
                ->update(['sort' => $index + 1]);
        }
    }
}