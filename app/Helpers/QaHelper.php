<?php

namespace App\Helpers;

use App\Models\Product;
use App\Services\QuestionService;

class QaHelper
{
    /**
     * Generate structured data for Q&A (SEO).
     *
     * @param  Product  $product
     * @return array
     */
    public static function generateStructuredData(Product $product): array
    {
        $service = app(QuestionService::class);
        return $service->generateStructuredData($product);
    }

    /**
     * Get Q&A count for display.
     *
     * @param  Product  $product
     * @return string
     */
    public static function getQaCountText(Product $product): string
    {
        $count = $product->qa_count;
        
        if ($count === 0) {
            return 'No questions yet';
        } elseif ($count === 1) {
            return '1 question';
        } else {
            return "{$count} questions";
        }
    }

    /**
     * Get answered Q&A count text.
     *
     * @param  Product  $product
     * @return string
     */
    public static function getAnsweredQaCountText(Product $product): string
    {
        $answered = $product->answered_qa_count;
        $total = $product->qa_count;
        
        if ($total === 0) {
            return 'No questions';
        }
        
        return "{$answered} of {$total} answered";
    }
}


