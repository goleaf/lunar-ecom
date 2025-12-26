<script>
    // Provide a locale-aware URL builder so the global language selector can switch locale
    // while preserving referral code + query params.
    window.__localeSwitchUrlFor = function(languageCode) {
        try {
            const url = new URL(window.location.href);
            const parts = url.pathname.split('/').filter(Boolean); // [locale, 'r', code]
            if (parts.length >= 3 && parts[1] === 'r') {
                parts[0] = (languageCode || '').toLowerCase();
                url.pathname = '/' + parts.join('/');
                return url.toString();
            }
        } catch (e) {}
        return null;
    };
</script>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    You've been invited
                </h1>
                <p class="mt-2 text-gray-600">
                    Use this referral to shop with benefits. (Localized template content will be configurable in admin.)
                </p>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500">Referral code</div>
                <div class="font-mono text-sm bg-gray-100 rounded px-2 py-1 inline-block">{{ strtoupper($referrer?->referral_code ?: $code) }}</div>
            </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('storefront.homepage', ['ref' => strtoupper($referrer?->referral_code ?: $code)]) }}"
               class="inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Start shopping
            </a>
            <button
                type="button"
                class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-gray-800 rounded hover:bg-gray-50"
                onclick="navigator.clipboard.writeText('{{ $shareUrl }}').then(() => alert('Link copied')).catch(() => alert('Copy failed'))">
                Copy link
            </button>
        </div>

        <div class="mt-6 border-t pt-4">
            <div class="text-sm text-gray-700">
                <div class="font-medium">Language</div>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($languages as $lang)
                        <a
                            href="{{ route('storefront.referrals.landing', ['locale' => $lang['code'], 'code' => strtoupper($referrer?->referral_code ?: $code)]) . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}"
                            class="px-3 py-1 rounded border text-sm {{ $lang['code'] === app()->getLocale() ? 'bg-blue-50 border-blue-200 text-blue-700' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                            {{ strtoupper($lang['code']) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($program)
            <div class="mt-6 text-xs text-gray-500">
                Program: {{ $program->name }}
            </div>
        @endif
    </div>
</div>


