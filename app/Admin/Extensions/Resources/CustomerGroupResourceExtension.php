<?php

namespace App\Admin\Extensions\Resources;

use App\Admin\Support\Forms\Components\Attributes as AppAttributes;
use Filament\Forms\Form;
use Lunar\Admin\Support\Extending\ResourceExtension;
use Lunar\Admin\Support\Forms\Components\Attributes as LunarAttributes;

/**
 * Customer Group resource extension.
 *
 * Lunar Admin 1.2.1 / Filament v3 expects the Attributes component to always
 * provide array state during validation. In some cases the built-in component
 * can receive non-array state (e.g. an AttributeData object), which triggers a
 * TypeError. We swap it for our hardened implementation.
 */
class CustomerGroupResourceExtension extends ResourceExtension
{
    public function extendForm(Form $form): Form
    {
        $components = $form->getComponents(withHidden: true);

        $newComponents = [];
        foreach ($components as $component) {
            if ($component instanceof LunarAttributes) {
                $newComponents[] = AppAttributes::make();
                continue;
            }

            $newComponents[] = $component;
        }

        return $form->schema($newComponents);
    }
}


