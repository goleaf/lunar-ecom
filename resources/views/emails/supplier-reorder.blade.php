<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reorder Request</title>
</head>
<body>
    <h2>Reorder Request</h2>
    
    <p>This is an automated reorder request for the following product:</p>
    
    <table style="border-collapse: collapse; width: 100%;">
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Supplier SKU:</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $hook->supplier_sku ?? $variant->sku }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Product Name:</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $variant->product->translateAttribute('name') }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Current Quantity:</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $currentQuantity }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Reorder Point:</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $hook->reorder_point }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Requested Quantity:</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $quantity }}</td>
        </tr>
        @if($hook->unit_cost)
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Unit Cost:</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">${{ number_format($hook->unit_cost, 2) }}</td>
        </tr>
        @endif
        @if($hook->warehouse)
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong>Warehouse:</strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $hook->warehouse->name }}</td>
        </tr>
        @endif
    </table>
    
    <p style="margin-top: 20px;">
        <strong>Please confirm receipt of this reorder request.</strong>
    </p>
    
    <p>
        This is an automated message. Please do not reply to this email.
    </p>
</body>
</html>


