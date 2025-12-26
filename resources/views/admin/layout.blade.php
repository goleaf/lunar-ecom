<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Portal')</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @stack('styles')
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-900" data-app="admin">
<div class="min-h-screen flex">
    <aside class="w-64 bg-slate-900 text-slate-100 flex flex-col">
        <div class="p-6 border-b border-slate-800">
            <a href="{{ route('admin.bundles.index') }}" class="text-xl font-semibold">{{ config('app.name', 'Store') }} Admin</a>
            <p class="text-xs text-slate-400 mt-1">Commerce operations</p>
        </div>
        <nav class="flex-1 px-4 py-4 space-y-1 text-sm">
            <p class="text-xs uppercase tracking-wide text-slate-400 px-2 mt-2">Operations</p>
            <a href="{{ route('admin.bundles.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Bundles</a>
            <a href="{{ route('admin.products.import-export') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Product import/export</a>
            <a href="{{ route('admin.products.questions.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Product Q&A</a>
            <a href="{{ route('admin.reviews.moderation') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Review moderation</a>
            <a href="{{ route('admin.stock.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Stock management</a>
            <a href="{{ route('admin.inventory.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Inventory levels</a>
            <a href="{{ route('admin.checkout-locks.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Checkout locks</a>

            <p class="text-xs uppercase tracking-wide text-slate-400 px-2 mt-6">Merchandising</p>
            <a href="{{ route('admin.badges.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Badges</a>
            <a href="{{ route('admin.badges.rules.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Badge rules</a>
            <a href="{{ route('admin.size-guides.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Size guides</a>
            <a href="{{ route('admin.customizations.templates') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Customization templates</a>
            <a href="{{ route('admin.schedules.calendar') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Schedule calendar</a>

            <p class="text-xs uppercase tracking-wide text-slate-400 px-2 mt-6">Insights</p>
            <a href="{{ route('admin.comparison-analytics.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Comparison analytics</a>
            <a href="{{ route('admin.stock-notifications.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Stock notifications</a>
            @if (Route::has('admin.search-analytics.index'))
                <a href="{{ route('admin.search-analytics.index') }}" class="block px-3 py-2 rounded hover:bg-slate-800">Search analytics</a>
            @endif
        </nav>
        <div class="p-4 border-t border-slate-800 text-xs text-slate-400">
            <div class="font-semibold text-slate-300">Session</div>
            <div class="mt-1">
                {{ optional(auth('staff')->user())->email ?? optional(auth()->user())->email ?? 'Signed in' }}
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white border-b border-slate-200">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">@yield('title', 'Admin')</h1>
                    @hasSection('subtitle')
                        <p class="text-sm text-slate-500">@yield('subtitle')</p>
                    @endif
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('frontend.homepage') }}" class="text-blue-600 hover:text-blue-700">View frontend</a>
                </div>
            </div>
        </header>

        <main class="flex-1 p-6">
            @if(session('success'))
                <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>

