<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    private const PREFIX = 'lunar_';

    private const TABLES = [
        'ab_test_events',
        'abandoned_carts',
        'addresses',
        'assets',
        'attributables',
        'attribute_group_attributes',
        'attribute_groups',
        'attribute_set_groups',
        'attribute_sets',
        'attribute_value_history',
        'attributes',
        'availability_bookings',
        'availability_notifications',
        'availability_rules',
        'b2b_contracts',
        'brand_collection',
        'brand_discount',
        'brands',
        'bundle_analytics',
        'bundle_items',
        'bundle_prices',
        'bundles',
        'cart_addresses',
        'cart_line_discount',
        'cart_lines',
        'cart_pricing_snapshots',
        'carts',
        'categories',
        'category_channels',
        'category_languages',
        'category_product',
        'channel_attribute_values',
        'channel_media',
        'channel_product_data',
        'channel_warehouse',
        'channelables',
        'channels',
        'checkout_locks',
        'collection_customer_group',
        'collection_discount',
        'collection_groups',
        'collection_product',
        'collection_product_metadata',
        'collection_rules',
        'collections',
        'coming_soon_notifications',
        'comparison_analytics',
        'contract_audits',
        'contract_company_hierarchies',
        'contract_credit_limits',
        'contract_prices',
        'contract_purchase_orders',
        'contract_rules',
        'contract_sales_reps',
        'contract_shared_carts',
        'countries',
        'currencies',
        'customer_customer_group',
        'customer_discount',
        'customer_group_discount',
        'customer_group_product',
        'customer_groups',
        'customer_user',
        'customers',
        'customization_examples',
        'customization_templates',
        'digital_product_versions',
        'digital_products',
        'discount_audit_trails',
        'discount_user',
        'discountables',
        'discounts',
        'download_links',
        'download_logs',
        'downloads',
        'fit_reviews',
        'inventory_automation_rules',
        'inventory_levels',
        'inventory_transactions',
        'languages',
        'license_key_activations',
        'low_stock_alerts',
        'map_prices',
        'margin_alerts',
        'media_product_variant',
        'order_addresses',
        'order_item_customizations',
        'order_lines',
        'order_status_history',
        'orders',
        'out_of_stock_triggers',
        'price_elasticity',
        'price_histories',
        'price_history',
        'price_lists',
        'price_matrices',
        'price_simulations',
        'price_snapshots',
        'prices',
        'pricing_approvals',
        'pricing_rules',
        'pricing_tiers',
        'product_ab_tests',
        'product_analytics',
        'product_answers',
        'product_associations',
        'product_attribute_values',
        'product_automation_rules',
        'product_availability',
        'product_badge_assignments',
        'product_badge_performance',
        'product_badge_product',
        'product_badge_rules',
        'product_badges',
        'product_bulk_actions',
        'product_comparisons',
        'product_customizations',
        'product_import_errors',
        'product_import_rollbacks',
        'product_import_rows',
        'product_imports',
        'product_option_value_product_variant',
        'product_option_values',
        'product_options',
        'product_product_option',
        'product_purchase_associations',
        'product_qa_metrics',
        'product_questions',
        'product_schedule_history',
        'product_schedules',
        'product_size_guide',
        'product_types',
        'product_variants',
        'product_versions',
        'product_views',
        'product_workflow_history',
        'product_workflows',
        'products',
        'promotional_banners',
        'recommendation_clicks',
        'recommendation_rules',
        'review_helpful_votes',
        'review_media',
        'reviews',
        'search_analytics',
        'search_synonyms',
        'seasonal_product_rules',
        'size_charts',
        'size_guides',
        'smart_collection_rules',
        'staff',
        'states',
        'stock_movements',
        'stock_notification_metrics',
        'stock_notifications',
        'stock_reservations',
        'supplier_reorder_hooks',
        'taggables',
        'tags',
        'tax_classes',
        'tax_rate_amounts',
        'tax_rates',
        'tax_zone_countries',
        'tax_zone_customer_groups',
        'tax_zone_postcodes',
        'tax_zone_states',
        'tax_zones',
        'transactions',
        'url_redirects',
        'urls',
        'variant_attribute_combinations',
        'variant_attribute_dependencies',
        'variant_attribute_normalizations',
        'variant_attribute_values',
        'variant_availability_restrictions',
        'variant_license_keys',
        'variant_lots',
        'variant_performance',
        'variant_personalizations',
        'variant_price_hooks',
        'variant_prices',
        'variant_relationships',
        'variant_returns',
        'variant_serial_numbers',
        'variant_templates',
        'variant_validation_rules',
        'variant_views',
        'warehouse_fulfillment_rules',
        'warehouses',
    ];

    public function up(): void
    {
        $this->renameTables(self::TABLES, self::PREFIX, '');
    }

    public function down(): void
    {
        $this->renameTables(self::TABLES, '', self::PREFIX);
    }

    private function renameTables(array $tables, string $fromPrefix, string $toPrefix): void
    {
        $schema = Schema::connection($this->getConnection());
        $schema->disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                $from = $fromPrefix . $table;
                $to = $toPrefix . $table;

                if (! $schema->hasTable($from)) {
                    continue;
                }

                if ($schema->hasTable($to)) {
                    if ($this->tableHasRows($to)) {
                        throw new \RuntimeException("Cannot rename {$from} to {$to} because {$to} exists and has data.");
                    }

                    $schema->drop($to);
                }

                $schema->rename($from, $to);
            }
        } finally {
            $schema->enableForeignKeyConstraints();
        }
    }

    private function tableHasRows(string $table): bool
    {
        return DB::connection($this->getConnection())
            ->table($table)
            ->limit(1)
            ->exists();
    }
};
