<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\CustomerGroupFactory;
use Lunar\Models\CustomerGroup;

class CustomerGroupSeeder extends Seeder
{
    public const DEFAULT_HANDLE = 'default';

    /**
     * Canonical customer groups for local/dev/demo environments.
     *
     * Notes:
     * - Keep exactly ONE default group (handle = "default") to align with factories and pricing logic.
     * - Handles are used in pricing endpoints (e.g. `?customer_group=wholesale`).
     */
    public const GROUPS = [
        ['handle' => 'default', 'name' => 'Default', 'default' => true],
        ['handle' => 'retail', 'name' => 'Retail', 'default' => false],
        ['handle' => 'wholesale', 'name' => 'Wholesale', 'default' => false],
        ['handle' => 'trade', 'name' => 'Trade', 'default' => false],
        ['handle' => 'vip', 'name' => 'VIP', 'default' => false],
    ];

    public function run(): void
    {
        $this->command?->info('Seeding customer groups...');

        self::seed();

        $this->command?->info('Customer groups seeded.');
    }

    /**
     * Idempotently seed customer groups and return them keyed by handle.
     *
     * @return array<string, \Lunar\Models\CustomerGroup>
     */
    public static function seed(): array
    {
        // Ensure there's never more than one default group.
        CustomerGroup::query()->update(['default' => false]);

        $groupsByHandle = [];

        foreach (self::GROUPS as $group) {
            $handle = Arr::get($group, 'handle');
            $data = Arr::only($group, ['handle', 'name', 'default']);

            $factoryData = CustomerGroupFactory::new()
                ->state($data)
                ->make()
                ->getAttributes();

            $model = CustomerGroup::updateOrCreate(
                ['handle' => $handle],
                Arr::only($factoryData, ['name', 'default'])
            );

            $groupsByHandle[$handle] = $model;
        }

        // Safety: enforce only the declared default as default.
        CustomerGroup::query()
            ->where('handle', '!=', self::DEFAULT_HANDLE)
            ->update(['default' => false]);

        CustomerGroup::query()
            ->where('handle', self::DEFAULT_HANDLE)
            ->update(['default' => true]);

        // Refresh models after updates.
        foreach ($groupsByHandle as $handle => $model) {
            $groupsByHandle[$handle] = $model->refresh();
        }

        return $groupsByHandle;
    }
}

