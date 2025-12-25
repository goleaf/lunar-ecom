<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="text-sm font-medium text-gray-500">Total Clicks</div>
                <div class="text-2xl font-bold">{{ number_format($this->getSummary()['total_clicks'] ?? 0) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500">Total Signups</div>
                <div class="text-2xl font-bold">{{ number_format($this->getSummary()['total_signups'] ?? 0) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500">Total Revenue</div>
                <div class="text-2xl font-bold">€{{ number_format($this->getSummary()['total_revenue'] ?? 0, 2) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500">Conversion Rate</div>
                <div class="text-2xl font-bold">{{ number_format($this->getSummary()['avg_overall_conversion_rate'] ?? 0, 2) }}%</div>
            </x-filament::section>
        </div>

        <!-- Date Range Filter -->
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        <!-- Analytics Table -->
        <x-filament::section>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>

