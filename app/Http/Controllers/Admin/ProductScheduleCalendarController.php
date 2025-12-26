<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Controller for product schedule calendar view.
 */
class ProductScheduleCalendarController extends Controller
{
    /**
     * Display calendar view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.products.schedules.calendar');
    }

    /**
     * Get schedules for calendar view.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getSchedules(Request $request): JsonResponse
    {
        $start = Carbon::parse($request->input('start', now()->startOfMonth()));
        $end = Carbon::parse($request->input('end', now()->endOfMonth()));

        $schedules = ProductSchedule::with('product')
            ->whereBetween('scheduled_at', [$start, $end])
            ->orWhere(function ($q) use ($start, $end) {
                $q->whereBetween('expires_at', [$start, $end]);
            })
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->product->translateAttribute('name') . ' - ' . ucfirst($schedule->type),
                    'start' => $schedule->scheduled_at->toIso8601String(),
                    'end' => $schedule->expires_at?->toIso8601String() ?? $schedule->scheduled_at->toIso8601String(),
                    'color' => $this->getScheduleColor($schedule->type),
                    'type' => $schedule->type,
                    'product_id' => $schedule->product_id,
                    'url' => route('admin.products.schedules.show', $schedule->id),
                ];
            });

        return response()->json($schedules);
    }

    /**
     * Get schedule color based on type.
     */
    protected function getScheduleColor(string $type): string
    {
        return match ($type) {
            'publish' => '#10b981', // green
            'unpublish' => '#ef4444', // red
            'flash_sale' => '#f59e0b', // amber
            'seasonal' => '#3b82f6', // blue
            'time_limited' => '#8b5cf6', // purple
            default => '#6b7280', // gray
        };
    }
}


