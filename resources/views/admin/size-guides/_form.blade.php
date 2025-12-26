@php
    $sizeGuideModel = $sizeGuide ?? null;
    $supportedRegions = old('supported_regions', $sizeGuideModel->supported_regions ?? []);
    $sizeLabels = old('size_labels', $sizeGuideModel->size_labels ?? []);
    $supportedRegions = is_array($supportedRegions) ? $supportedRegions : [];
    $sizeLabels = is_array($sizeLabels) ? $sizeLabels : [];
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $sizeGuideModel->name ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Region</label>
            <input type="text" name="region" value="{{ old('region', $sizeGuideModel->region ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Measurement unit</label>
            <select name="measurement_unit" class="w-full rounded border border-slate-300 px-3 py-2" required>
                <option value="cm" {{ old('measurement_unit', $sizeGuideModel->measurement_unit ?? '') === 'cm' ? 'selected' : '' }}>cm</option>
                <option value="inches" {{ old('measurement_unit', $sizeGuideModel->measurement_unit ?? '') === 'inches' ? 'selected' : '' }}>inches</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Size system</label>
            <select name="size_system" class="w-full rounded border border-slate-300 px-3 py-2" required>
                @foreach(['us', 'eu', 'uk', 'asia', 'custom'] as $system)
                    <option value="{{ $system }}" {{ old('size_system', $sizeGuideModel->size_system ?? '') === $system ? 'selected' : '' }}>{{ strtoupper($system) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
            <select name="category_id" class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="">None</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) old('category_id', $sizeGuideModel->category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->translateAttribute('name') ?? $category->name ?? 'Category' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Brand</label>
            <select name="brand_id" class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="">None</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ (string) old('brand_id', $sizeGuideModel->brand_id ?? '') === (string) $brand->id ? 'selected' : '' }}>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
        <textarea name="description" rows="3" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('description', $sizeGuideModel->description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Supported regions</label>
            @for($i = 0; $i < 3; $i++)
                <input type="text" name="supported_regions[]" value="{{ $supportedRegions[$i] ?? '' }}" class="w-full rounded border border-slate-300 px-3 py-2 mb-2" placeholder="e.g. US">
            @endfor
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Size labels</label>
            @for($i = 0; $i < 3; $i++)
                <input type="text" name="size_labels[]" value="{{ $sizeLabels[$i] ?? '' }}" class="w-full rounded border border-slate-300 px-3 py-2 mb-2" placeholder="e.g. S">
            @endfor
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Display order</label>
            <input type="number" name="display_order" value="{{ old('display_order', $sizeGuideModel->display_order ?? 0) }}" class="w-full rounded border border-slate-300 px-3 py-2" min="0">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Guide image</label>
        <input type="file" name="image" class="w-full rounded border border-slate-300 px-3 py-2">
    </div>

    <div class="flex flex-wrap gap-6">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active', $sizeGuideModel->is_active ?? true) ? 'checked' : '' }}>
            Active
        </label>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save size guide</button>
    </div>
</div>
