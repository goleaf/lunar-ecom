<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Clicks</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($this->getStats()['clicks']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Signups</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($this->getStats()['signups']) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ number_format($this->getStats()['click_to_signup_rate'], 2) }}% conversion</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">First Purchases</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($this->getStats()['first_purchases']) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ number_format($this->getStats()['signup_to_purchase_rate'], 2) }}% conversion</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Overall Conversion</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($this->getStats()['click_to_purchase_rate'], 2) }}%</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Click → Purchase</p>
            </div>
        </div>

        <!-- Revenue & Cost -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenue</h3>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">€{{ number_format($this->getStats()['revenue'], 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Cost</h3>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">€{{ number_format($this->getStats()['cost'], 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">ROI</h3>
                <p class="text-3xl font-bold {{ $this->getStats()['roi'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $this->getStats()['roi'] >= 0 ? '+' : '' }}{{ number_format($this->getStats()['roi'], 2) }}%
                </p>
            </div>
        </div>

        <!-- Funnel Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Conversion Funnel (Last 30 Days)</h2>
            <div class="space-y-4">
                @foreach($this->getFunnelData() as $stage)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">{{ $stage['stage'] }}</span>
                            <span class="text-sm text-gray-500">{{ number_format($stage['count']) }} ({{ number_format($stage['percentage'], 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-blue-600 h-4 rounded-full" style="width: {{ $stage['percentage'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Top Referrers -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Top Referrers</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Referrals</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->getTopReferrers() as $referrer)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $referrer['user']->email ?? 'N/A' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $referrer['count'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">€{{ number_format($referrer['revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
