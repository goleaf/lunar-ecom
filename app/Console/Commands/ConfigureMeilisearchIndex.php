<?php

namespace App\Console\Commands;

use App\Models\SearchSynonym;
use Illuminate\Console\Command;
use Meilisearch\Client as MeilisearchClient;

class ConfigureMeilisearchIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:configure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configures the Meilisearch index settings for products.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->info('Scout driver is not Meilisearch. Skipping configuration.');
            return;
        }

        try {
            $client = new MeilisearchClient(
                config('scout.meilisearch.host'),
                config('scout.meilisearch.key')
            );
            $index = $client->index('products');

            $this->info('Configuring Meilisearch index "products"...');

            // Update ranking rules
            $rankingRules = config('scout.meilisearch.index-settings.products.rankingRules', [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ]);
            $index->updateRankingRules($rankingRules);
            $this->info('Updated ranking rules.');

            // Update searchable attributes
            $searchableAttributes = config('scout.meilisearch.index-settings.products.searchableAttributes', [
                'name', 'description', 'sku', 'brand_name', 'category_names', 'attribute_values', 'skus',
            ]);
            $index->updateSearchableAttributes($searchableAttributes);
            $this->info('Updated searchable attributes.');

            // Update filterable attributes
            $filterableAttributes = config('scout.meilisearch.index-settings.products.filterableAttributes', [
                'status', 'brand_id', 'category_ids', 'product_type_id', 'price_min', 'price_max', 'in_stock',
            ]);
            $index->updateFilterableAttributes($filterableAttributes);
            $this->info('Updated filterable attributes.');

            // Update sortable attributes
            $sortableAttributes = config('scout.meilisearch.index-settings.products.sortableAttributes', [
                'price_min', 'created_at', 'updated_at',
            ]);
            $index->updateSortableAttributes($sortableAttributes);
            $this->info('Updated sortable attributes.');

            // Update typo tolerance
            $index->updateTypoTolerance([
                'enabled' => true,
                'minWordSizeForTypos' => [
                    'oneTypo' => 4,
                    'twoTypos' => 8,
                ],
            ]);
            $this->info('Updated typo tolerance settings.');

            // Update stop words
            $stopWords = config('scout.meilisearch.index-settings.products.stopWords', []);
            if (!empty($stopWords)) {
                $index->updateStopWords($stopWords);
                $this->info('Updated stop words.');
            }

            // Update synonyms from database
            $this->updateSynonyms($index);

            $this->info('Meilisearch index configured successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to configure Meilisearch index: ' . $e->getMessage());
        }
    }

    /**
     * Update synonyms in Meilisearch.
     *
     * @param  \Meilisearch\Endpoints\Indexes  $index
     * @return void
     */
    protected function updateSynonyms($index): void
    {
        $synonyms = SearchSynonym::active()->get();
        $synonymMap = [];

        foreach ($synonyms as $synonym) {
            $synonymMap[$synonym->term] = $synonym->synonyms;
        }

        if (!empty($synonymMap)) {
            $index->updateSynonyms($synonymMap);
            $this->info('Updated synonyms.');
        }
    }
}

