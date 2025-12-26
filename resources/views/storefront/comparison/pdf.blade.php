<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Comparison</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .product-header {
            text-align: center;
            vertical-align: top;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
        .attribute-col {
            background-color: #f9f9f9;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Product Comparison</h1>
    <p>Generated on: {{ now()->format('F j, Y g:i A') }}</p>
    
    <table>
        <thead>
            <tr>
                <th class="attribute-col">Attribute</th>
                @foreach($comparisonTable['products'] as $product)
                    <th class="product-header">
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="product-image">
                        <h3>{{ $product['name'] }}</h3>
                        <p>${{ number_format($product['price'] / 100, 2) }}</p>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="attribute-col"><strong>SKU</strong></td>
                @foreach($comparisonTable['products'] as $product)
                    <td>{{ $product['sku'] ?? '—' }}</td>
                @endforeach
            </tr>
            <tr>
                <td class="attribute-col"><strong>Brand</strong></td>
                @foreach($comparisonTable['products'] as $product)
                    <td>{{ $product['brand'] ?? '—' }}</td>
                @endforeach
            </tr>
            <tr>
                <td class="attribute-col"><strong>Rating</strong></td>
                @foreach($comparisonTable['products'] as $product)
                    <td>{{ $product['rating'] ?? 0 }}/5 ({{ $product['total_reviews'] ?? 0 }} reviews)</td>
                @endforeach
            </tr>
            @foreach($comparisonTable['attributes'] as $attribute)
                <tr>
                    <td class="attribute-col"><strong>{{ $attribute['attribute_name'] }}</strong></td>
                    @foreach($attribute['values'] as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>


