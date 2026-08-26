<?php

namespace App\Http\Controllers\Discount;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Discount;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiscountController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Discount::class);

        return view('discounts.index', [
            'discounts' => Discount::withCount('usages')->orderBy('code')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Discount::class);

        return view('discounts.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Discount::class);

        Discount::create($this->validated($request));

        return redirect()
            ->route('discounts.index')
            ->with('success', __('ui.discount_created'));
    }

    public function edit(Discount $discount): View
    {
        $this->authorize('update', $discount);

        return view('discounts.edit', array_merge(['discount' => $discount], $this->formOptions()));
    }

    public function update(Request $request, Discount $discount): RedirectResponse
    {
        $this->authorize('update', $discount);

        $discount->update($this->validated($request, $discount->id));

        return redirect()
            ->route('discounts.index')
            ->with('success', __('ui.discount_updated'));
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $this->authorize('delete', $discount);

        $discount->delete();

        return redirect()
            ->route('discounts.index')
            ->with('success', __('ui.discount_deleted'));
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('discounts', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Discount::TYPES)],
            'value' => ['required', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'category_scope' => ['nullable', 'array'],
            'category_scope.*' => ['integer', 'exists:product_categories,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['branch_id'] = $data['branch_id'] ?? null;
        $data['category_scope'] = ! empty($data['category_scope'])
            ? ProductCategory::whereIn('id', $data['category_scope'])->pluck('type_slug')->all()
            : null;

        if (($data['type'] ?? null) === 'percent' && (float) $data['value'] > 100) {
            validator(['value' => $data['value']], ['value' => 'lte:100'])->validate();
        }

        return $data;
    }

    private function formOptions(): array
    {
        return [
            'branches' => Branch::active()->orderBy('name')->get(),
            'categories' => ProductCategory::orderBy('sort_order')->get(),
        ];
    }
}
