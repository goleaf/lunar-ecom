# Policies Quick Reference Guide

## Policy Usage in Code

### In Controllers
```php
// Check if user can perform an action
$this->authorize('view', $product);
$this->authorize('create', Product::class);
$this->authorize('update', $address);
$this->authorize('delete', $variant);
```

### In Form Requests
```php
public function authorize(): bool
{
    $user = $this->user();
    if (!$user) {
        return false;
    }
    
    return Gate::forUser($user)->allows('create', Product::class);
}
```

### In Blade Templates
```blade
@can('update', $product)
    <a href="{{ route('products.edit', $product) }}">Edit</a>
@endcan

@can('delete', $address)
    <form method="POST" action="{{ route('addresses.destroy', $address) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endcan

@cannot('view', $order)
    <p>You don't have permission to view this order.</p>
@endcannot
```

## Available Policies

### ProductPolicy
- `viewAny($user)` - View list of products
- `view($user, $product)` - View a specific product
- `create($user)` - Create a new product
- `update($user, $product)` - Update a product
- `delete($user, $product)` - Delete a product
- `restore($user, $product)` - Restore a deleted product
- `forceDelete($user, $product)` - Permanently delete a product

### ProductVariantPolicy
- `viewAny($user)` - View list of variants
- `view($user, $variant)` - View a specific variant
- `create($user)` - Create a new variant
- `update($user, $variant)` - Update a variant
- `delete($user, $variant)` - Delete a variant
- `restore($user, $variant)` - Restore a deleted variant
- `forceDelete($user, $variant)` - Permanently delete a variant

### CategoryPolicy
- `viewAny($user)` - View list of categories
- `view($user, $category)` - View a specific category
- `create($user)` - Create a new category
- `update($user, $category)` - Update a category
- `delete($user, $category)` - Delete a category
- `restore($user, $category)` - Restore a deleted category
- `forceDelete($user, $category)` - Permanently delete a category

### CollectionPolicy
- `viewAny($user)` - View list of collections
- `view($user, $collection)` - View a specific collection
- `create($user)` - Create a new collection
- `update($user, $collection)` - Update a collection
- `delete($user, $collection)` - Delete a collection
- `restore($user, $collection)` - Restore a deleted collection
- `forceDelete($user, $collection)` - Permanently delete a collection

### AddressPolicy
- `viewAny($user)` - View list of addresses
- `view($user, $address)` - View a specific address
- `create($user)` - Create a new address
- `update($user, $address)` - Update an address
- `delete($user, $address)` - Delete an address
- `setDefaultShipping($user, $address)` - Set as default shipping address
- `setDefaultBilling($user, $address)` - Set as default billing address

### UserPolicy
- `viewAny($user)` - View list of users
- `view($user, $model)` - View a specific user
- `create($user)` - Create a new user
- `update($user, $model)` - Update a user
- `delete($user, $model)` - Delete a user
- `restore($user, $model)` - Restore a deleted user
- `forceDelete($user, $model)` - Permanently delete a user

### OrderPolicy
- `viewAny($user)` - View list of orders
- `view($user, $order)` - View a specific order
- `create($user)` - Create a new order
- `update($user, $order)` - Update an order
- `delete($user, $order)` - Delete an order
- `cancel($user, $order)` - Cancel an order

## Permission Structure

### Catalog Permissions
- `catalog:products:read`
- `catalog:products:create`
- `catalog:products:update`
- `catalog:products:delete`
- `catalog:products:restore`
- `catalog:variants:read`
- `catalog:variants:create`
- `catalog:variants:update`
- `catalog:variants:delete`
- `catalog:variants:restore`
- `catalog:categories:read`
- `catalog:categories:create`
- `catalog:categories:update`
- `catalog:categories:delete`
- `catalog:categories:restore`
- `catalog:collections:read`
- `catalog:collections:create`
- `catalog:collections:update`
- `catalog:collections:delete`
- `catalog:collections:restore`

### Customer Permissions
- `customers:read`
- `customers:create`
- `customers:update`
- `customers:delete`
- `customers:restore`
- `customers:addresses:read`
- `customers:addresses:create`
- `customers:addresses:update`
- `customers:addresses:delete`

### Order Permissions
- `orders:read`
- `orders:create`
- `orders:update`
- `orders:delete`

## Authorization Rules Summary

### Guest Users
- ✅ Can view published products
- ✅ Can view active categories
- ✅ Can view collections
- ✅ Can view variants of published products
- ❌ Cannot perform any write operations
- ❌ Cannot view orders or addresses

### Regular Users
- ✅ Can view published products, categories, collections
- ✅ Can manage their own addresses (CRUD)
- ✅ Can view their own orders
- ✅ Can create orders (checkout)
- ✅ Can cancel their own orders (if not shipped)
- ✅ Can update their own profile
- ❌ Cannot manage catalog items
- ❌ Cannot view other users' data

### Staff Members
- ✅ Can view all resources (with permissions)
- ✅ Can create/update/delete based on permissions
- ✅ Admin role has full access
- ✅ Uses granular permissions for access control

## Common Patterns

### Checking Ownership
```php
// In AddressPolicy
if ($user instanceof User) {
    $customer = CustomerHelper::getOrCreateCustomerForUser($user);
    return $address->customer_id === $customer->id;
}
```

### Checking Permissions
```php
// In ProductPolicy
if ($user instanceof Staff) {
    return $user->hasRole('admin') || $user->hasPermissionTo('catalog:products:update');
}
```

### Guest Access
```php
// In ProductPolicy
public function view(User|Staff|null $user, Product $product): bool
{
    if ($user instanceof Staff) {
        return $user->hasRole('admin') || $user->hasPermissionTo('catalog:products:read');
    }
    
    // Regular users and guests can only view published products
    return $product->status === 'published';
}
```

