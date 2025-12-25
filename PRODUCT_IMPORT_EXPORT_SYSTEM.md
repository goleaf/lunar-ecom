# Product Import/Export System

## Overview

A comprehensive CSV/Excel import and export system for products with bulk operations, field mapping, validation, progress tracking, and error reporting.

## Features

### Import Features

1. **CSV File Upload**
   - Support for CSV and TXT files
   - Maximum file size: 10MB
   - Automatic header detection
   - Template download available

2. **Field Mapping**
   - Map CSV columns to product fields
   - Support for all product attributes
   - Support for variant fields
   - Support for categories and collections
   - Skip columns option

3. **Bulk Operations**
   - Process multiple products at once
   - Queue-based processing for large files
   - Progress tracking
   - Background job processing

4. **Validation**
   - Row-level validation
   - Field-level error reporting
   - Duplicate detection
   - Required field checking

5. **Progress Tracking**
   - Real-time progress updates
   - Success/failure counts
   - Processing status
   - Time estimates

6. **Error Reporting**
   - Detailed error messages
   - Row-by-row error tracking
   - Error type classification
   - Error summary statistics

7. **Import Options**
   - Update existing products (by SKU)
   - Skip errors and continue
   - Cancel running imports

### Export Features

1. **CSV Export**
   - Export all products or filtered subset
   - Selectable fields
   - UTF-8 encoding with BOM
   - Proper CSV formatting

2. **Filtering**
   - Filter by status
   - Filter by brand
   - Filter by product type
   - Filter by category
   - Filter by collection

3. **Field Selection**
   - Choose which fields to export
   - Include variant information
   - Include category/collection data
   - Include SEO fields

## Models

### ProductImport
- **Location**: `app/Models/ProductImport.php`
- **Table**: `lunar_product_imports`
- **Key Fields**:
  - `user_id`: User who started the import
  - `file_path`: Path to uploaded file
  - `file_name`: Original filename
  - `file_size`: File size in bytes
  - `total_rows`: Total number of rows
  - `processed_rows`: Number of rows processed
  - `successful_rows`: Number of successful imports
  - `failed_rows`: Number of failed imports
  - `status`: Import status (pending, processing, completed, failed, cancelled)
  - `field_mapping`: CSV column to field mapping
  - `options`: Import options
  - `error_summary`: Summary of errors by type

### ProductImportError
- **Location**: `app/Models/ProductImportError.php`
- **Table**: `lunar_product_import_errors`
- **Key Fields**:
  - `import_id`: Related import
  - `row_number`: Row number in CSV
  - `field`: Field that caused error
  - `error_message`: Error message
  - `error_type`: Error type (validation, duplicate, missing, etc.)
  - `row_data`: The actual row data

## Services

### ProductImportService
- **Location**: `app/Services/ProductImportService.php`
- **Methods**:
  - `startImport()`: Start a new import
  - `countRows()`: Count rows in CSV file
  - `getAvailableFields()`: Get available field mappings
  - `getImportStatus()`: Get import progress status
  - `cancelImport()`: Cancel running import
  - `getImportErrors()`: Get import errors

### ProductExportService
- **Location**: `app/Services/ProductExportService.php`
- **Methods**:
  - `exportToCsv()`: Export products to CSV
  - `getProducts()`: Get products with filters
  - `getDefaultFields()`: Get default export fields
  - `formatProductRow()`: Format product for CSV
  - `getFieldValue()`: Get field value for export

## Jobs

### ProcessProductImport
- **Location**: `app/Jobs/ProcessProductImport.php`
- **Features**:
  - Queue-based processing
  - Row-by-row processing
  - Transaction-based updates
  - Error handling and logging
  - Progress updates

## Controllers

### Admin\ProductImportController
- **Location**: `app/Http/Controllers/Admin/ProductImportController.php`
- **Methods**:
  - `index()`: Display import/export page
  - `import()`: Start product import
  - `status()`: Get import status (AJAX)
  - `errors()`: Get import errors (AJAX)
  - `cancel()`: Cancel import
  - `export()`: Export products to CSV
  - `downloadTemplate()`: Download CSV template

## Routes

```php
Route::prefix('admin/products')->name('admin.products.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/import-export', [ProductImportController::class, 'index'])->name('import-export');
    Route::post('/import', [ProductImportController::class, 'import'])->name('import');
    Route::get('/imports/{import}/status', [ProductImportController::class, 'status'])->name('imports.status');
    Route::get('/imports/{import}/errors', [ProductImportController::class, 'errors'])->name('imports.errors');
    Route::post('/imports/{import}/cancel', [ProductImportController::class, 'cancel'])->name('imports.cancel');
    Route::post('/export', [ProductImportController::class, 'export'])->name('export');
    Route::get('/import-template', [ProductImportController::class, 'downloadTemplate'])->name('import-template');
});
```

## Frontend

### Import/Export Page
- **Location**: `resources/views/admin/products/import-export.blade.php`
- **Features**:
  - File upload form
  - Dynamic field mapping
  - Import options
  - Real-time progress tracking
  - Import history table
  - Error viewing modal
  - Export form with filters

## Available Fields

### Product Fields
- `name`: Product Name
- `description`: Description
- `sku`: SKU
- `barcode`: Barcode
- `status`: Status
- `brand_id`: Brand ID
- `product_type_id`: Product Type ID

### Custom Fields
- `weight`: Weight (grams)
- `length`: Length (cm)
- `width`: Width (cm)
- `height`: Height (cm)
- `manufacturer_name`: Manufacturer
- `warranty_period`: Warranty Period (months)
- `condition`: Condition
- `origin_country`: Origin Country

### Variant Fields
- `variant_sku`: Variant SKU
- `variant_price`: Variant Price
- `variant_stock`: Variant Stock
- `variant_barcode`: Variant Barcode

### Relationships
- `categories`: Categories (comma-separated)
- `collections`: Collections (comma-separated)

### SEO
- `meta_title`: Meta Title
- `meta_description`: Meta Description

## Usage Examples

### Import Products

1. **Download Template**
   ```bash
   GET /admin/products/import-template
   ```

2. **Fill CSV File**
   - Use template as reference
   - Fill in product data
   - Map columns to fields

3. **Upload and Import**
   - Upload CSV file
   - Map CSV columns to product fields
   - Select import options
   - Start import

4. **Monitor Progress**
   - View real-time progress
   - Check success/failure counts
   - Review errors if any

### Export Products

1. **Set Filters** (Optional)
   - Filter by status
   - Filter by brand
   - Filter by category

2. **Select Fields**
   - Choose fields to export
   - Include variant data
   - Include relationships

3. **Export**
   - Click "Export to CSV"
   - Download generated file

## Error Handling

### Error Types
- **validation**: Validation errors
- **duplicate**: Duplicate SKU/identifier
- **missing**: Required field missing
- **other**: Other errors

### Error Reporting
- Row-by-row error tracking
- Field-level error messages
- Error summary by type
- Detailed error modal

## Best Practices

1. **File Preparation**
   - Use template as starting point
   - Validate data before import
   - Check for duplicates
   - Ensure required fields are filled

2. **Field Mapping**
   - Map all required fields
   - Verify column names match
   - Test with small file first

3. **Import Options**
   - Use "Update existing" for updates
   - Use "Skip errors" for large imports
   - Monitor progress regularly

4. **Error Resolution**
   - Review error messages
   - Fix data issues
   - Re-import corrected rows

5. **Export**
   - Export regularly for backups
   - Use filters to export subsets
   - Select only needed fields

## Dependencies

- **league/csv**: CSV reading/writing
- **Laravel Queue**: Background job processing
- **Laravel Storage**: File management

## Future Enhancements

1. **Excel Support**
   - XLSX file support
   - Multiple sheet support
   - Excel template generation

2. **Advanced Mapping**
   - Save mapping presets
   - Import mapping configurations
   - Field transformation rules

3. **Scheduled Imports**
   - Automatic imports
   - FTP/SFTP integration
   - API-based imports

4. **Validation Rules**
   - Custom validation rules
   - Field-level rules
   - Cross-field validation

5. **Import Preview**
   - Preview before import
   - Validate before processing
   - Show sample data

