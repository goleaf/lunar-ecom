<div>
    @if($show)
        @php
            $consentAction = route('gdpr.cookie-consent.store');
        @endphp
        <div
            x-data="{ showDetails: false }"
            class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-lg p-4 md:p-6"
        >
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Cookie Consent</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            We use cookies to enhance your browsing experience, analyze site traffic, and personalize content.
                            By clicking "Accept All", you consent to our use of cookies.
                            <a href="{{ route('gdpr.privacy-policy.show') }}" class="text-blue-600 hover:underline">Learn more</a>
                        </p>

                        <form id="cookie-consent-custom" method="POST" action="{{ $consentAction }}">
                            @csrf
                            <input type="hidden" name="necessary" value="1">
                            <input type="hidden" name="consent_method" value="banner">

                            <div x-show="showDetails" x-cloak class="mt-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-gray-900">Necessary Cookies</span>
                                        <p class="text-xs text-gray-500">Required for the website to function properly</p>
                                    </div>
                                    <span class="text-sm text-gray-600">Always Active</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-gray-900">Analytics Cookies</span>
                                        <p class="text-xs text-gray-500">Help us understand how visitors interact with our website</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="analytics" value="1" @checked($analytics) class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-gray-900">Marketing Cookies</span>
                                        <p class="text-xs text-gray-500">Used to deliver personalized advertisements</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="marketing" value="1" @checked($marketing) class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-gray-900">Preference Cookies</span>
                                        <p class="text-xs text-gray-500">Remember your preferences and settings</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="preferences" value="1" @checked($preferences) class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 md:ml-4">
                        <button
                            type="button"
                            x-on:click="showDetails = !showDetails"
                            x-bind:aria-expanded="showDetails.toString()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            <span x-show="!showDetails">Customize</span>
                            <span x-show="showDetails" x-cloak>Hide Details</span>
                        </button>

                        <form method="POST" action="{{ $consentAction }}">
                            @csrf
                            <input type="hidden" name="necessary" value="1">
                            <input type="hidden" name="analytics" value="0">
                            <input type="hidden" name="marketing" value="0">
                            <input type="hidden" name="preferences" value="0">
                            <input type="hidden" name="consent_method" value="banner">
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Necessary Only
                            </button>
                        </form>

                        <form method="POST" action="{{ $consentAction }}">
                            @csrf
                            <input type="hidden" name="necessary" value="1">
                            <input type="hidden" name="analytics" value="1">
                            <input type="hidden" name="marketing" value="1">
                            <input type="hidden" name="preferences" value="1">
                            <input type="hidden" name="consent_method" value="banner">
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                            >
                                Accept All
                            </button>
                        </form>

                        <button
                            type="submit"
                            form="cookie-consent-custom"
                            x-show="showDetails"
                            x-cloak
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-md hover:bg-gray-800"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


