<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Lunar\Models\Language;

return new class extends Migration
{
    public function up(): void
    {
        $languageModel = new Language();

        if (!Schema::hasTable($languageModel->getTable())) {
            return;
        }

        // Ensure there is exactly one default language: Lithuanian.
        Language::query()->where('default', true)->update(['default' => false]);

        Language::query()->updateOrCreate(
            ['code' => 'lt'],
            ['name' => 'Lithuanian', 'default' => true]
        );
    }

    public function down(): void
    {
        $languageModel = new Language();

        if (!Schema::hasTable($languageModel->getTable())) {
            return;
        }

        // Best-effort rollback: unset Lithuanian default, restore English as default if present.
        Language::query()->where('code', 'lt')->update(['default' => false]);

        $en = Language::query()->where('code', 'en')->first();
        if ($en) {
            Language::query()->where('default', true)->update(['default' => false]);
            $en->update(['default' => true]);
        }
    }
};


