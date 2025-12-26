<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $productName }} is Back in Stock!</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <table role="presentation" style="width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #3b82f6; padding: 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">
                                {{ $isLimited ? '⚡ Limited Quantity Available!' : 'Great News!' }}
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #1f2937; font-size: 20px;">
                                {{ $productName }} is Back in Stock!
                            </h2>
                            
                            @if($isLimited)
                            <p style="margin: 0 0 20px 0; color: #92400e; background-color: #fef3c7; padding: 15px; border-radius: 6px; font-weight: 600;">
                                ⚠️ Limited quantity available - don't miss out!
                            </p>
                            @endif
                            
                            @if(isset($productImage) && $productImage)
                            <div style="text-align: center; margin: 20px 0;">
                                <img src="{{ $productImage }}" alt="{{ $productName }}" style="max-width: 300px; height: auto; border-radius: 8px;">
                            </div>
                            @endif
                            
                            <p style="margin: 0 0 20px 0; color: #4b5563; font-size: 16px; line-height: 1.6;">
                                Great news! <strong>{{ $productName }}</strong> is back in stock and ready to order!
                            </p>
                            
                            <div style="background-color: #f9fafb; padding: 20px; border-radius: 6px; margin: 20px 0; text-align: center;">
                                <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">Price</p>
                                <p style="margin: 0; color: #3b82f6; font-size: 28px; font-weight: 600;">{{ $formattedPrice }}</p>
                            </div>
                            
                            <!-- Buy Now Button -->
                            <table role="presentation" style="width: 100%; margin: 30px 0;">
                                <tr>
                                    <td style="text-align: center;">
                                        <a href="{{ $buyNowUrl }}" style="display: inline-block; background-color: #3b82f6; color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                            Buy Now
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- View Product Link -->
                            <p style="margin: 20px 0 0 0; text-align: center;">
                                <a href="{{ $productUrl }}" style="color: #3b82f6; text-decoration: none;">View Product Details →</a>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 12px;">
                                Thank you for your interest!
                            </p>
                            <p style="margin: 0; font-size: 12px;">
                                <a href="{{ $unsubscribeUrl }}" style="color: #6b7280; text-decoration: underline;">Unsubscribe from back-in-stock notifications</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <!-- Tracking Pixel -->
    @if(isset($trackingPixelUrl))
    <img src="{{ $trackingPixelUrl }}" width="1" height="1" style="display:none;" alt="">
    @endif
</body>
</html>


