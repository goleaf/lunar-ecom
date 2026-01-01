<?php

namespace App\Livewire\Frontend\Pages;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Livewire\Component;

class SearchIndex extends Component
{
    public function render()
    {
        $request = request();
        $query = (string) $request->get('q', '');

        if ($query === '') {
            return view('frontend.search.index', [
                'products' => collect(),
                'query' => '',
                'facets' => [],
            ]);
        }

        $filters = $this->parseFilters($request);
        $result = app(SearchService::class)->searchWithFilters($query, $filters, [
            'per_page' => $request->get('per_page', 24),
            'page' => $request->get('page', 1),
            'sort' => $request->get('sort', 'relevance'),
        ]);

        return view('frontend.search.index', [
            'products' => $result['results'],
            'query' => $query,
            'facets' => $result['facets'],
            'filters' => $filters,
        ]);
    }

    protected function parseFilters(Request $request): array
    {
        $filters = [];

        if ($request->has('category_id')) {
            $filters['category_ids'] = (array) $request->get('category_id');
        }

        if ($request->has('brand_id')) {
            $filters['brand_id'] = $request->get('brand_id');
        }

        if ($request->has('price_min')) {
            $filters['price_min'] = (int) ($request->get('price_min') * 100);
        }
        if ($request->has('price_max')) {
            $filters['price_max'] = (int) ($request->get('price_max') * 100);
        }

        if ($request->has('in_stock')) {
            $filters['in_stock'] = filter_var($request->get('in_stock'), FILTER_VALIDATE_BOOLEAN);
        }

        return $filters;
    }
}


