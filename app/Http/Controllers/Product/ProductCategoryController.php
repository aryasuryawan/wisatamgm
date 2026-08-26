<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ProductCategory::class);

        return view('product-categories.index', [
            'categories' => ProductCategory::withCount('products')->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ProductCategory::class);

        return view('product-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ProductCategory::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type_slug' => ['required', 'string', 'max:64', 'unique:product_categories,type_slug'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        ProductCategory::create($data);

        return redirect()
            ->route('product-categories.index')
            ->with('success', __('ui.category_created'));
    }

    public function edit(ProductCategory $productCategory): View
    {
        $this->authorize('update', $productCategory);

        return view('product-categories.edit', [
            'category' => $productCategory,
        ]);
    }

    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $this->authorize('update', $productCategory);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type_slug' => ['required', 'string', 'max:64', 'unique:product_categories,type_slug,' . $productCategory->id],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $productCategory->update($data);

        return redirect()
            ->route('product-categories.index')
            ->with('success', __('ui.category_updated'));
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $this->authorize('delete', $productCategory);

        if ($productCategory->products()->exists()) {
            return redirect()
                ->route('product-categories.index')
                ->with('error', __('ui.category_has_products'));
        }

        $productCategory->delete();

        return redirect()
            ->route('product-categories.index')
            ->with('success', __('ui.category_deleted'));
    }
}
