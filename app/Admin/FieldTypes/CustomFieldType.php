<?php

namespace App\Admin\FieldTypes;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Lunar\Admin\Support\FieldTypes\BaseFieldType;
use Lunar\Models\Attribute;

/**
 * Example custom field type converter for Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/attributes#create-the-field-type
 * 
 * This class converts the custom field into a Filament form component for rendering
 * in the admin panel. It also handles configuration fields for the attribute.
 */
class CustomFieldType extends BaseFieldType
{
    /**
     * The Livewire Synthesizer class name for this field type.
     */
    protected static string $synthesizer = \App\Admin\Synthesizers\CustomFieldSynth::class;

    /**
     * Get the Filament form component for this attribute.
     * 
     * This method is called when rendering the attribute in the admin panel.
     * You can access the attribute's configuration here to customize the component.
     */
    public static function getFilamentComponent(Attribute $attribute): Component
    {
        // Access configuration if set (e.g., min_length, max_length)
        $min = (int) ($attribute->configuration?->get('min_length') ?? 0);
        $max = (int) ($attribute->configuration?->get('max_length') ?? 255);

        return TextInput::make($attribute->handle)
            ->label($attribute->name)
            ->minLength($min)
            ->maxLength($max);
    }

    /**
     * Get configuration fields for this attribute type.
     * 
     * These fields allow administrators to configure additional settings
     * for the attribute (e.g., min/max values, validation rules).
     * The configuration is stored in the `configuration` JSON column.
     */
    public static function getConfigurationFields(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('min_length')
                    ->label('Minimum Length')
                    ->numeric()
                    ->default(0),
                TextInput::make('max_length')
                    ->label('Maximum Length')
                    ->numeric()
                    ->default(255),
            ]),
        ];
    }
}


