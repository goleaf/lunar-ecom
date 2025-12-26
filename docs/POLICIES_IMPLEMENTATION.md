# Authorization Policies Implementation

This document summarizes the complete authorization policy implementation for the Lunar e-commerce application.

## Overview

All authorization has been migrated from manual checks to Laravel Policies, providing a centralized, maintainable authorization system that supports both customer (`User`) and admin (`Staff`) authentication.

## Policy Files Created

### 1. ProductPolicy (`app/Policies/ProductPolicy.php`)
- **viewAny**: Staff can view all; users/guests can view published products
- **view**: Staff can view any; users/guests can only view published products
- **create**: Staff only (admin or `catalog:products:create` permission)
- **update**: Staff only (admin or `catalog:products:update` permission)
- **delete**: Staff only (admin or `catalog:products:delete` permission)
- **restore**: Staff only (admin or `catalog:products:restore` permission)
- **forceDelete**: Admin only

### 2. ProductVariantPolicy (`app/Policies/ProductVariantPolicy.php`)
- **viewAny**: Staff can view all; users/guests can view variants of published products
- **view**: Staff can view any; users/guests can only view variants of published products
- **create**: Staff only (admin or `catalog:variants:create` permission)
- **update**: Staff only (admin or `catalog:variants:update` permission)
- **delete**: Staff only (admin or `catalog:variants:delete` permission)
- **restore**: Staff only (admin or `catalog:variants:restore` permission)
- **forceDelete**: Admin only

### 3. CategoryPolicy (`app/Policies/CategoryPolicy.php`)
- **viewAny**: Staff can view all; users/guests can view active categories
- **view**: Staff can view any; users/guests can only view active categories
- **create**: Staff only (admin or `catalog:categories:create` permission)
- **update**: Staff only (admin or `catalog:categories:update` permission)
- **delete**: Staff only (admin or `catalog:categories:delete` permission)
- **restore**: Staff only (admin or `catalog:categories:restore` permission)
- **forceDelete**: Admin only

### 4. CollectionPolicy (`app/Policies/CollectionPolicy.php`)
- **viewAny**: Staff can view all; users/guests can view collections
- **view**: Staff can view any; users/guests can view collections
- **create**: Staff only (admin or `catalog:collections:create` permission)
- **update**: Staff only (admin or `catalog:collections:update` permission)
- **delete**: Staff only (admin or `catalog:collections:delete` permission)
- **restore**: Staff only (admin or `catalog:collections:restore` permission)
- **forceDelete**: Admin only

### 5. AddressPolicy (`app/Policies/AddressPolicy.php`)
- **viewAny**: Staff can view all; users can view their own addresses
- **view**: Staff can view any; users can only view their own addresses
- **create**: Staff can create for any customer; users can create their own
- **update**: Staff can update any; users can only update their own addresses
- **delete**: Staff can delete any; users can only delete their own addresses
- **setDefaultShipping**: Staff can set for any; users can only set for their own
- **setDefaultBilling**: Staff can set for any; users can only set for their own

### 6. UserPolicy (`app/Policies/UserPolicy.php`)
- **viewAny**: Staff only (admin or `customers:read` permission)
- **view**: Staff can view any; users can only view their own profile
- **create**: Staff only (admin or `customers:create` permission)
- **update**: Staff can update any; users can only update their own profile
- **delete**: Staff only (admin or `customers:delete` permission)
- **restore**: Staff only (admin or `customers:restore` permission)
- **forceDelete**: Admin only

### 7. OrderPolicy (`app/Policies/OrderPolicy.php`)
- **viewAny**: Staff can view all; users can view their own orders
- **view**: Staff can view any; users can only view their own orders (by user_id or customer_id)
- **create**: Staff can create orders; users can create orders (checkout)
- **update**: Staff only (admin or `orders:update` permission)
- **delete**: Staff only (admin or `orders:delete` permission)
- **cancel**: Staff can cancel any; users can cancel their own orders if not shipped/delivered
- **viewAny**: Staff only (admin or `customers:read` permission)
- **view**: Staff can view any; users can only view their own profile
- **create**: Staff only (admin or `customers:create` permission)
- **update**: Staff can update any; users can only update their own profile
- **delete**: Staff only (admin or `customers:delete` permission)
- **restore**: Staff only (admin or `customers:restore` permission)
- **forceDelete**: Admin only

## Infrastructure

### AuthServiceProvider (`app/Providers/AuthServiceProvider.php`)
- Registered all 7 policies
- Added to `bootstrap/providers.php`

### Base Controller (`app/Http/Controllers/Controller.php`)
- Added `AuthorizesRequests` trait to enable `$this->authorize()` in all controllers

## Controllers Updated

### Form Request Classes (5 files)
All form requests now use policy authorization:
- `StoreProductRequest`
- `UpdateProductRequest`
- `StoreVariantRequest`
- `UpdateVariantRequest`
- `GenerateVariantsRequest`

### Frontend Controllers (11 files)
1. **AddressController** - All methods (viewAny, view, create, update, delete, setDefaultShipping, setDefaultBilling)
2. **ProductController** - show method
3. **CategoryController** - show method
4. **CollectionController** - show method
5. **VariantController** - generate, store, update, destroy, updateStock, attachImage, detachImage, setPrimaryImage
6. **MediaController** - uploadProductImages, uploadCollectionImages, uploadBrandLogo, deleteMedia, reorderMedia
7. **ReviewController** - index, store methods
8. **ProductAssociationController** - store, destroy, index methods
9. **CartController** (Frontend) - add method
10. **CartController** (API) - addItem method
11. **CheckoutController** - confirmation method

### Admin Controllers (2 files)
1. **ReviewModerationController** - All methods (staff-only checks)
2. **SearchAnalyticsController** - All methods (staff-only checks)

### API Controllers (3 files)
1. **CollectionController** - store, show, update, destroy, addProducts, removeProducts
2. **CategoryController** - show method
3. **ProductVariantController** - store, update, destroy, updateStock
4. **VariantManagementController** - generateVariants, bulkUpdate

## Authorization Statistics

- **Total Policy Files**: 7
- **Total Authorization Checks**: 45+ across 15 controller files
- **Form Requests Updated**: 5
- **Controllers Updated**: 16

## Key Features

### Guest User Support
- View methods (`view`, `viewAny`) accept nullable users
- Guests can view published products, active categories, and collections
- Guests cannot perform any write operations

### Dual Authentication Support
- Policies support both `User` (customers) and `Staff` (admin) models
- Uses Spatie Permission for staff authorization
- Admin role has full access; staff members use granular permissions

### Ownership Checks
- Address operations check customer ownership
- User profile operations check self-ownership
- Staff members can override ownership checks

### Admin Protection
- Admin controllers require staff authentication
- Uses `auth('staff')->check()` for staff-only operations

## Permission Structure

### Catalog Permissions
- `catalog:products:read`, `create`, `update`, `delete`, `restore`
- `catalog:variants:read`, `create`, `update`, `delete`, `restore`
- `catalog:categories:read`, `create`, `update`, `delete`, `restore`
- `catalog:collections:read`, `create`, `update`, `delete`, `restore`

### Customer Permissions
- `customers:read`, `create`, `update`, `delete`, `restore`
- `customers:addresses:read`, `create`, `update`, `delete`

### Order Permissions
- `orders:read`, `create`, `update`, `delete`

## Usage Examples

### In Controllers
```php
// Check if user can view a product
$this->authorize('view', $product);

// Check if user can create a variant
$this->authorize('create', ProductVariant::class);

// Check if user can update their address
$this->authorize('update', $address);
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
```

## Testing Recommendations

1. Test guest access to public content (products, categories, collections)
2. Test customer access to their own resources (addresses, profile)
3. Test staff permissions for catalog management
4. Test admin full access
5. Test permission-based access for staff members
6. Test ownership checks (users can't access other users' addresses)

## Future Enhancements

Consider creating additional policies for:
- **BrandPolicy** - For brand management
- **ReviewPolicy** - For review moderation (currently handled by admin controllers)
- **SearchSynonymPolicy** - For search synonym management (currently handled by admin controllers)

Note: OrderPolicy has been implemented and is included in this implementation.

## Notes

- All policies follow Laravel conventions
- Policies are registered in `AuthServiceProvider`
- Guest users are supported for public viewing operations
- Staff authentication uses the `staff` guard
- Customer authentication uses the `web` guard (default)

