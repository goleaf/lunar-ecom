# Admin + Frontend Architecture

This project is organized into **two product surfaces**:

- **Admin**: Filament + Lunar Panel (back-office operations).
- **Frontend**: Livewire-driven customer-facing UI.

## Folder structure and namespaces

### Admin

- **Code**: `app/Admin/**`
- **Namespace**: `App\Admin\...`
- **Views**: `resources/views/admin/**`
- **Blade components**: `resources/views/components/admin/**`

### Frontend

- **Livewire components**: `app/Livewire/Frontend/**`
  - **Namespace**: `App\Livewire\Frontend\...`
  - **Livewire views**: `resources/views/livewire/frontend/**`
- **Controllers (endpoints/actions)**: `app/Http/Controllers/Frontend/**`
  - **Namespace**: `App\Http\Controllers\Frontend\...`
  - Use for downloads, JSON endpoints, and POST/PUT/DELETE actions.
- **Blade components (PHP classes)**: `app/View/Components/Frontend/**`
  - **Namespace**: `App\View\Components\Frontend\...`
  - **Blade component views**: `resources/views/components/frontend/**`
- **Page views**: `resources/views/frontend/**`

## Routing conventions

- **Frontend route names**: `frontend.*`
- **Admin route names**: `admin.*`

Frontend pages should be routed directly to Livewire page components:

- Example pattern: `Route::get('/path', SomePage::class)->name('frontend.some-page');`

## “Livewire-only” frontend rule

- **All new frontend pages must be Livewire components** (prefer `app/Livewire/Frontend/Pages/**`).
- Avoid adding new **GET** page controllers for frontend UI. Keep frontend controllers for non-page concerns.

## Translations

- Frontend translations: `resources/lang/<locale>/frontend.php`
- Access pattern: `__('frontend...')`

## Frontend session initialization

- Middleware: `App\Http\Middleware\FrontendSessionMiddleware` (registered in `bootstrap/app.php`)
- Helper: `App\Lunar\FrontendSession\FrontendSessionHelper`

Use the helper for app-level language/currency/session state rather than ad-hoc session keys.

## Naming rule: avoid legacy terminology

Do not introduce new code, folders, routes, views, or translation keys using legacy naming.

