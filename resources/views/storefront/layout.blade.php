<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Lunar E-commerce Store')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <a href="{{ route('storefront.home') }}" class="flex items-center px-2 py-2 text-xl font-bold text-gray-900">
                        Lunar Store
                    </a>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('storefront.products.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                            Products
                        </a>
                        <a href="{{ route('storefront.collections.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                            Collections
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <form action="{{ route('storefront.search.index') }}" method="GET" class="mr-4">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..." class="border rounded px-3 py-1">
                    </form>
                    <a href="{{ route('storefront.cart.index') }}" class="text-gray-700 hover:text-gray-900">
                        Cart
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-600">&copy; {{ date('Y') }} Lunar Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>


