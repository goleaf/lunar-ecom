<?php

namespace App\Enums;

use Osama\LaravelEnums\Concerns\EnumTranslatable;

enum CollectionType: string
{
    use EnumTranslatable;

    case STANDARD = 'standard';
    case CROSS_SELL = 'cross_sell';
    case UP_SELL = 'up_sell';
    case RELATED = 'related';
    case BUNDLE = 'bundle';

    /**
     * Get all collection type values.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the type.
     *
     * @return string
     */
    public function label(): string
    {
        return $this->trans();
    }

    /**
     * Get description for the type.
     *
     * @return string
     */
    public function description(): string
    {
        return match($this) {
            self::STANDARD => 'Standard collection for organizing products',
            self::CROSS_SELL => 'Collection for complementary products (cross-selling)',
            self::UP_SELL => 'Collection for higher-value alternatives (up-selling)',
            self::RELATED => 'Collection for related or similar products',
            self::BUNDLE => 'Collection for bundled product sets',
        };
    }
}


