<?php

namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    public const TYPES = [
        'simple',
        'configurable',
        'bundle',
        'digital',
        'service',
    ];

    /**
     * Seed required product types and return them keyed by name.
     *
     * @return array<string, \App\Models\ProductType>
     */
    public static function seed(): array
    {
        $types = [];
        foreach (self::TYPES as $name) {
            $types[$name] = ProductType::firstOrCreate(['name' => $name]);
        }

        return $types;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        self::seed();
    }
}
