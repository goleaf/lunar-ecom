<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Lunar\Currencies\CurrencyHelper;
use App\Lunar\FrontendSession\FrontendSessionHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Lunar\Models\Currency;

/**
 * Controller for handling currency switching in the frontend.
 */
class CurrencyController extends Controller
{
    /**
     * Get all enabled currencies.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $currencies = CurrencyHelper::getEnabled()
            ->map(function ($currency) {
                return [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'exchange_rate' => $currency->exchange_rate,
                    'decimal_places' => $currency->decimal_places,
                    'format' => $currency->format,
                    'decimal_point' => $currency->decimal_point,
                    'thousand_point' => $currency->thousand_point,
                    'is_default' => $currency->default,
                ];
            });

        return response()->json([
            'currencies' => $currencies,
            'current' => $this->getCurrentCurrencyData(),
        ]);
    }

    /**
     * Switch to a different currency.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string',
        ]);

        $currencyCode = $request->input('currency');
        $currency = CurrencyHelper::findByCode($currencyCode);

        if (!$currency) {
            return response()->json([
                'error' => 'Currency not found',
            ], 404);
        }

        if (!$currency->enabled) {
            return response()->json([
                'error' => 'Currency is not enabled',
            ], 400);
        }

        // Set the currency in the frontend session
        FrontendSessionHelper::setCurrency($currency);

        return response()->json([
            'success' => true,
            'message' => __('frontend.messages.currency_switched'),
            'currency' => $this->getCurrentCurrencyData(),
        ]);
    }

    /**
     * Get the current currency.
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        return response()->json([
            'currency' => $this->getCurrentCurrencyData(),
        ]);
    }

    /**
     * Get current currency data as array.
     *
     * @return array|null
     */
    protected function getCurrentCurrencyData(): ?array
    {
        $currency = FrontendSessionHelper::getCurrency();

        if (!$currency) {
            return null;
        }

        return [
            'id' => $currency->id,
            'code' => $currency->code,
            'name' => $currency->name,
            'exchange_rate' => $currency->exchange_rate,
            'decimal_places' => $currency->decimal_places,
            'format' => $currency->format,
            'decimal_point' => $currency->decimal_point,
            'thousand_point' => $currency->thousand_point,
            'is_default' => $currency->default,
        ];
    }
}





