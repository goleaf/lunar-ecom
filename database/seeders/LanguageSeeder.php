<?php

namespace Database\Seeders;

use App\Lunar\Languages\LanguageHelper;
use Illuminate\Database\Seeder;
use Lunar\Models\Language;

/**
 * Seeder for multi-language configuration.
 * 
 * Creates and configures 5 languages: English, Spanish, French, German, Chinese
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
                'code' => 'en',
                'name' => 'English',
                'default' => true,
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
            [
                'code' => 'zh',
                'name' => 'Chinese',
                'default' => false,
            ],
        ];

        // First, unset any existing default languages
        Language::where('default', true)->update(['default' => false]);

        foreach ($languages as $languageData) {
            $language = Language::updateOrCreate(
                ['code' => $languageData['code']],
                [
                    'name' => $languageData['name'],
                    'default' => $languageData['default'],
                ]
            );

            $this->command->info("  ✓ {$language->code} - {$language->name}" . ($language->default ? ' (default)' : ''));
        }

        $this->command->info('✅ Multi-language configuration completed!');
        $this->command->info('   Default language: English (en)');
        $this->command->info('   Available languages: English, Spanish, French, German, Chinese');
    }
}

