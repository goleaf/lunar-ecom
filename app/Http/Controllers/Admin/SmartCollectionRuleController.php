<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\SmartCollectionRuleResource;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\SmartCollectionRule;
use App\Services\SmartCollectionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SmartCollectionRuleController extends Controller
{
    protected SmartCollectionService $smartCollectionService;

    public function __construct(SmartCollectionService $smartCollectionService)
    {
        $this->smartCollectionService = $smartCollectionService;
    }

    /**
     * Show the smart collection rules page.
     *
     * @param Collection $collection
     * @return RedirectResponse
     */
    public function index(Collection $collection): RedirectResponse
    {
        // Prefer Filament for the admin UI.
        $slug = SmartCollectionRuleResource::getSlug();

        return redirect()->route(
            "filament.admin.resources.{$slug}.index",
            [
                // Pre-apply the collection filter in the table.
                'tableFilters' => [
                    'collection_id' => [
                        'value' => $collection->getKey(),
                    ],
                ],
            ]
        );
    }

    /**
     * Store a new rule.
     *
     * @param Request $request
     * @param Collection $collection
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Collection $collection)
    {
        $validated = $request->validate([
            'field' => 'required|string',
            'operator' => 'required|string',
            'value' => 'nullable',
            'logic_group' => 'nullable|string',
            'group_operator' => 'nullable|in:and,or',
            'priority' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Process value based on field type
        $validated['value'] = $this->processValue($validated['field'], $request->get('value'));

        $collection->smartRules()->create($validated);

        $this->notify('Rule created successfully.');

        return redirect()->route('admin.collections.smart-rules', $collection);
    }

    /**
     * Update a rule.
     *
     * @param Request $request
     * @param Collection $collection
     * @param SmartCollectionRule $rule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Collection $collection, SmartCollectionRule $rule)
    {
        $validated = $request->validate([
            'field' => 'required|string',
            'operator' => 'required|string',
            'value' => 'nullable',
            'logic_group' => 'nullable|string',
            'group_operator' => 'nullable|in:and,or',
            'priority' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Process value based on field type
        $validated['value'] = $this->processValue($validated['field'], $request->get('value') ?? $request->get('values', []));

        $rule->update($validated);

        $this->notify('Rule updated successfully.');

        return redirect()->route('admin.collections.smart-rules', $collection);
    }

    /**
     * Delete a rule.
     *
     * @param Collection $collection
     * @param SmartCollectionRule $rule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Collection $collection, SmartCollectionRule $rule)
    {
        $rule->delete();

        $this->notify('Rule deleted successfully.');

        return redirect()->route('admin.collections.smart-rules', $collection);
    }

    /**
     * Test rules and preview results.
     *
     * @param Request $request
     * @param Collection $collection
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request, Collection $collection)
    {
        $rules = $collection->smartRules()->active()->orderBy('priority')->get();
        
        if ($rules->isEmpty()) {
            return response()->json([
                'count' => 0,
                'products' => [],
            ]);
        }

        $query = \App\Models\Product::published();
        $query = $this->smartCollectionService->applyRules($query, $rules);
        
        $count = $query->count();
        $products = $query->limit(10)->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->translateAttribute('name'),
                'sku' => $product->sku,
            ];
        });

        return response()->json([
            'count' => $count,
            'products' => $products,
        ]);
    }

    /**
     * Process smart collection immediately.
     *
     * @param Collection $collection
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(Collection $collection)
    {
        try {
            $count = $this->smartCollectionService->processSmartCollection($collection);
            $this->notify("Smart collection processed. {$count} products assigned.");
        } catch (\Exception $e) {
            $this->notify('Failed to process smart collection: ' . $e->getMessage(), 'error');
        }

        return redirect()->route('admin.collections.smart-rules', $collection);
    }

    /**
     * Lightweight notification helper (replaces legacy Lunar Hub Notifies trait).
     */
    protected function notify(string $message, string $type = 'success'): void
    {
        $key = $type === 'error' ? 'error' : 'success';
        session()->flash($key, $message);
    }

    /**
     * Process value based on field type.
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    protected function processValue(string $field, $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Handle array values
        if (is_array($value)) {
            return array_filter($value);
        }

        // Handle JSON strings
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Handle between operator (range values)
        if (str_contains($field, '_') && in_array($field, ['price', 'rating', 'created_at', 'updated_at'])) {
            if (is_string($value) && str_contains($value, ',')) {
                $parts = explode(',', $value);
                if ($field === 'price' || $field === 'rating') {
                    return ['min' => (float) $parts[0], 'max' => (float) $parts[1]];
                } else {
                    return ['from' => $parts[0], 'to' => $parts[1]];
                }
            }
        }

        return $value;
    }
}
