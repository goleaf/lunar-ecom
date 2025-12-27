<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;
use Lunar\Models\Collection;
use Lunar\Models\Product as LunarProduct;

return new class extends Migration
{
    public function up(): void
    {
        $table = $this->prefix.'urls';

        if (! Schema::hasTable($table)) {
            return;
        }

        $prefix = (string) config('lunar.database.morph_prefix', '');
        $productMorph = $prefix.'product';
        $collectionMorph = $prefix.'collection';

        DB::table($table)
            ->whereIn('element_type', [Product::class, LunarProduct::class])
            ->update(['element_type' => $productMorph]);

        DB::table($table)
            ->whereIn('element_type', [Collection::class])
            ->update(['element_type' => $collectionMorph]);
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};
