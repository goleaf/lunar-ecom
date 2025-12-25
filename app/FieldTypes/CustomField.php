<?php

namespace App\FieldTypes;

use Lunar\FieldTypes\Text;
use Lunar\Exceptions\FieldTypeException;

/**
 * Custom attribute field type with comprehensive functionality.
 * 
 * This class extends Lunar's Text field type to provide enhanced
 * functionality including validation, formatting, comparison, and utility methods.
 * 
 * @see https://docs.lunarphp.com/1.x/admin/extending/attributes
 */
class CustomField extends Text
{
    /**
     * Additional metadata for the custom field.
     *
     * @var array
     */
    protected array $metadata = [];

    /**
     * Create a new instance of CustomField.
     *
     * @param  string|int|float|bool|null  $value
     * @param  array  $metadata  Additional metadata to store with the field
     */
    public function __construct($value = '', array $metadata = [])
    {
        parent::__construct($value);
        $this->metadata = $metadata;
    }

    /**
     * Get the raw value of this field.
     *
     * @return string
     */
    public function getValue(): string
    {
        return parent::getValue() ?? '';
    }

    /**
     * Set the value of this field.
     *
     * @param  string|int|float|bool|null  $value
     * @return self
     */
    public function setValue($value): self
    {
        parent::setValue($value);
        return $this;
    }

    /**
     * Check if the field value is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->getValue());
    }

    /**
     * Check if the field value is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Check if the field value equals the given value.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function equals($value): bool
    {
        return (string) $this->getValue() === (string) $value;
    }

    /**
     * Check if the field value does not equal the given value.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function notEquals($value): bool
    {
        return !$this->equals($value);
    }

    /**
     * Check if the field value contains the given substring.
     *
     * @param  string  $substring
     * @param  bool  $caseSensitive
     * @return bool
     */
    public function contains(string $substring, bool $caseSensitive = true): bool
    {
        $value = $this->getValue();
        $substring = $caseSensitive ? $substring : mb_strtolower($substring);
        $value = $caseSensitive ? $value : mb_strtolower($value);
        
        return str_contains($value, $substring);
    }

    /**
     * Check if the field value starts with the given prefix.
     *
     * @param  string  $prefix
     * @param  bool  $caseSensitive
     * @return bool
     */
    public function startsWith(string $prefix, bool $caseSensitive = true): bool
    {
        $value = $this->getValue();
        
        if (!$caseSensitive) {
            $value = mb_strtolower($value);
            $prefix = mb_strtolower($prefix);
        }
        
        return str_starts_with($value, $prefix);
    }

    /**
     * Check if the field value ends with the given suffix.
     *
     * @param  string  $suffix
     * @param  bool  $caseSensitive
     * @return bool
     */
    public function endsWith(string $suffix, bool $caseSensitive = true): bool
    {
        $value = $this->getValue();
        
        if (!$caseSensitive) {
            $value = mb_strtolower($value);
            $suffix = mb_strtolower($suffix);
        }
        
        return str_ends_with($value, $suffix);
    }

    /**
     * Get the length of the field value.
     *
     * @return int
     */
    public function length(): int
    {
        return mb_strlen($this->getValue());
    }

    /**
     * Trim whitespace from the value.
     *
     * @param  string  $characters
     * @return self
     */
    public function trim(string $characters = " \t\n\r\0\x0B"): self
    {
        $this->setValue(trim($this->getValue(), $characters));
        return $this;
    }

    /**
     * Convert the value to uppercase.
     *
     * @return self
     */
    public function upper(): self
    {
        $this->setValue(mb_strtoupper($this->getValue()));
        return $this;
    }

    /**
     * Convert the value to lowercase.
     *
     * @return self
     */
    public function lower(): self
    {
        $this->setValue(mb_strtolower($this->getValue()));
        return $this;
    }

    /**
     * Convert the value to title case.
     *
     * @return self
     */
    public function title(): self
    {
        $this->setValue(mb_convert_case($this->getValue(), MB_CASE_TITLE));
        return $this;
    }

    /**
     * Convert the value to sentence case.
     *
     * @return self
     */
    public function sentence(): self
    {
        $value = $this->getValue();
        $value = mb_strtolower($value);
        $value = ucfirst($value);
        $this->setValue($value);
        return $this;
    }

    /**
     * Pad the value to a certain length.
     *
     * @param  int  $length
     * @param  string  $padString
     * @param  int  $padType  STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH
     * @return self
     */
    public function pad(int $length, string $padString = ' ', int $padType = STR_PAD_RIGHT): self
    {
        $this->setValue(str_pad($this->getValue(), $length, $padString, $padType));
        return $this;
    }

    /**
     * Replace occurrences of a string in the value.
     *
     * @param  string|array  $search
     * @param  string|array  $replace
     * @return self
     */
    public function replace($search, $replace): self
    {
        $this->setValue(str_replace($search, $replace, $this->getValue()));
        return $this;
    }

    /**
     * Get a substring of the value.
     *
     * @param  int  $start
     * @param  int|null  $length
     * @return string
     */
    public function substring(int $start, ?int $length = null): string
    {
        if ($length === null) {
            return mb_substr($this->getValue(), $start);
        }
        
        return mb_substr($this->getValue(), $start, $length);
    }

    /**
     * Limit the length of the value and add suffix if truncated.
     *
     * @param  int  $length
     * @param  string  $suffix
     * @return self
     */
    public function limit(int $length, string $suffix = '...'): self
    {
        $value = $this->getValue();
        
        if (mb_strlen($value) > $length) {
            $value = mb_substr($value, 0, $length) . $suffix;
            $this->setValue($value);
        }
        
        return $this;
    }

    /**
     * Validate the field value against a regular expression.
     *
     * @param  string  $pattern
     * @return bool
     */
    public function matches(string $pattern): bool
    {
        return (bool) preg_match($pattern, $this->getValue());
    }

    /**
     * Check if the value is a valid email address.
     *
     * @return bool
     */
    public function isEmail(): bool
    {
        return filter_var($this->getValue(), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if the value is a valid URL.
     *
     * @return bool
     */
    public function isUrl(): bool
    {
        return filter_var($this->getValue(), FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if the value is numeric.
     *
     * @return bool
     */
    public function isNumeric(): bool
    {
        return is_numeric($this->getValue());
    }

    /**
     * Check if the value is an integer.
     *
     * @return bool
     */
    public function isInteger(): bool
    {
        return filter_var($this->getValue(), FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Check if the value is a float.
     *
     * @return bool
     */
    public function isFloat(): bool
    {
        return filter_var($this->getValue(), FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Convert the value to an integer.
     *
     * @return int|null
     */
    public function toInteger(): ?int
    {
        $value = filter_var($this->getValue(), FILTER_VALIDATE_INT);
        return $value !== false ? $value : null;
    }

    /**
     * Convert the value to a float.
     *
     * @return float|null
     */
    public function toFloat(): ?float
    {
        $value = filter_var($this->getValue(), FILTER_VALIDATE_FLOAT);
        return $value !== false ? $value : null;
    }

    /**
     * Convert the value to a boolean.
     *
     * @return bool
     */
    public function toBoolean(): bool
    {
        $value = mb_strtolower(trim($this->getValue()));
        return in_array($value, ['1', 'true', 'yes', 'on', 'enabled'], true);
    }

    /**
     * Convert the value to an array by splitting on a delimiter.
     *
     * @param  string  $delimiter
     * @return array
     */
    public function toArray(string $delimiter = ','): array
    {
        $value = $this->getValue();
        
        if (empty($value)) {
            return [];
        }
        
        return array_map('trim', explode($delimiter, $value));
    }

    /**
     * Append a string to the value.
     *
     * @param  string  $suffix
     * @return self
     */
    public function append(string $suffix): self
    {
        $this->setValue($this->getValue() . $suffix);
        return $this;
    }

    /**
     * Prepend a string to the value.
     *
     * @param  string  $prefix
     * @return self
     */
    public function prepend(string $prefix): self
    {
        $this->setValue($prefix . $this->getValue());
        return $this;
    }

    /**
     * Get metadata associated with this field.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getMetadata(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->metadata;
        }
        
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set metadata for this field.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return self
     */
    public function setMetadata($key, $value = null): self
    {
        if (is_array($key)) {
            $this->metadata = array_merge($this->metadata, $key);
        } else {
            $this->metadata[$key] = $value;
        }
        
        return $this;
    }

    /**
     * Check if metadata exists for a given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasMetadata(string $key): bool
    {
        return isset($this->metadata[$key]);
    }

    /**
     * Remove metadata for a given key.
     *
     * @param  string  $key
     * @return self
     */
    public function removeMetadata(string $key): self
    {
        unset($this->metadata[$key]);
        return $this;
    }

    /**
     * Clear all metadata.
     *
     * @return self
     */
    public function clearMetadata(): self
    {
        $this->metadata = [];
        return $this;
    }

    /**
     * Validate the field value.
     *
     * @param  array  $rules  Validation rules (min, max, pattern, etc.)
     * @return bool
     * @throws FieldTypeException
     */
    public function validate(array $rules = []): bool
    {
        $value = $this->getValue();
        
        if (isset($rules['required']) && $rules['required'] && $this->isEmpty()) {
            throw new FieldTypeException('CustomField value is required.');
        }
        
        if (isset($rules['min_length']) && mb_strlen($value) < $rules['min_length']) {
            throw new FieldTypeException("CustomField value must be at least {$rules['min_length']} characters.");
        }
        
        if (isset($rules['max_length']) && mb_strlen($value) > $rules['max_length']) {
            throw new FieldTypeException("CustomField value must not exceed {$rules['max_length']} characters.");
        }
        
        if (isset($rules['pattern']) && !$this->matches($rules['pattern'])) {
            throw new FieldTypeException('CustomField value does not match the required pattern.');
        }
        
        if (isset($rules['email']) && $rules['email'] && !$this->isEmail()) {
            throw new FieldTypeException('CustomField value must be a valid email address.');
        }
        
        if (isset($rules['url']) && $rules['url'] && !$this->isUrl()) {
            throw new FieldTypeException('CustomField value must be a valid URL.');
        }
        
        return true;
    }

    /**
     * Create a copy of this field.
     *
     * @return self
     */
    public function copy(): self
    {
        return new self($this->getValue(), $this->metadata);
    }

    /**
     * Merge this field's value with another value.
     *
     * @param  string  $value
     * @param  string  $separator
     * @return self
     */
    public function merge(string $value, string $separator = ' '): self
    {
        $current = $this->getValue();
        $this->setValue($current ? $current . $separator . $value : $value);
        return $this;
    }

    /**
     * Serialize the class including metadata.
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        $data = parent::jsonSerialize();
        
        if (!empty($this->metadata)) {
            return [
                'value' => $data,
                'metadata' => $this->metadata,
            ];
        }
        
        return $data;
    }

    /**
     * Get configuration for this field type.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'options' => [
                'min_length' => 'numeric|min:0',
                'max_length' => 'numeric',
                'pattern' => 'nullable|string',
                'required' => 'boolean',
            ],
        ];
    }

    /**
     * Create a new instance from an array.
     *
     * @param  array  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $value = $data['value'] ?? '';
        $metadata = $data['metadata'] ?? [];
        
        return new self($value, $metadata);
    }

    /**
     * Create a new instance from JSON.
     *
     * @param  string  $json
     * @return self
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        if (!is_array($data)) {
            return new self($json);
        }
        
        return self::fromArray($data);
    }
}


