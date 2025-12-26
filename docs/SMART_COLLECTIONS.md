# Smart Collections System

## Overview

A comprehensive smart collection system that automatically updates collections based on configurable rules. Collections can be configured with multiple rules using a visual rule builder interface in the admin panel.

## Features

### Rule Types

1. **Price Rules**
   - Greater than
   - Less than
   - Equals
   - Between (range)

2. **Tag Rules**
   - Equals
   - Does not equal
   - Is one of (multiple tags)
   - Is not one of
   - Contains

3. **Product Type Rules**
   - Equals
   - Does not equal
   - Is one of (multiple types)
   - Is not one of

4. **Inventory Status Rules**
   - In Stock
   - Out of Stock
   - Low Stock (≤10 items)
   - Backorder

5. **Brand Rules**
   - Equals
   - Does not equal
   - Is one of (multiple brands)
   - Is not one of

6. **Category Rules**
   - Equals
   - Does not equal
   - Is one of (multiple categories)
   - Is not one of

7. **Attribute Rules**
   - Equals
   - Does not equal
   - Is one of (multiple values)
   - Is not one of
   - Contains

8. **Rating Rules**
   - Greater than
   - Less than
   - Equals
   - Between (range)

9. **Date Rules**
   - Created date filters
   - Updated date filters
   - Greater than, less than, equals, between

### Advanced Features

1. **Rule Grouping**
   - Group rules with AND/OR logic
   - Multiple groups supported
   - Complex rule combinations

2. **Rule Priority**
   - Control rule execution order
   - Numeric priority system

3. **Active/Inactive Rules**
   - Enable/disable rules without deletion
   - Test rules before activation

4. **Rule Descriptions**
   - Add descriptions to rules
   - Document rule purpose

5. **Preview Functionality**
   - Preview products matching rules
   - See product count before processing
   - Test rule combinations

## Models

### SmartCollectionRule
- **Location**: `app/Models/SmartCollectionRule.php`
- **Table**: `lunar_smart_collection_rules`
- **Key Fields**:
  - `collection_id`: Collection the rule belongs to
  - `field`: Field to filter on (price, tag, product_type, etc.)
  - `operator`: Operator (equals, greater_than, etc.)
  - `value`: Filter value (JSON)
  - `logic_group`: Group identifier for AND/OR logic
  - `group_operator`: 'and' or 'or'
  - `priority`: Execution order
  - `is_active`: Enable/disable rule
  - `description`: Rule description

## Services

### SmartCollectionService
- **Location**: `app/Services/SmartCollectionService.php`
- **Methods**:
  - `processSmartCollection()`: Process rules and assign products
  - `applyRules()`: Apply all rules to query
  - `applyRule()`: Apply single rule
  - `applyPriceRule()`: Apply price filter
  - `applyTagRule()`: Apply tag filter
  - `applyProductTypeRule()`: Apply product type filter
  - `applyInventoryStatusRule()`: Apply inventory filter
  - `applyBrandRule()`: Apply brand filter
  - `applyCategoryRule()`: Apply category filter
  - `applyAttributeRule()`: Apply attribute filter
  - `applyRatingRule()`: Apply rating filter
  - `applyDateRule()`: Apply date filter
  - `processAllSmartCollections()`: Process all smart collections

## Controllers

### Admin\SmartCollectionRuleController
- **Location**: `app/Http/Controllers/Admin/SmartCollectionRuleController.php`
- **Methods**:
  - `index()`: Show rule builder interface
  - `store()`: Create new rule
  - `update()`: Update existing rule
  - `destroy()`: Delete rule
  - `preview()`: Preview rule results (AJAX)
  - `process()`: Process collection immediately

## Views

### Smart Rules Admin Page
- **Location**: `resources/views/admin/collections/smart-rules.blade.php`
- **Features**:
  - Rule builder form
  - Dynamic field/operator/value inputs
  - Rule list with grouping indicators
  - Preview modal
  - Process button
  - Rule editing and deletion

## Routes

```php
// Smart Collection Rules
Route::get('/collections/{collection}/smart-rules', [SmartCollectionRuleController::class, 'index']);
Route::post('/collections/{collection}/smart-rules', [SmartCollectionRuleController::class, 'store']);
Route::put('/collections/{collection}/smart-rules/{rule}', [SmartCollectionRuleController::class, 'update']);
Route::delete('/collections/{collection}/smart-rules/{rule}', [SmartCollectionRuleController::class, 'destroy']);
Route::get('/collections/{collection}/smart-rules/preview', [SmartCollectionRuleController::class, 'preview']);
Route::post('/collections/{collection}/smart-rules/process', [SmartCollectionRuleController::class, 'process']);
```

## Usage Examples

### Creating a Smart Collection

1. **Create Collection**
   ```php
   $collection = Collection::create([
       'name' => 'Premium Products',
       'collection_type' => 'custom',
       'auto_assign' => true,
   ]);
   ```

2. **Add Price Rule**
   ```php
   SmartCollectionRule::create([
       'collection_id' => $collection->id,
       'field' => 'price',
       'operator' => 'greater_than',
       'value' => 100.00,
       'priority' => 0,
       'is_active' => true,
   ]);
   ```

3. **Add Rating Rule**
   ```php
   SmartCollectionRule::create([
       'collection_id' => $collection->id,
       'field' => 'rating',
       'operator' => 'greater_than',
       'value' => 4.0,
       'priority' => 1,
       'is_active' => true,
   ]);
   ```

4. **Process Collection**
   ```php
   $service = app(SmartCollectionService::class);
   $count = $service->processSmartCollection($collection);
   ```

### Rule Grouping Example

```php
// Group 1: High-end products (AND)
SmartCollectionRule::create([
    'collection_id' => $collection->id,
    'field' => 'price',
    'operator' => 'greater_than',
    'value' => 500,
    'logic_group' => 'high_end',
    'group_operator' => 'and',
]);

SmartCollectionRule::create([
    'collection_id' => $collection->id,
    'field' => 'rating',
    'operator' => 'greater_than',
    'value' => 4.5,
    'logic_group' => 'high_end',
    'group_operator' => 'and',
]);

// Group 2: Popular products (OR)
SmartCollectionRule::create([
    'collection_id' => $collection->id,
    'field' => 'tag',
    'operator' => 'equals',
    'value' => 'bestseller',
    'logic_group' => 'popular',
    'group_operator' => 'or',
]);

SmartCollectionRule::create([
    'collection_id' => $collection->id,
    'field' => 'tag',
    'operator' => 'equals',
    'value' => 'trending',
    'logic_group' => 'popular',
    'group_operator' => 'or',
]);
```

## Rule Logic

### Basic Rules (AND)
All rules are combined with AND logic by default:
- Product must match ALL active rules

### Rule Groups (OR)
Rules in the same group with `group_operator = 'or'`:
- Product must match ANY rule in the group

### Complex Logic
- Groups are combined with AND
- Rules within groups use group operator (AND/OR)
- Example: (Rule1 AND Rule2) OR (Rule3 AND Rule4)

## Scheduled Processing

Smart collections are processed automatically by the scheduled command:

```php
// Runs hourly via scheduler
Schedule::command('collections:process-assignments')->hourly();
```

This processes:
1. Smart collections (with rules)
2. Auto-assign collections (with assignment_rules)
3. Removes expired assignments

## Performance Considerations

1. **Query Optimization**
   - Indexes on rule fields
   - Efficient joins for relationships
   - Limit max products per collection

2. **Caching**
   - Cache rule evaluation results
   - Cache product counts
   - Cache filter options

3. **Batch Processing**
   - Process collections in batches
   - Queue large operations
   - Background processing for large collections

## Best Practices

1. **Rule Design**
   - Keep rules simple and focused
   - Use rule groups for OR logic
   - Test rules before activating
   - Use descriptions to document rules

2. **Performance**
   - Limit number of rules per collection
   - Use efficient operators
   - Avoid complex nested groups
   - Set max products limit

3. **Maintenance**
   - Review rule effectiveness regularly
   - Update rules based on performance
   - Document complex rule logic
   - Monitor processing times

## Future Enhancements

1. **Advanced Features**
   - Rule templates
   - Rule import/export
   - Rule versioning
   - Rule testing interface

2. **Analytics**
   - Rule performance tracking
   - Product match rates
   - Rule effectiveness metrics
   - Conversion tracking

3. **UI Improvements**
   - Visual rule builder
   - Drag-and-drop rule ordering
   - Rule templates library
   - Bulk rule operations

