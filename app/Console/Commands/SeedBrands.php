<?php

namespace App\Console\Commands;

use Database\Seeders\BrandSeeder;
use Illuminate\Console\Command;

/**
 * Artisan command to seed brands.
 * 
 * Usage: php artisan brands:seed
 */
class SeedBrands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brands:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed sample brands with descriptions and logo images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding brands...');
        
        $seeder = new BrandSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('✅ Brand seeding completed!');
        
        return Command::SUCCESS;
    }
}

