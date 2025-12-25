<?php

namespace App\Admin\Synthesizers;

use App\FieldTypes\CustomField;
use Lunar\Admin\Support\Synthesizers\AbstractFieldSynth;

/**
 * Example Livewire Synthesizer for custom field type.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/attributes#create-the-livewire-synthesizer
 * https://livewire.laravel.com/docs/synthesizers
 * 
 * Synthesizers tell Livewire how to hydrate/dehydrate field values when editing
 * attributes in the admin panel. They convert between the field type instance
 * and a format that Livewire can work with.
 */
class CustomFieldSynth extends AbstractFieldSynth
{
    /**
     * The unique key for this synthesizer.
     * This must be unique across all synthesizers.
     */
    public static string $key = 'lunar_custom_field_field';

    /**
     * The target field type class that this synthesizer handles.
     */
    protected static string $targetClass = CustomField::class;
}


