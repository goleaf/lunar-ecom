<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Low Stock Alert</title>
</head>
<body>
    <h2>Low Stock Alert</h2>
    
    <p><strong>Product:</strong> {{ $product->translateAttribute('name') }}</p>
    <p><strong>Variant SKU:</strong> {{ $variant->sku }}</p>
    <p><strong>Warehouse:</strong> {{ $warehouse->name }} ({{ $warehouse->code }})</p>
    
    <p><strong>Current Quantity:</strong> {{ $alert->current_quantity }}</p>
    <p><strong>Reorder Point:</strong> {{ $alert->reorder_point }}</p>
    <p><strong>Suggested Reorder Quantity:</strong> {{ $alert->inventoryLevel->reorder_quantity ?? 'N/A' }}</p>
    
    <p>Please review and place a purchase order if needed.</p>
</body>
</html>


