<?php

namespace App\Console\Commands;

use Database\Seeders\CurrencySeeder;
use Illuminate\Console\Command;

/**
 * Artisan command to seed currencies.
 * 
 * Usage: php artisan currencies:seed
 */
class SeedCurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currencies:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed multi-currency configuration (USD, EUR, GBP, JPY, AUD)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding currencies...');
        
        $seeder = new CurrencySeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('✅ Currency seeding completed!');
        
        return Command::SUCCESS;
    }
}

