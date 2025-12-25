<div id="cookie-consent-banner" 
     x-data="{ 
         show: false, 
         necessary: true, 
         analytics: false, 
         marketing: false, 
         preferences: false,
         showDetails: false,
         init() {
             this.checkConsent();
         },
         checkConsent() {
             fetch('{{ route('gdpr.cookie-consent.show') }}')
                 .then(response => response.json())
                 .then(data => {
                     if (!data.has_consented) {
                         this.show = true;
                     } else {
                         this.necessary = data.consent.necessary ?? true;
                         this.analytics = data.consent.analytics ?? false;
                         this.marketing = data.consent.marketing ?? false;
                         this.preferences = data.consent.preferences ?? false;
                     }
                 });
         },
         acceptAll() {
             this.analytics = true;
             this.marketing = true;
             this.preferences = true;
             this.save();
         },
         acceptNecessary() {
             this.analytics = false;
             this.marketing = false;
             this.preferences = false;
             this.save();
         },
         save() {
             fetch('{{ route('gdpr.cookie-consent.store') }}', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                 },
                 body: JSON.stringify({
                     necessary: this.necessary,
                     analytics: this.analytics,
                     marketing: this.marketing,
                     preferences: this.preferences
                 })
             })
             .then(response => response.json())
             .then(data => {
                 this.show = false;
                 location.reload();
             });
         }
     }"
     x-show="show"
     x-cloak
     class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-lg p-4 md:p-6"
     style="display: none;">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Cookie Consent</h3>
                <p class="text-sm text-gray-600 mb-4">
                    We use cookies to enhance your browsing experience, analyze site traffic, and personalize content. 
                    By clicking "Accept All", you consent to our use of cookies. 
                    <a href="{{ route('gdpr.privacy-policy.show') }}" class="text-blue-600 hover:underline">Learn more</a>
                </p>
                
                <div x-show="showDetails" class="mt-4 space-y-3">
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
                            <input type="checkbox" x-model="analytics" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-gray-900">Marketing Cookies</span>
                            <p class="text-xs text-gray-500">Used to deliver personalized advertisements</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="marketing" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-gray-900">Preference Cookies</span>
                            <p class="text-xs text-gray-500">Remember your preferences and settings</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="preferences" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-2 md:ml-4">
                <button 
                    @click="showDetails = !showDetails"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <span x-show="!showDetails">Customize</span>
                    <span x-show="showDetails">Hide Details</span>
                </button>
                <button 
                    @click="acceptNecessary()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Necessary Only
                </button>
                <button 
                    @click="acceptAll()"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Accept All
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

