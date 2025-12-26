<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceMatrix;
use App\Models\PricingTier;
use App\Models\PricingRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class PricingExportController extends Controller
{
    /**
     * Export pricing data.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['product_id', 'matrix_type', 'format']);

        $matrices = PriceMatrix::query()
            ->when($filters['product_id'] ?? null, function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            })
            ->when($filters['matrix_type'] ?? null, function ($q) use ($filters) {
                $q->where('matrix_type', $filters['matrix_type']);
            })
            ->with(['product', 'productVariant', 'tiers', 'pricingRules'])
            ->get();

        $format = $filters['format'] ?? 'csv';
        $filename = 'pricing_export_' . now()->format('Y-m-d_His') . '.' . $format;

        if ($format === 'csv') {
            return $this->exportCsv($matrices, $filename);
        }

        return $this->exportExcel($matrices, $filename);
    }

    /**
     * Export as CSV.
     */
    protected function exportCsv($matrices, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($matrices) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Product ID',
                'Product Name',
                'Variant ID',
                'Variant Name',
                'Matrix Type',
                'Tier Name',
                'Min Quantity',
                'Max Quantity',
                'Price',
                'Customer Group',
                'Region',
            ]);

            foreach ($matrices as $matrix) {
                if ($matrix->matrix_type === 'quantity' && $matrix->tiers->isNotEmpty()) {
                    foreach ($matrix->tiers as $tier) {
                        fputcsv($file, [
                            $matrix->product_id,
                            $matrix->product->translateAttribute('name') ?? '',
                            $matrix->product_variant_id,
                            $matrix->productVariant->name ?? '',
                            $matrix->matrix_type,
                            $tier->tier_name,
                            $tier->min_quantity,
                            $tier->max_quantity,
                            $tier->price,
                            '',
                            '',
                        ]);
                    }
                } else {
                    foreach ($matrix->pricingRules as $rule) {
                        fputcsv($file, [
                            $matrix->product_id,
                            $matrix->product->translateAttribute('name') ?? '',
                            $matrix->product_variant_id,
                            $matrix->productVariant->name ?? '',
                            $matrix->matrix_type,
                            '',
                            '',
                            '',
                            $rule->price,
                            $rule->rule_type === 'customer_group' ? $rule->rule_value : '',
                            $rule->rule_type === 'region' ? $rule->rule_value : '',
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export as Excel.
     */
    protected function exportExcel($matrices, string $filename)
    {
        // Similar to CSV but using Excel format
        // Implementation would use Maatwebsite\Excel
        return $this->exportCsv($matrices, str_replace('.xlsx', '.csv', $filename));
    }
}


