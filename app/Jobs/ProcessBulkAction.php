<?php

namespace App\Jobs;

use App\Models\ProductBulkAction;
use App\Services\ProductBulkActionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to process bulk actions on products.
 */
class ProcessBulkAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  ProductBulkAction  $bulkAction
     */
    public function __construct(
        public ProductBulkAction $bulkAction
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ProductBulkActionService $service): void
    {
        $service->processBulkAction($this->bulkAction);
    }
}

