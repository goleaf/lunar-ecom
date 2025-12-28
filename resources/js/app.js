import './bootstrap';

import Alpine from 'alpinejs';
import './frontend/inline';
import './components/ProductAttributeFilter';
import './collection-filters';
import './admin/inline';
import './admin/bundles-form';

window.Alpine = Alpine;
Alpine.start();

// Load homepage-only code on demand (keeps main bundle smaller for other pages).
if (document.querySelector('.homepage')) {
    import('./homepage');
}
