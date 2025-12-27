{{-- Category Filter Tree (for /products sidebar) --}}
@php
    /** @var \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $categories */
    /** @var int|null $selectedCategoryId */
    /** @var string $baseUrl */
    /** @var array $preserveQuery */
    /** @var int $level */

    $selectedCategoryId = $selectedCategoryId ?? null;
    $baseUrl = $baseUrl ?? route('frontend.products.index');
    $preserveQuery = $preserveQuery ?? [];
    $level = $level ?? 0;
@endphp

@if($categories->count() > 0)
    <ul class="{{ $level === 0 ? 'space-y-1' : 'space-y-1 mt-1 ml-3 border-l border-gray-100 pl-3' }}">
        @foreach($categories as $category)
            @php
                /** @var \App\Models\Category $category */
                $isSelected = (int) $category->id === (int) $selectedCategoryId;
                $href = $baseUrl . '?' . http_build_query(array_filter(array_merge(
                    $preserveQuery,
                    ['category_id' => $category->id]
                ), fn ($v) => $v !== null && $v !== ''));
            @endphp

            <li>
                <a href="{{ $href }}"
                   class="block rounded px-2 py-1 text-sm hover:bg-gray-100 {{ $isSelected ? 'bg-gray-100 font-semibold' : '' }}">
                    <span>{{ $category->getName() }}</span>
                    @if(($category->product_count ?? 0) > 0)
                        <span class="text-xs text-gray-500">({{ $category->product_count }})</span>
                    @endif
                </a>

                @if($category->relationLoaded('children') && $category->children->count() > 0)
                    @include('frontend.components.category-filter-tree', [
                        'categories' => $category->children,
                        'selectedCategoryId' => $selectedCategoryId,
                        'baseUrl' => $baseUrl,
                        'preserveQuery' => $preserveQuery,
                        'level' => $level + 1,
                    ])
                @endif
            </li>
        @endforeach
    </ul>
@endif




