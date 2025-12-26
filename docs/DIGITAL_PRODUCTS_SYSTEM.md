# Digital Products Support System

## Overview

A comprehensive system for managing digital/downloadable products with secure download links, download limits, expiration dates, and automatic delivery after purchase completion.

## Features

### Core Features

1. **Digital Product Configuration**
   - Mark product variants as digital
   - Upload and manage digital files
   - Configure download limits (unlimited or specific count)
   - Set expiration dates (never expires or X days)
   - Require login for downloads
   - Auto-delivery after purchase
   - Email notifications with download links

2. **Secure Download Links**
   - Unique 64-character tokens
   - One-time or limited-use links
   - Expiration date support
   - Customer-specific access
   - Download logging and tracking

3. **Automatic Delivery**
   - Automatic delivery when order is completed/paid
   - Email notifications with download links
   - Direct purchase links in emails
   - Resend download emails

4. **Download Management**
   - Customer download dashboard
   - Download history and tracking
   - Download limits enforcement
   - Expiration date checking
   - File access logging

## Models

### DigitalProduct
- **Location**: `app/Models/DigitalProduct.php`
- **Table**: `lunar_digital_products`
- **Key Fields**:
  - `product_variant_id`: Associated variant
  - `is_digital`: Digital product flag
  - `download_limit`: Maximum downloads (null = unlimited)
  - `download_expiry_days`: Days until link expires (null = never)
  - `require_login`: Require customer login
  - `storage_disk`: File storage disk (local, s3, etc.)
  - `file_path`: Path to digital file
  - `file_name`: Original filename
  - `file_size`: File size in bytes
  - `file_type`: MIME type
  - `auto_deliver`: Auto-deliver after purchase
  - `send_email`: Send email notification

### DownloadLink
- **Location**: `app/Models/DownloadLink.php`
- **Table**: `lunar_download_links`
- **Key Fields**:
  - `order_id`, `order_line_id`, `product_variant_id`
  - `customer_id`, `email`
  - `token`: Secure download token (64 chars)
  - `download_count`: Number of downloads
  - `download_limit`: Override from digital_products
  - `expires_at`: Expiration timestamp
  - `last_downloaded_at`: Last download time
  - `is_active`: Active status
  - `delivered_at`: Delivery timestamp

### DownloadLog
- **Location**: `app/Models/DownloadLog.php`
- **Table**: `lunar_download_logs`
- **Key Fields**:
  - `download_link_id`
  - `customer_id`
  - `ip_address`, `user_agent`, `referer`
  - `success`: Download success status
  - `error_message`: Error details
  - `downloaded_at`: Download timestamp

## Services

### DigitalProductService
- **Location**: `app/Services/DigitalProductService.php`
- **Methods**:
  - `createOrUpdateDigitalProduct()`: Configure digital product
  - `uploadFile()`: Upload digital file
  - `createDownloadLink()`: Create secure download link
  - `deliverOrderDigitalProducts()`: Deliver all digital products for order
  - `getDownloadStream()`: Get file download stream
  - `getCustomerDownloads()`: Get customer's downloads
  - `getDownloadsByEmail()`: Get downloads by email

## Controllers

### Storefront\DownloadController
- `index()`: Display customer downloads
- `download()`: Download file via secure token
- `resendEmail()`: Resend download email

## Notifications

### DigitalProductDelivered
- **Location**: `app/Notifications/DigitalProductDelivered.php`
- **Features**:
  - Queued email notification
  - Product details and order reference
  - Direct download link
  - Download limit information
  - Expiration date information

## Event Listeners

### DeliverDigitalProducts
- **Location**: `app/Listeners/DeliverDigitalProducts.php`
- **Event**: `OrderStatusChanged`
- **Action**: Automatically deliver digital products when order is paid/completed

## Routes

```php
Route::prefix('downloads')->name('frontend.downloads.')->middleware('auth')->group(function () {
    Route::get('/', [DownloadController::class, 'index'])->name('index');
    Route::get('/{token}', [DownloadController::class, 'download'])->name('download');
    Route::post('/{downloadLink}/resend-email', [DownloadController::class, 'resendEmail'])->name('resend-email');
});
```

## Frontend Components

### Downloads Index
- **Location**: `resources/views/storefront/downloads/index.blade.php`
- **Features**:
  - List all customer downloads
  - Show download status and limits
  - Display expiration dates
  - Download buttons
  - Resend email functionality

## Usage Examples

### Configure Digital Product
```php
use App\Services\DigitalProductService;

$service = app(DigitalProductService::class);

$digitalProduct = $service->createOrUpdateDigitalProduct($variant, [
    'is_digital' => true,
    'download_limit' => 5, // 5 downloads max
    'download_expiry_days' => 30, // Expires in 30 days
    'require_login' => true,
    'auto_deliver' => true,
    'send_email' => true,
]);
```

### Upload Digital File
```php
$digitalProduct = $service->uploadFile(
    $variant,
    $request->file('digital_file'),
    'local' // or 's3', etc.
);
```

### Manual Delivery
```php
$service = app(DigitalProductService::class);
$delivered = $service->deliverOrderDigitalProducts($order);
```

### Get Customer Downloads
```php
$downloads = $service->getCustomerDownloads($customerId);
```

## Automatic Delivery

Digital products are automatically delivered when:
1. Order status changes to `paid`, `completed`, or `shipped`
2. `OrderStatusChanged` event is fired
3. `DeliverDigitalProducts` listener processes the order
4. Download links are created and emails are sent

## Security Features

1. **Secure Tokens**
   - 64-character random tokens
   - Unique per download link
   - Cannot be guessed

2. **Access Control**
   - Login requirement (optional)
   - Customer ownership verification
   - Active status checking

3. **Download Limits**
   - Enforce maximum downloads
   - Track download count
   - Prevent abuse

4. **Expiration**
   - Time-based expiration
   - Automatic invalidation
   - Clear expiration messages

5. **Download Logging**
   - Track all download attempts
   - Log IP addresses and user agents
   - Success/failure tracking

## File Storage

### Supported Disks
- `local`: Local filesystem
- `s3`: Amazon S3
- `public`: Public filesystem
- Any Laravel storage disk

### File Management
- Files stored in `digital-products/` directory
- Original filenames preserved
- File size and type tracked
- Automatic cleanup on update

## Email Notifications

### Delivery Email Includes
- Product name
- Order reference
- Direct download link
- Download limit (if set)
- Expiration date (if set)
- Store branding

### Resend Email
- Customers can request email resend
- Same download link (not regenerated)
- Useful if email is lost

## Best Practices

1. **File Security**
   - Store files outside public directory
   - Use secure download tokens
   - Verify customer ownership

2. **Download Limits**
   - Set reasonable limits (3-5 downloads)
   - Allow unlimited for premium products
   - Track and enforce limits

3. **Expiration Dates**
   - Set expiration for time-sensitive content
   - Allow unlimited for evergreen content
   - Clear expiration messaging

4. **Email Delivery**
   - Send immediately after purchase
   - Include clear instructions
   - Provide support contact

5. **File Management**
   - Use cloud storage for large files
   - Optimize file sizes
   - Support multiple file types

## Future Enhancements

1. **Multiple Files**
   - Support multiple files per product
   - Zip file generation
   - File versioning

2. **Streaming**
   - Video/audio streaming
   - Progressive download
   - DRM protection

3. **License Management**
   - License key generation
   - License validation
   - License activation tracking

4. **Watermarking**
   - PDF watermarking
   - Image watermarking
   - Customer-specific watermarks

5. **Analytics**
   - Download analytics
   - Conversion tracking
   - Customer behavior analysis


