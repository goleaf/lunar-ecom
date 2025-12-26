<?php

namespace App\Livewire\Frontend;

use App\Lunar\Products\ProductSEO;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductRelationService;
use App\Traits\ChecksCheckoutLock;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Lunar\Facades\CartSession;
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
            ->where('element_type', Product::class)
            ->firstOrFail();

        $this->product = Product::with([
            'variants.prices',
            'media',
            'collections',
            'associations.target',
            'tags',
            'urls',
            'reviews.customer',
            'digitalProduct',
        ])->findOrFail($url->element_id);

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
            'variantId' => ['required', 'exists:lunar_product_variants,id'],
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

        return view('livewire.frontend.product-show', [
            'description' => $description,
            'material' => $material,
            'weight' => $weight,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
        ]);
    }
}


