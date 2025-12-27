<?php

namespace App\Livewire\Frontend;

use App\Lunar\Products\ProductSEO;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductRelationService;
use App\Traits\ChecksCheckoutLock;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Language;
use Lunar\Models\Url;

class ProductShow extends Component
{
    use AuthorizesRequests;
    use ChecksCheckoutLock;

    public Product $product;

    public int $quantity = 1;

    public ?int $variantId = null;

    public array $metaTags = [];

    public array $structuredData = [];

    public string $robotsMeta = '';

    public $crossSell;

    public $upSell;

    public $alternate;

    public $accessories;

    public $related;

    public $customersAlsoBought;

    public string $message = '';

    public string $messageType = 'success';

    public function mount(string $slug): void
    {
        $url = Url::where('slug', $slug)
            ->whereIn('element_type', [Product::morphName(), Product::class])
            ->first();

        $productId = $url?->element_id;

        if (! $productId && ctype_digit($slug)) {
            $productId = (int) $slug;
            $canonicalSlug = $this->resolveCanonicalSlug($productId);

            if ($canonicalSlug && $canonicalSlug !== $slug) {
                $this->redirectRoute(
                    'frontend.products.show',
                    array_merge(['slug' => $canonicalSlug], request()->query()),
                    navigate: true
                );
                return;
            }
        }

        if (! $productId) {
            abort(404);
        }

        $this->product = Product::with([
            'variants.prices',
            'media',
            'collections',
            'associations.target',
            'tags',
            'urls',
            'reviews.customer',
            'digitalProduct',
        ])->findOrFail($productId);

        $this->authorize('view', $this->product);

        $relationService = app(ProductRelationService::class);
        $this->crossSell = $relationService->getCrossSell($this->product, 10);
        $this->upSell = $relationService->getUpSell($this->product, 10);
        $this->alternate = $relationService->getReplacements($this->product, 10);
        $this->accessories = $relationService->getAccessories($this->product, 10);
        $this->related = $relationService->getRelated($this->product, 10);
        $this->customersAlsoBought = $relationService->getCustomersAlsoBought($this->product, 10);

        $this->metaTags = ProductSEO::getMetaTags($this->product);
        $this->structuredData = ProductSEO::getStructuredData($this->product);
        $this->robotsMeta = ProductSEO::getRobotsMeta($this->product);

        $this->variantId = $this->product->variants->first()?->id;
    }

    public function addToCart(): void
    {
        $this->message = '';
        $this->messageType = 'success';

        $this->validate([
            'variantId' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $variant = ProductVariant::findOrFail($this->variantId);
        $this->authorize('view', $variant);

        try {
            $this->ensureCartNotLocked();

            CartSession::add($variant, $this->quantity);
            $this->dispatch('cartUpdated');

            $this->message = 'Item added to cart.';
            $this->messageType = 'success';
        } catch (\Throwable $e) {
            $this->message = $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function render()
    {
        $description = $this->product->translateAttribute('description');
        $material = $this->product->translateAttribute('material');
        $weight = $this->product->translateAttribute('weight');
        $metaTitle = $this->product->translateAttribute('meta_title');
        $metaDescription = $this->product->translateAttribute('meta_description');

        $pageMeta = new HtmlString(view('frontend.products._meta-show', [
            'metaTags' => $this->metaTags,
            'robotsMeta' => $this->robotsMeta,
            'structuredData' => $this->structuredData,
        ])->render());

        return view('livewire.frontend.product-show', [
            'description' => $description,
            'material' => $material,
            'weight' => $weight,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
        ])->layout('frontend.layout', [
            'pageTitle' => $metaTitle ?: ($this->metaTags['title'] ?? $this->product->translateAttribute('name')),
            'pageMeta' => $pageMeta,
        ]);
    }

    private function resolveCanonicalSlug(int $productId): ?string
    {
        $language = Language::where('code', app()->getLocale())->first()
            ?? Language::getDefault();

        $query = Url::whereIn('element_type', [Product::morphName(), Product::class])
            ->where('element_id', $productId);

        if ($language) {
            $query->where('language_id', $language->id);
        }

        $url = $query->orderByDesc('default')->first();

        if (! $url && $language) {
            $url = Url::whereIn('element_type', [Product::morphName(), Product::class])
                ->where('element_id', $productId)
                ->orderByDesc('default')
                ->first();
        }

        return $url?->slug;
    }
}
