<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('category')->orderBy('name');

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('products.index', [
            'products' => $query->paginate(20)->withQueryString(),
            'categories' => ProductCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', [
            'categories' => ProductCategory::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validate([
            'category_id' => ['required', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:32'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product = Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('success', __('ui.product_created'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
            'categories' => ProductCategory::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'category_id' => ['required', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:32'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('success', __('ui.product_updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', __('ui.product_deleted'));
    }
}
