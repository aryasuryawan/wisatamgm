<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\EquipmentUnit;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', EquipmentUnit::class);

        $query = EquipmentUnit::with(['product.category', 'branch'])
            ->orderBy('code');

        if (auth()->user()->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id');
            $query->whereIn('branch_id', $branchIds);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return view('equipment.index', [
            'units' => $query->paginate(20)->withQueryString(),
            'branches' => Branch::active()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', EquipmentUnit::class);

        $branches = auth()->user()->hasRole('admin-cabang')
            ? auth()->user()->branches()->where('is_active', true)->get()
            : Branch::active()->orderBy('name')->get();

        return view('equipment.create', [
            'products' => Product::whereHas('category', fn ($q) => $q->where('type_slug', 'sewa-alat'))
                ->orderBy('name')
                ->get(),
            'branches' => $branches,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EquipmentUnit::class);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:64', Rule::unique('equipment_units')
                ->where(fn ($q) => $q->where('branch_id', $request->input('branch_id')))],
            'condition' => ['required', 'in:good,fair,poor,damaged'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $unit = EquipmentUnit::create($data);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'equipment_created',
            'model_type' => EquipmentUnit::class,
            'model_id' => $unit->id,
            'after' => $unit->toArray(),
            'ip_address' => request()->ip(),
        ]);

        return redirect()
            ->route('equipment.index')
            ->with('success', __('ui.equipment_created'));
    }

    public function edit(EquipmentUnit $equipment): View
    {
        $this->authorize('update', $equipment);

        $branches = auth()->user()->hasRole('admin-cabang')
            ? auth()->user()->branches()->where('is_active', true)->get()
            : Branch::active()->orderBy('name')->get();

        return view('equipment.edit', [
            'unit' => $equipment,
            'products' => Product::whereHas('category', fn ($q) => $q->where('type_slug', 'sewa-alat'))
                ->orderBy('name')
                ->get(),
            'branches' => $branches,
            'maintenanceLogs' => $equipment->maintenanceLogs()->with('performer')->orderByDesc('date')->get(),
        ]);
    }

    public function update(Request $request, EquipmentUnit $equipment): RedirectResponse
    {
        $this->authorize('update', $equipment);

        $oldValues = $equipment->only(['code', 'condition', 'status', 'notes']);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:64', Rule::unique('equipment_units')
                ->where(fn ($q) => $q->where('branch_id', $request->input('branch_id')))
                ->ignore($equipment->id)],
            'condition' => ['required', 'in:good,fair,poor,damaged'],
            'status' => ['required', 'in:available,rented,maintenance'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $equipment->update($data);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'equipment_updated',
            'model_type' => EquipmentUnit::class,
            'model_id' => $equipment->id,
            'before' => $oldValues,
            'after' => $equipment->only(array_keys($data)),
            'ip_address' => request()->ip(),
        ]);

        return redirect()
            ->route('equipment.index')
            ->with('success', __('ui.equipment_updated'));
    }

    public function addMaintenance(Request $request, EquipmentUnit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:routine,repair,inspection,replacement'],
            'description' => ['nullable', 'string', 'max:500'],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        $data['performed_by'] = auth()->id();

        $log = $unit->maintenanceLogs()->create($data);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'maintenance_added',
            'model_type' => \App\Models\EquipmentMaintenanceLog::class,
            'model_id' => $log->id,
            'after' => $log->toArray(),
            'ip_address' => request()->ip(),
        ]);

        return redirect()
            ->route('equipment.edit', $unit)
            ->with('success', __('ui.maintenance_added'));
    }

    public function destroy(EquipmentUnit $equipment): RedirectResponse
    {
        $this->authorize('delete', $equipment);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'equipment_deleted',
            'model_type' => EquipmentUnit::class,
            'model_id' => $equipment->id,
            'before' => $equipment->toArray(),
            'ip_address' => request()->ip(),
        ]);

        $equipment->maintenanceLogs()->delete();
        $equipment->delete();

        return redirect()
            ->route('equipment.index')
            ->with('success', __('ui.equipment_deleted'));
    }
}
