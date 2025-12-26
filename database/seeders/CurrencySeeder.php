<?php

namespace Database\Seeders;

use App\Lunar\Currencies\CurrencyHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Database\Factories\CurrencyFactory;
use Lunar\Models\Currency;

/**
 * Seeder for multi-currency configuration.
 * 
 * Creates and configures 5 currencies: USD, EUR, GBP, JPY, AUD
 * Sets up exchange rates, default currency (USD), and currency formatting.
 */
class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Setting up multi-currency configuration...');

        // Currency configurations with exchange rates (as of typical rates)
        // Exchange rates are relative to USD (default currency)
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'exchange_rate' => 1.0000, // Default currency
                'decimal_places' => 2,
                'format' => '{symbol}{value}',
                'decimal_point' => '.',
                'thousand_point' => ',',
                'default' => true,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'exchange_rate' => 0.9200, // 1 USD = 0.92 EUR (approx)
                'decimal_places' => 2,
                'format' => '{value} {symbol}',
                'decimal_point' => ',',
                'thousand_point' => '.',
                'default' => false,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'exchange_rate' => 0.7900, // 1 USD = 0.79 GBP (approx)
                'decimal_places' => 2,
                'format' => '{symbol}{value}',
                'decimal_point' => '.',
                'thousand_point' => ',',
                'default' => false,
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'exchange_rate' => 150.0000, // 1 USD = 150 JPY (approx)
                'decimal_places' => 0, // JPY typically has no decimal places
                'format' => '{symbol}{value}',
                'decimal_point' => '.',
                'thousand_point' => ',',
                'default' => false,
            ],
            [
                'code' => 'AUD',
                'name' => 'Australian Dollar',
                'exchange_rate' => 1.5200, // 1 USD = 1.52 AUD (approx)
                'decimal_places' => 2,
                'format' => '{symbol}{value}',
                'decimal_point' => '.',
                'thousand_point' => ',',
                'default' => false,
            ],
        ];

        // First, unset any existing default currencies
        Currency::where('default', true)->update(['default' => false]);

        $table = config('lunar.database.table_prefix') . 'currencies';
        $hasFormat = Schema::hasColumn($table, 'format');
        $hasDecimalPoint = Schema::hasColumn($table, 'decimal_point');
        $hasThousandPoint = Schema::hasColumn($table, 'thousand_point');

        foreach ($currencies as $currencyData) {
            $factory = CurrencyFactory::new()->state([
                'code' => $currencyData['code'],
                'name' => $currencyData['name'],
                'exchange_rate' => $currencyData['exchange_rate'],
                'decimal_places' => $currencyData['decimal_places'],
                'enabled' => true,
                'default' => $currencyData['default'],
            ]);

            if ($hasFormat || $hasDecimalPoint || $hasThousandPoint) {
                $factory = $factory->withFormatting(
                    $currencyData['format'],
                    $currencyData['decimal_point'],
                    $currencyData['thousand_point']
                );
            }

            $factoryData = $factory->make()->getAttributes();

            $updateData = [
                'name' => $factoryData['name'],
                'exchange_rate' => $factoryData['exchange_rate'],
                'decimal_places' => $factoryData['decimal_places'],
                'enabled' => $factoryData['enabled'],
                'default' => $factoryData['default'],
            ];

            if ($hasFormat) {
                $updateData['format'] = $factoryData['format'];
            }
            if ($hasDecimalPoint) {
                $updateData['decimal_point'] = $factoryData['decimal_point'];
            }
            if ($hasThousandPoint) {
                $updateData['thousand_point'] = $factoryData['thousand_point'];
            }

            $currency = Currency::updateOrCreate(
                ['code' => $currencyData['code']],
                $updateData
            );

            $this->command->info("  ✓ {$currency->code} - {$currency->name} (Rate: {$currency->exchange_rate})");
        }

        $this->command->info('✅ Multi-currency configuration completed!');
        $this->command->info('   Default currency: USD');
        $this->command->info('   Enabled currencies: USD, EUR, GBP, JPY, AUD');
    }
}
