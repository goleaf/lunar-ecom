<?php

namespace App\Console\Commands;

use App\Services\AbandonedCartService;
use Lunar\Models\Cart;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * Command to process abandoned carts.
 */
class ProcessAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:process-abandoned-carts 
                            {--hours=24 : Hours since last update to consider abandoned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Track abandoned carts from inactive carts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(AbandonedCartService $service): int
    {
        $hours = (int) $this->option('hours');
        $cutoffTime = now()->subHours($hours);
        
        $this->info("Processing abandoned carts (inactive for {$hours} hours)...");
        
        // Find carts that haven't been updated recently and have items
        $carts = Cart::where('updated_at', '<=', $cutoffTime)
            ->whereDoesntHave('order')
            ->whereHas('lines')
            ->get();
        
        $bar = $this->output->createProgressBar($carts->count());
        $bar->start();
        
        $tracked = 0;
        foreach ($carts as $cart) {
            try {
                $service->trackAbandonedCart($cart);
                $tracked++;
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError tracking cart {$cart->id}: " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Tracked {$tracked} abandoned carts.");
        
        return Command::SUCCESS;
    }
}

