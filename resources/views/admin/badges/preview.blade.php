<span class="{{ $badge->getCssClasses() }}" style="{{ $badge->getInlineStyles() }}">
    @if($badge->show_icon && $badge->icon)
        <span class="mr-1">{{ $badge->icon }}</span>
    @endif
    {{ $badge->getDisplayLabel() }}
</span>
