<?php

namespace App\Livewire\Storefront;

use App\Services\SearchService;
use Livewire\Component;

class SearchMegaMenu extends Component
{
    public string $query = '';

    public bool $open = false;

    /**
     * Flat index into selectable items (across all groups).
     * -1 means nothing selected.
     */
    public int $activeIndex = -1;

    /**
     * Grouped suggestion items for rendering.
     *
     * Each item shape:
     * - key: string (unique across all items)
     * - index: int (flat index for keyboard nav)
     * - title: string
     * - url: string
     * - image_url?: string|null
     * - subtitle?: string|null
     */
    public array $groups = [
        'categories' => [],
        'brands' => [],
        'products' => [],
    ];

    /** @var array<int, string> */
    public array $history = [];

    /** @var array<int, string> */
    public array $popular = [];

    /** @var array<int, string> */
    public array $flatKeys = [];

    public function mount(): void
    {
        $service = app(SearchService::class);
        $this->history = $service->getSearchHistory(5)->values()->all();
        $this->popular = $service->popularSearches(5)->pluck('search_term')->values()->all();
    }

    public function openDropdown(): void
    {
        $this->open = true;
        $this->refresh();
    }

    public function close(): void
    {
        $this->open = false;
        $this->activeIndex = -1;
    }

    public function updatedQuery(): void
    {
        $this->open = true;
        $this->activeIndex = -1;
        $this->refresh();
    }

    public function refresh(): void
    {
        $q = trim($this->query);

        if (mb_strlen($q) < 2) {
            $this->groups = [
                'categories' => [],
                'brands' => [],
                'products' => [],
            ];
            $this->flatKeys = [];
            return;
        }

        $service = app(SearchService::class);
        $raw = $service->megaMenuAutocomplete($q, [
            'categories' => 5,
            'brands' => 5,
            'products' => 6,
        ]);

        // Build flat index ordering: Search -> Categories -> Brands -> Products.
        $index = 0;
        $groups = [
            'categories' => [],
            'brands' => [],
            'products' => [],
        ];
        $flatKeys = [];

        foreach (['categories', 'brands', 'products'] as $groupName) {
            foreach (($raw[$groupName] ?? []) as $item) {
                $item['index'] = $index;
                $groups[$groupName][] = $item;
                $flatKeys[] = $item['key'];
                $index++;
            }
        }

        // Append a "View all results" option at the end.
        $viewAllKey = 'search:all';
        $flatKeys[] = $viewAllKey;

        $this->groups = $groups;
        $this->flatKeys = $flatKeys;
    }

    public function next(): void
    {
        if (empty($this->flatKeys)) {
            return;
        }

        $this->activeIndex = min($this->activeIndex + 1, count($this->flatKeys) - 1);
    }

    public function prev(): void
    {
        if (empty($this->flatKeys)) {
            return;
        }

        $this->activeIndex = max($this->activeIndex - 1, 0);
    }

    public function setActiveIndex(int $index): void
    {
        if ($index < 0 || $index >= count($this->flatKeys)) {
            return;
        }

        $this->activeIndex = $index;
    }

    public function submit(): mixed
    {
        $q = trim($this->query);
        if ($q === '') {
            return null;
        }

        $this->close();

        return redirect()->route('storefront.search.index', ['q' => $q]);
    }

    public function goActive(): mixed
    {
        $q = trim($this->query);
        if ($q === '') {
            return null;
        }

        $activeKey = $this->flatKeys[$this->activeIndex] ?? null;

        // "View all results" option (always last).
        if ($activeKey === 'search:all' || $activeKey === null) {
            return $this->submit();
        }

        foreach (['categories', 'brands', 'products'] as $groupName) {
            foreach ($this->groups[$groupName] as $item) {
                if (($item['key'] ?? null) === $activeKey && !empty($item['url'])) {
                    $this->close();
                    return redirect()->to($item['url']);
                }
            }
        }

        return $this->submit();
    }

    public function selectHistory(string $term): mixed
    {
        $this->query = $term;
        return $this->submit();
    }

    public function selectPopular(string $term): mixed
    {
        $this->query = $term;
        return $this->submit();
    }

    public function render()
    {
        return view('livewire.storefront.search-mega-menu');
    }
}


