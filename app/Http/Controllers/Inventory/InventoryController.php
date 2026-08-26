<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Inventory\Services\StockService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $this->authorize('inventory.view');

        $query = StockMovement::with(['product', 'user'])->orderByDesc('created_at');

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        return view('inventory.index', [
            'movements' => $query->paginate(20)->withQueryString(),
            'products' => Product::orderBy('name')->get(),
            'lowStockProducts' => Product::where('is_active', true)
                ->where('stock_quantity', '<=', 5)
                ->where('stock_quantity', '>', 0)
                ->orderBy('stock_quantity')
                ->get(),
            'outOfStockProducts' => Product::where('is_active', true)
                ->where('stock_quantity', 0)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('inventory.edit');

        return view('inventory.create', [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('inventory.edit');

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:in,adjustment'],
            'qty' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        if ($data['type'] === 'in') {
            $this->stock->stockIn($product, $data['qty'], $data['notes']);
        } else {
            $this->stock->adjust($product, $data['qty'], $data['notes']);
        }

        return redirect()
            ->route('inventory.index')
            ->with('success', __('ui.stock_updated'));
    }
}
