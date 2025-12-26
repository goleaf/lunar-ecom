{{-- Responsive Image Component --}}
@php
    use App\Lunar\Media\MediaHelper;
    
    if (!isset($media) && isset($model)) {
        // If media is not provided, try to get from model
        $media = $model->getFirstMedia($collectionName ?? 'images');
    }
    
    $fallbackUrl = config('lunar.media.fallback.url');
    
    if (!$media) {
        // Fallback to placeholder
        if ($fallbackUrl) {
            echo '<img src="' . $fallbackUrl . '" alt="' . ($alt ?? 'Image') . '" class="' . ($class ?? '') . '">';
            return;
        }
        // No media and no fallback, show placeholder div
        echo '<div class="' . ($class ?? '') . ' bg-gray-200 flex items-center justify-center"><span class="text-gray-400">No Image</span></div>';
        return;
    }
    
    $responsiveAttrs = MediaHelper::getResponsiveAttributes($media, $sizeType ?? 'default');
    $src = $media->getUrl($conversion ?? 'medium');
    $srcset = $responsiveAttrs['srcset'] ?? '';
    $sizes = $responsiveAttrs['sizes'] ?? '';
@endphp

<img 
    src="{{ $src }}"
    @if($srcset) srcset="{{ $srcset }}" @endif
    @if($sizes) sizes="{{ $sizes }}" @endif
    alt="{{ $alt ?? ($media->name ?? 'Image') }}"
    class="{{ $class ?? '' }}"
    loading="{{ $loading ?? 'lazy' }}"
    @if(isset($width)) width="{{ $width }}" @endif
    @if(isset($height)) height="{{ $height }}" @endif
>

