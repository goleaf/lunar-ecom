<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductSchedule;
use App\Services\ProductSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Lunar\Models\Product;
use Lunar\Models\Collection;

/**
 * Controller for bulk scheduling operations.
 */
class BulkScheduleController extends Controller
{
    public function __construct(
        protected ProductSchedulingService $schedulingService
    ) {}

    /**
     * Create bulk schedule.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:publish,unpublish,flash_sale,seasonal,time_limited',
            'scheduled_at' => 'required|date|after:now',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'target_status' => 'nullable|string',
            'timezone' => 'nullable|string',
            'product_ids' => 'nullable|array',
            'category_ids' => 'nullable|array',
            'tag_ids' => 'nullable|array',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        try {
            $bulkScheduleId = Str::uuid()->toString();
            $products = $this->getProductsForBulkSchedule($validated);
            $created = 0;

            foreach ($products as $product) {
                $scheduleData = array_merge($validated, [
                    'bulk_schedule_id' => $bulkScheduleId,
                    'applied_to' => [
                        'product_ids' => $validated['product_ids'] ?? [],
                        'category_ids' => $validated['category_ids'] ?? [],
                        'tag_ids' => $validated['tag_ids'] ?? [],
                    ],
                ]);

                $this->schedulingService->createSchedule($product, $scheduleData);
                $created++;
            }

            return response()->json([
                'success' => true,
                'message' => "Bulk schedule created for {$created} products",
                'bulk_schedule_id' => $bulkScheduleId,
                'created_count' => $created,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bulk schedule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get products for bulk schedule.
     */
    protected function getProductsForBulkSchedule(array $data): \Illuminate\Database\Eloquent\Collection
    {
        $query = Product::query();

        if (!empty($data['product_ids'])) {
            $query->whereIn('id', $data['product_ids']);
        }

        if (!empty($data['category_ids'])) {
            $query->orWhereHas('collections', function ($q) use ($data) {
                $q->whereIn('id', $data['category_ids']);
            });
        }

        if (!empty($data['tag_ids'])) {
            $query->orWhereHas('tags', function ($q) use ($data) {
                $q->whereIn('id', $data['tag_ids']);
            });
        }

        return $query->get();
    }
}


