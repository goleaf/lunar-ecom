<?php

namespace App\Services;

use App\Models\GdprRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use Lunar\Models\Address;
use Carbon\Carbon;

class GdprDataExportService
{
    /**
     * Export all user data in JSON format
     */
    public function exportUserData(User $user, GdprRequest $request): string
    {
        $data = [
            'export_date' => now()->toIso8601String(),
            'user_id' => $user->id,
            'user' => $this->getUserData($user),
            'customer' => $this->getCustomerData($user),
            'orders' => $this->getOrdersData($user),
            'addresses' => $this->getAddressesData($user),
            'cart_history' => $this->getCartHistory($user),
            'consents' => $this->getConsentsData($user),
            'reviews' => $this->getReviewsData($user),
            'search_history' => $this->getSearchHistory($user),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'gdpr_export_' . $user->id . '_' . now()->format('Y-m-d_His') . '.json';
        $path = 'gdpr/exports/' . $filename;

        Storage::put($path, $json);

        $request->addLog('Data export file created', ['filename' => $filename, 'path' => $path]);

        return $path;
    }

    /**
     * Export customer data (when no user account exists)
     */
    public function exportCustomerData(Customer $customer, GdprRequest $request): string
    {
        $data = [
            'export_date' => now()->toIso8601String(),
            'customer_id' => $customer->id,
            'customer' => $this->getCustomerDataById($customer),
            'orders' => $this->getOrdersDataByCustomer($customer),
            'addresses' => $this->getAddressesDataByCustomer($customer),
            'consents' => $this->getConsentsDataByCustomer($customer),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'gdpr_export_customer_' . $customer->id . '_' . now()->format('Y-m-d_His') . '.json';
        $path = 'gdpr/exports/' . $filename;

        Storage::put($path, $json);

        $request->addLog('Customer data export file created', ['filename' => $filename, 'path' => $path]);

        return $path;
    }

    protected function getUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(),
            'updated_at' => $user->updated_at->toIso8601String(),
        ];
    }

    protected function getCustomerData(User $user): ?array
    {
        $customer = $user->customers()->first();
        
        if (!$customer) {
            return null;
        }

        return $this->getCustomerDataById($customer);
    }

    protected function getCustomerDataById(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'title' => $customer->title,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'company_name' => $customer->company_name,
            'vat_no' => $customer->vat_no,
            'meta' => $customer->meta,
            'created_at' => $customer->created_at->toIso8601String(),
            'updated_at' => $customer->updated_at->toIso8601String(),
        ];
    }

    protected function getOrdersData(User $user): array
    {
        $customer = $user->customers()->first();
        
        if (!$customer) {
            return [];
        }

        return $this->getOrdersDataByCustomer($customer);
    }

    protected function getOrdersDataByCustomer(Customer $customer): array
    {
        $orders = Order::where('customer_id', $customer->id)
            ->with(['lines', 'addresses', 'payments'])
            ->get();

        return $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'reference' => $order->reference,
                'status' => $order->status,
                'sub_total' => $order->sub_total,
                'discount_total' => $order->discount_total,
                'shipping_total' => $order->shipping_total,
                'tax_total' => $order->tax_total,
                'total' => $order->total,
                'currency_code' => $order->currency_code,
                'placed_at' => $order->placed_at?->toIso8601String(),
                'notes' => $order->notes,
                'lines' => $order->lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'description' => $line->description,
                        'quantity' => $line->quantity,
                        'unit_price' => $line->unit_price,
                        'sub_total' => $line->sub_total,
                        'discount_total' => $line->discount_total,
                        'tax_total' => $line->tax_total,
                        'total' => $line->total,
                    ];
                }),
                'addresses' => $order->addresses->map(function ($address) {
                    return [
                        'type' => $address->type,
                        'first_name' => $address->first_name,
                        'last_name' => $address->last_name,
                        'company_name' => $address->company_name,
                        'line_one' => $address->line_one,
                        'line_two' => $address->line_two,
                        'city' => $address->city,
                        'state' => $address->state,
                        'postcode' => $address->postcode,
                        'country_id' => $address->country_id,
                        'contact_email' => $address->contact_email,
                        'contact_phone' => $address->contact_phone,
                    ];
                }),
                'created_at' => $order->created_at->toIso8601String(),
                'updated_at' => $order->updated_at->toIso8601String(),
            ];
        })->toArray();
    }

    protected function getAddressesData(User $user): array
    {
        $customer = $user->customers()->first();
        
        if (!$customer) {
            return [];
        }

        return $this->getAddressesDataByCustomer($customer);
    }

    protected function getAddressesDataByCustomer(Customer $customer): array
    {
        $addresses = Address::where('customer_id', $customer->id)->get();

        return $addresses->map(function ($address) {
            return [
                'id' => $address->id,
                'title' => $address->title,
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'company_name' => $address->company_name,
                'line_one' => $address->line_one,
                'line_two' => $address->line_two,
                'line_three' => $address->line_three,
                'city' => $address->city,
                'state' => $address->state,
                'postcode' => $address->postcode,
                'country_id' => $address->country_id,
                'contact_email' => $address->contact_email,
                'contact_phone' => $address->contact_phone,
                'shipping_default' => $address->shipping_default,
                'billing_default' => $address->billing_default,
                'created_at' => $address->created_at->toIso8601String(),
                'updated_at' => $address->updated_at->toIso8601String(),
            ];
        })->toArray();
    }

    protected function getCartHistory(User $user): array
    {
        // Get cart history if available
        return DB::table('lunar_carts')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(['id', 'currency_id', 'meta', 'created_at', 'updated_at'])
            ->map(function ($cart) {
                return [
                    'id' => $cart->id,
                    'currency_id' => $cart->currency_id,
                    'meta' => json_decode($cart->meta, true),
                    'created_at' => Carbon::parse($cart->created_at)->toIso8601String(),
                    'updated_at' => Carbon::parse($cart->updated_at)->toIso8601String(),
                ];
            })
            ->toArray();
    }

    protected function getConsentsData(User $user): array
    {
        return [
            'cookie_consents' => \App\Models\CookieConsent::where('user_id', $user->id)
                ->get()
                ->map(function ($consent) {
                    return [
                        'necessary' => $consent->necessary,
                        'analytics' => $consent->analytics,
                        'marketing' => $consent->marketing,
                        'preferences' => $consent->preferences,
                        'consented_at' => $consent->consented_at?->toIso8601String(),
                    ];
                }),
            'consent_tracking' => \App\Models\ConsentTracking::where('user_id', $user->id)
                ->get()
                ->map(function ($tracking) {
                    return [
                        'consent_type' => $tracking->consent_type,
                        'purpose' => $tracking->purpose,
                        'consented' => $tracking->consented,
                        'consented_at' => $tracking->consented_at?->toIso8601String(),
                        'withdrawn_at' => $tracking->withdrawn_at?->toIso8601String(),
                    ];
                }),
        ];
    }

    protected function getConsentsDataByCustomer(Customer $customer): array
    {
        return [
            'cookie_consents' => \App\Models\CookieConsent::where('customer_id', $customer->id)
                ->get()
                ->map(function ($consent) {
                    return [
                        'necessary' => $consent->necessary,
                        'analytics' => $consent->analytics,
                        'marketing' => $consent->marketing,
                        'preferences' => $consent->preferences,
                        'consented_at' => $consent->consented_at?->toIso8601String(),
                    ];
                }),
            'consent_tracking' => \App\Models\ConsentTracking::where('customer_id', $customer->id)
                ->get()
                ->map(function ($tracking) {
                    return [
                        'consent_type' => $tracking->consent_type,
                        'purpose' => $tracking->purpose,
                        'consented' => $tracking->consented,
                        'consented_at' => $tracking->consented_at?->toIso8601String(),
                        'withdrawn_at' => $tracking->withdrawn_at?->toIso8601String(),
                    ];
                }),
        ];
    }

    protected function getReviewsData(User $user): array
    {
        if (!class_exists(\App\Models\Review::class)) {
            return [];
        }

        return \App\Models\Review::where('user_id', $user->id)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'product_id' => $review->product_id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'content' => $review->content,
                    'created_at' => $review->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    protected function getSearchHistory(User $user): array
    {
        if (!class_exists(\App\Models\SearchAnalytic::class)) {
            return [];
        }

        return \App\Models\SearchAnalytic::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(['query', 'results_count', 'created_at'])
            ->map(function ($search) {
                return [
                    'query' => $search->query,
                    'results_count' => $search->results_count,
                    'created_at' => $search->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }
}

