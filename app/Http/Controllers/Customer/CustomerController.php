<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Customer\Services\CustomerService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::withCount('certifications')->orderBy('name');

        if ($search = $request->input('q')) {
            $query->search($search);
        }

        if ($segment = $request->input('segment')) {
            $query->whereRaw("CASE WHEN total_orders >= 5 THEN 'VIP' WHEN total_orders > 1 THEN 'Repeat' ELSE 'Baru' END = ?", [$segment]);
        }

        if ($nationality = $request->input('nationality')) {
            $query->nationality($nationality);
        }

        return view('customers.index', [
            'customers' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'nationality_type' => ['required', 'in:indonesia,international'],
            'source' => ['required', 'in:organic,ads,referral,walk_in,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'preferences.allergies' => ['nullable', 'string', 'max:500'],
            'preferences.equipment_size' => ['nullable', 'string', 'max:32'],
            'preferences.experience_level' => ['nullable', 'string', 'max:32'],
        ], [
            'name.required' => __('auth.name_required'),
            'source.required' => __('auth.source_required'),
        ]);

        $data['branch_id'] = $request->input('branch_id')
            ?? auth()->user()->branches()->first()?->id
            ?? Branch::first()?->id
            ?? 1;

        $customer = $this->service->createCustomer($data);

        return redirect()
            ->route('customers.edit', $customer)
            ->with('success', __('ui.customer_created'));
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        return view('customers.show', [
            'customer' => $customer->load(['certifications', 'branch']),
        ]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', [
            'customer' => $customer->load('certifications'),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'nationality_type' => ['required', 'in:indonesia,international'],
            'source' => ['required', 'in:organic,ads,referral,walk_in,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'preferences.allergies' => ['nullable', 'string', 'max:500'],
            'preferences.equipment_size' => ['nullable', 'string', 'max:32'],
            'preferences.experience_level' => ['nullable', 'string', 'max:32'],
        ]);

        $certs = collect($request->input('certs', []))
            ->filter(fn (array $cert) => ! empty($cert['agency']))
            ->values()
            ->all();

        $this->service->updateCustomer($customer, $data, $certs);

        return redirect()
            ->route('customers.edit', $customer)
            ->with('success', __('ui.customer_updated'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $this->service->deleteCustomer($customer);

        return redirect()
            ->route('customers.index')
            ->with('success', __('ui.customer_deleted'));
    }
}
