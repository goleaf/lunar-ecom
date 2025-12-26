<?php

namespace App\Services;

use App\Models\Attribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Attribute Definition Service.
 * 
 * Manages attribute definitions with:
 * - Code (unique identifier)
 * - Type (text, select, number, boolean, color, file, date, JSON)
 * - Scope (product / variant)
 * - Localizable
 * - Channel-specific
 * - Required / optional
 * - Default value
 * - Validation rules
 * - UI hint (dropdown, swatch, slider)
 */
class AttributeDefinitionService
{
    /**
     * Supported attribute types.
     */
    protected array $supportedTypes = [
        'text',
        'select',
        'multiselect',
        'number',
        'boolean',
        'color',
        'file',
        'date',
        'datetime',
        'json',
        'textarea',
        'richtext',
    ];

    /**
     * Supported UI hints.
     */
    protected array $supportedUIHints = [
        'dropdown',
        'swatch',
        'slider',
        'text',
        'textarea',
        'checkbox',
        'radio',
        'color_picker',
        'file_upload',
        'date_picker',
        'number_input',
        'json_editor',
    ];

    /**
     * Create a new attribute definition.
     *
     * @param  array  $data
     * @return Attribute
     */
    public function createAttributeDefinition(array $data): Attribute
    {
        $validated = $this->validateAttributeData($data);

        return DB::transaction(function () use ($validated) {
            // Generate code if not provided
            if (empty($validated['code'])) {
                $validated['code'] = $this->generateCode($validated['handle'] ?? $validated['name']);
            }

            // Ensure code is unique
            $validated['code'] = $this->ensureUniqueCode($validated['code']);

            // Set default UI hint based on type if not provided
            if (empty($validated['ui_hint'])) {
                $validated['ui_hint'] = $this->getDefaultUIHint($validated['type']);
            }

            // Create attribute
            $attribute = Attribute::create($validated);

            return $attribute;
        });
    }

    /**
     * Update an attribute definition.
     *
     * @param  Attribute  $attribute
     * @param  array  $data
     * @return Attribute
     */
    public function updateAttributeDefinition(Attribute $attribute, array $data): Attribute
    {
        $validated = $this->validateAttributeData($data, $attribute);

        return DB::transaction(function () use ($attribute, $validated) {
            // Ensure code is unique if changed
            if (isset($validated['code']) && $validated['code'] !== $attribute->code) {
                $validated['code'] = $this->ensureUniqueCode($validated['code'], $attribute->id);
            }

            $attribute->update($validated);

            return $attribute->fresh();
        });
    }

    /**
     * Validate attribute data.
     *
     * @param  array  $data
     * @param  Attribute|null  $attribute
     * @return array
     */
    protected function validateAttributeData(array $data, ?Attribute $attribute = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255', 'alpha_dash'],
            'code' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'type' => ['required', 'string', 'in:' . implode(',', $this->supportedTypes)],
            'scope' => ['nullable', 'string', 'in:product,variant,both'],
            'localizable' => ['nullable', 'boolean'],
            'channel_specific' => ['nullable', 'boolean'],
            'required' => ['nullable', 'boolean'],
            'default_value' => ['nullable'],
            'ui_hint' => ['nullable', 'string', 'in:' . implode(',', $this->supportedUIHints)],
            'validation_rules' => ['nullable', 'array'],
            'searchable' => ['nullable', 'boolean'],
            'filterable' => ['nullable', 'boolean'],
            'sortable' => ['nullable', 'boolean'],
        ];

        // Unique handle check
        if ($attribute) {
            $rules['handle'][] = 'unique:' . config('lunar.database.table_prefix') . 'attributes,handle,' . $attribute->id;
            $rules['code'][] = 'unique:' . config('lunar.database.table_prefix') . 'attributes,code,' . $attribute->id;
        } else {
            $rules['handle'][] = 'unique:' . config('lunar.database.table_prefix') . 'attributes,handle';
            $rules['code'][] = 'unique:' . config('lunar.database.table_prefix') . 'attributes,code';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Generate code from name/handle.
     *
     * @param  string  $input
     * @return string
     */
    protected function generateCode(string $input): string
    {
        // Convert to snake_case and uppercase
        $code = strtoupper(str_replace([' ', '-'], '_', $input));
        
        // Remove special characters
        $code = preg_replace('/[^A-Z0-9_]/', '', $code);
        
        return $code;
    }

    /**
     * Ensure code is unique.
     *
     * @param  string  $code
     * @param  int|null  $excludeId
     * @return string
     */
    protected function ensureUniqueCode(string $code, ?int $excludeId = null): string
    {
        $baseCode = $code;
        $counter = 1;

        while (Attribute::where('code', $code)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Get default UI hint for type.
     *
     * @param  string  $type
     * @return string
     */
    protected function getDefaultUIHint(string $type): string
    {
        return match($type) {
            'select' => 'dropdown',
            'multiselect' => 'dropdown',
            'color' => 'swatch',
            'number' => 'number_input',
            'boolean' => 'checkbox',
            'file' => 'file_upload',
            'date' => 'date_picker',
            'datetime' => 'date_picker',
            'json' => 'json_editor',
            'textarea' => 'textarea',
            'richtext' => 'textarea',
            default => 'text',
        };
    }

    /**
     * Get attributes by scope.
     *
     * @param  string  $scope
     * @return \Illuminate\Support\Collection
     */
    public function getAttributesByScope(string $scope): \Illuminate\Support\Collection
    {
        return Attribute::where('scope', $scope)
            ->orWhere('scope', 'both')
            ->orderBy('position')
            ->get();
    }

    /**
     * Get localizable attributes.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLocalizableAttributes(): \Illuminate\Support\Collection
    {
        return Attribute::where('localizable', true)
            ->orderBy('position')
            ->get();
    }

    /**
     * Get channel-specific attributes.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getChannelSpecificAttributes(): \Illuminate\Support\Collection
    {
        return Attribute::where('channel_specific', true)
            ->orderBy('position')
            ->get();
    }

    /**
     * Get required attributes for scope.
     *
     * @param  string  $scope
     * @return \Illuminate\Support\Collection
     */
    public function getRequiredAttributes(string $scope): \Illuminate\Support\Collection
    {
        return Attribute::where('required', true)
            ->where(function ($query) use ($scope) {
                $query->where('scope', $scope)
                      ->orWhere('scope', 'both');
            })
            ->orderBy('position')
            ->get();
    }

    /**
     * Validate attribute value against definition.
     *
     * @param  Attribute  $attribute
     * @param  mixed  $value
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateAttributeValue(Attribute $attribute, $value): array
    {
        $errors = [];

        // Check required
        if ($attribute->required && ($value === null || $value === '')) {
            $errors[] = "Attribute '{$attribute->name}' is required.";
            return ['valid' => false, 'errors' => $errors];
        }

        // If not required and empty, it's valid
        if (!$attribute->required && ($value === null || $value === '')) {
            return ['valid' => true, 'errors' => []];
        }

        // Type-specific validation
        $typeValidation = $this->validateByType($attribute, $value);
        if (!$typeValidation['valid']) {
            $errors = array_merge($errors, $typeValidation['errors']);
        }

        // Custom validation rules
        if ($attribute->validation_rules) {
            $customValidation = $this->validateCustomRules($attribute, $value);
            if (!$customValidation['valid']) {
                $errors = array_merge($errors, $customValidation['errors']);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate value by type.
     *
     * @param  Attribute  $attribute
     * @param  mixed  $value
     * @return array
     */
    protected function validateByType(Attribute $attribute, $value): array
    {
        $errors = [];

        switch ($attribute->type) {
            case 'number':
                if (!is_numeric($value)) {
                    $errors[] = "Value must be a number.";
                }
                break;

            case 'boolean':
                if (!is_bool($value) && !in_array($value, ['0', '1', 'true', 'false'])) {
                    $errors[] = "Value must be a boolean.";
                }
                break;

            case 'date':
            case 'datetime':
                try {
                    new \DateTime($value);
                } catch (\Exception $e) {
                    $errors[] = "Value must be a valid date.";
                }
                break;

            case 'json':
                if (!is_array($value) && !is_object($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $errors[] = "Value must be valid JSON.";
                    }
                }
                break;

            case 'select':
                // Validate against configuration options if available
                if (isset($attribute->configuration['options'])) {
                    $options = array_column($attribute->configuration['options'], 'value');
                    if (!in_array($value, $options)) {
                        $errors[] = "Value must be one of the allowed options.";
                    }
                }
                break;

            case 'multiselect':
                if (!is_array($value)) {
                    $errors[] = "Value must be an array.";
                } elseif (isset($attribute->configuration['options'])) {
                    $options = array_column($attribute->configuration['options'], 'value');
                    $invalid = array_diff($value, $options);
                    if (!empty($invalid)) {
                        $errors[] = "Values contain invalid options: " . implode(', ', $invalid);
                    }
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate custom rules.
     *
     * @param  Attribute  $attribute
     * @param  mixed  $value
     * @return array
     */
    protected function validateCustomRules(Attribute $attribute, $value): array
    {
        $errors = [];
        $rules = $attribute->validation_rules ?? [];

        // Min/Max for numbers
        if ($attribute->type === 'number' && is_numeric($value)) {
            if (isset($rules['min']) && $value < $rules['min']) {
                $errors[] = "Value must be at least {$rules['min']}.";
            }
            if (isset($rules['max']) && $value > $rules['max']) {
                $errors[] = "Value must be at most {$rules['max']}.";
            }
        }

        // Min/Max length for text
        if (in_array($attribute->type, ['text', 'textarea', 'richtext']) && is_string($value)) {
            if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                $errors[] = "Value must be at least {$rules['min_length']} characters.";
            }
            if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                $errors[] = "Value must be at most {$rules['max_length']} characters.";
            }
        }

        // Pattern matching
        if (isset($rules['pattern']) && is_string($value)) {
            if (!preg_match($rules['pattern'], $value)) {
                $errors[] = "Value does not match required pattern.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get default value for attribute.
     *
     * @param  Attribute  $attribute
     * @return mixed
     */
    public function getDefaultValue(Attribute $attribute)
    {
        if ($attribute->default_value !== null) {
            return $attribute->default_value;
        }

        // Type-specific defaults
        return match($attribute->type) {
            'number' => 0,
            'boolean' => false,
            'select', 'multiselect' => null,
            'json' => [],
            default => null,
        };
    }
}


