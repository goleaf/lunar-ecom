{{-- Category Tree Navigation Component --}}
@php
    use App\Lunar\Categories\CategoryHelper;
    $categories = CategoryHelper::getNavigation($maxDepth ?? 3);
@endphp

@if($categories->count() > 0)
    <nav class="category-tree" aria-label="Category Navigation">
        <ul class="space-y-2">
            @foreach($categories as $category)
                <li>
                    <a href="{{ $category['url'] }}" 
                       class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->is('categories/' . $category['slug'] . '*') ? 'bg-gray-100 font-semibold' : '' }}">
                        <div class="flex items-center">
                            @if($category['image_url'])
                                <img src="{{ $category['image_url'] }}" 
                                     alt="{{ $category['name'] }}" 
                                     class="w-8 h-8 object-cover rounded mr-2">
                            @endif
                            <span>{{ $category['name'] }}</span>
                            @if($category['product_count'] > 0)
                                <span class="ml-auto text-sm text-gray-500">({{ $category['product_count'] }})</span>
                            @endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif

