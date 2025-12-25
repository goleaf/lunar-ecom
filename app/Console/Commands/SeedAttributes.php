<?php

namespace App\Console\Commands;

use Database\Seeders\AttributeSeeder;
use Illuminate\Console\Command;

class SeedAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lunar:seed-attributes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed common product attributes (color, size, material, features)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding product attributes...');

        $seeder = new AttributeSeeder();
        $seeder->run();

        $this->info('Product attributes seeded successfully!');

        return Command::SUCCESS;
    }
}

