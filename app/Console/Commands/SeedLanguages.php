<?php

namespace App\Console\Commands;

use Database\Seeders\LanguageSeeder;
use Illuminate\Console\Command;

/**
 * Artisan command to seed languages.
 * 
 * Usage: php artisan languages:seed
 */
class SeedLanguages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languages:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed multi-language configuration (Lithuanian, English, Spanish, French, German)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding languages...');
        
        $seeder = new LanguageSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('✅ Language seeding completed!');
        
        return Command::SUCCESS;
    }
}
