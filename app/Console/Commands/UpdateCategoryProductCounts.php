<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

/**
 * Command to update product counts for all categories.
 */
class UpdateCategoryProductCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:update-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update product counts for all categories';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating category product counts...');

        $categories = Category::all();
        $bar = $this->output->createProgressBar($categories->count());
        $bar->start();

        foreach ($categories as $category) {
            $category->updateProductCount();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Product counts updated successfully!');

        return Command::SUCCESS;
    }
}

