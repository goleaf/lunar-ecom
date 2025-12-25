<?php

namespace App\Admin\Livewire;

use App\Models\Category;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Str;

/**
 * Livewire component for managing category tree with drag-and-drop.
 */
class CategoryTreeManager extends Component implements HasForms
{
    use InteractsWithForms;

    public $categories = [];
    public $selectedCategory = null;
    public $showForm = false;
    public $editingCategory = null;

    // Form data
    public $name = [];
    public $slug = '';
    public $description = [];
    public $meta_title = '';
    public $meta_description = '';
    public $display_order = 0;
    public $is_active = true;
    public $show_in_navigation = true;
    public $icon = '';
    public $parent_id = null;

    public function mount()
    {
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $this->categories = Category::with('children')
            ->whereIsRoot()
            ->ordered()
            ->get()
            ->toTree()
            ->toArray();
    }

    public function createCategory()
    {
        $data = $this->form->getState();

        try {
            $category = Category::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?: null, // Will auto-generate if empty
                'description' => $data['description'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'display_order' => $data['display_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'show_in_navigation' => $data['show_in_navigation'] ?? true,
                'icon' => $data['icon'] ?? null,
            ]);

            // Set parent if provided
            if (!empty($data['parent_id'])) {
                $parent = Category::find($data['parent_id']);
                if ($parent) {
                    $parent->appendNode($category);
                }
            }

            // Handle image upload
            if (isset($data['image']) && $data['image']) {
                $category->addMediaFromDisk($data['image'], 'public')
                    ->toMediaCollection('image');
            }

            Notification::make()
                ->title('Category Created')
                ->success()
                ->body('Category has been created successfully.')
                ->send();

            $this->showForm = false;
            $this->resetForm();
            $this->loadCategories();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function updateCategory()
    {
        if (!$this->editingCategory) {
            return;
        }

        $data = $this->form->getState();

        try {
            $category = Category::find($this->editingCategory);
            
            $category->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?: null,
                'description' => $data['description'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'display_order' => $data['display_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'show_in_navigation' => $data['show_in_navigation'] ?? true,
                'icon' => $data['icon'] ?? null,
            ]);

            // Handle parent change
            if (isset($data['parent_id']) && $data['parent_id'] != $category->parent_id) {
                if ($data['parent_id']) {
                    $parent = Category::find($data['parent_id']);
                    if ($parent) {
                        $category->appendToNode($parent)->save();
                    }
                } else {
                    $category->makeRoot()->save();
                }
            }

            // Handle image upload
            if (isset($data['image']) && $data['image']) {
                $category->clearMediaCollection('image');
                $category->addMediaFromDisk($data['image'], 'public')
                    ->toMediaCollection('image');
            }

            Notification::make()
                ->title('Category Updated')
                ->success()
                ->body('Category has been updated successfully.')
                ->send();

            $this->showForm = false;
            $this->editingCategory = null;
            $this->resetForm();
            $this->loadCategories();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function deleteCategory($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            $category->delete();

            Notification::make()
                ->title('Category Deleted')
                ->success()
                ->body('Category has been deleted successfully.')
                ->send();

            $this->loadCategories();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function editCategory($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $this->editingCategory = $categoryId;
        
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description;
        $this->meta_title = $category->meta_title;
        $this->meta_description = $category->meta_description;
        $this->display_order = $category->display_order;
        $this->is_active = $category->is_active;
        $this->show_in_navigation = $category->show_in_navigation;
        $this->icon = $category->icon;
        $this->parent_id = $category->parent_id;

        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->name = [];
        $this->slug = '';
        $this->description = [];
        $this->meta_title = '';
        $this->meta_description = '';
        $this->display_order = 0;
        $this->is_active = true;
        $this->show_in_navigation = true;
        $this->icon = '';
        $this->parent_id = null;
        $this->editingCategory = null;
    }

    protected function getFormSchema(): array
    {
        $categories = Category::all()->mapWithKeys(function ($cat) {
            return [$cat->id => str_repeat('— ', $cat->depth) . $cat->getName()];
        })->prepend('Root (No Parent)', '');

        return [
            Section::make('Category Information')
                ->schema([
                    TextInput::make('name.en')
                        ->label('Name (English)')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (empty($this->slug)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->helperText('Auto-generated from name if left empty')
                        ->maxLength(255)
                        ->unique(Category::class, 'slug', ignoreRecord: $this->editingCategory),

                    Textarea::make('description.en')
                        ->label('Description (English)')
                        ->rows(4),

                    TextInput::make('meta_title')
                        ->label('Meta Title')
                        ->maxLength(255),

                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->rows(2)
                        ->maxLength(500),

                    Select::make('parent_id')
                        ->label('Parent Category')
                        ->options($categories)
                        ->searchable()
                        ->nullable(),

                    TextInput::make('display_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0),

                    TextInput::make('icon')
                        ->label('Icon')
                        ->helperText('Icon class name or SVG path')
                        ->maxLength(255),

                    FileUpload::make('image')
                        ->label('Category Image')
                        ->image()
                        ->directory('categories')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Toggle::make('show_in_navigation')
                        ->label('Show in Navigation')
                        ->default(true),
                ]),
        ];
    }

    public function reorderCategories($oldIndex, $newIndex, $categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            $siblings = $category->siblings()->defaultOrder()->get();
            
            if ($newIndex < $oldIndex) {
                // Moving up
                $targetSibling = $siblings[$newIndex] ?? null;
                if ($targetSibling) {
                    $category->insertBeforeNode($targetSibling);
                }
            } else {
                // Moving down
                $targetSibling = $siblings[$newIndex] ?? null;
                if ($targetSibling) {
                    $category->insertAfterNode($targetSibling);
                }
            }

            Notification::make()
                ->title('Category Reordered')
                ->success()
                ->body('Category order has been updated.')
                ->send();

            $this->loadCategories();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function render()
    {
        return view('admin.livewire.category-tree-manager');
    }
}

