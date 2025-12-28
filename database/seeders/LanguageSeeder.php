<?php

namespace Database\Seeders;

use App\Lunar\Languages\LanguageHelper;
use Illuminate\Database\Seeder;
use Database\Factories\LanguageFactory;
use Lunar\Models\Language;

/**
 * Seeder for multi-language configuration.
 *
 * Creates and configures 5 languages: Lithuanian, English, Spanish, French, German
 */
class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌍 Setting up multi-language configuration...');

        // Language configurations
        $languages = [
            [
                'code' => 'lt',
                'name' => 'Lithuanian',
                'default' => true,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'default' => false,
            ],
            [
                'code' => 'es',
                'name' => 'Spanish',
                'default' => false,
            ],
            [
                'code' => 'fr',
                'name' => 'French',
                'default' => false,
            ],
            [
                'code' => 'de',
                'name' => 'German',
                'default' => false,
            ],
        ];

        // First, unset any existing default languages
        Language::where('default', true)->update(['default' => false]);

        foreach ($languages as $languageData) {
            $factoryData = LanguageFactory::new()
                ->state($languageData)
                ->make()
                ->getAttributes();

            $language = Language::updateOrCreate(
                ['code' => $languageData['code']],
                [
                    'name' => $factoryData['name'],
                    'default' => $factoryData['default'],
                ]
            );

            $this->command->info("  ✓ {$language->code} - {$language->name}" . ($language->default ? ' (default)' : ''));
        }

        $this->command->info('✅ Multi-language configuration completed!');
        $this->command->info('   Default language: Lithuanian (lt)');
        $this->command->info('   Available languages: Lithuanian, English, Spanish, French, German');
    }
}
