<?php

namespace App\Http\Controllers\Schedule;

use App\Domain\Schedule\Services\ScheduleService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Schedule;
use App\Models\ScheduleParticipant;
use App\Models\ScheduleStaff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private readonly ScheduleService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Schedule::class);

        $query = Schedule::with(['product', 'branch', 'staff.user'])
            ->withCount('participants')
            ->orderBy('date_start');

        if ($user = auth()->user()) {
            if ($user->hasRole('dive-guide')) {
                $query->whereHas('staff', fn ($q) => $q->where('user_id', $user->id));
            } elseif ($user->hasRole('admin-cabang')) {
                $query->whereIn('branch_id', $user->branches()->pluck('branches.id'));
            }
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return view('schedules.index', [
            'schedules' => $query->paginate(20)->withQueryString(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'statuses' => Schedule::STATUSES,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Schedule::class);

        return view('schedules.create', [
            'products' => $this->tripProducts(),
            'branches' => $this->availableBranches(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Schedule::class);

        $data = $this->validateSchedule($request);

        $schedule = $this->service->createSchedule($data);

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', __('ui.schedule_created'));
    }

    public function show(Schedule $schedule): View
    {
        $this->authorize('view', $schedule);

        $schedule->load(['product.category', 'branch', 'participants.customer', 'staff.user']);

        return view('schedules.show', [
            'schedule' => $schedule,
            'customers' => \App\Models\Customer::orderBy('name')->get(),
            'guides' => User::role('dive-guide')->orderBy('name')->get(),
            'statuses' => Schedule::STATUSES,
            'staffRoles' => ScheduleStaff::ROLES,
            'transitions' => $this->transitionsFor($schedule),
        ]);
    }

    public function edit(Schedule $schedule): View
    {
        $this->authorize('update', $schedule);

        return view('schedules.edit', [
            'schedule' => $schedule,
            'products' => $this->tripProducts(),
            'branches' => $this->availableBranches(),
        ]);
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule);

        try {
            $this->service->updateSchedule($schedule, $this->validateSchedule($request, $schedule));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('schedules.index')
            ->with('success', __('ui.schedule_updated'));
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->authorize('delete', $schedule);

        try {
            $this->service->deleteSchedule($schedule);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('schedules.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('schedules.index')
            ->with('success', __('ui.schedule_deleted'));
    }

    public function changeStatus(Request $request, Schedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule);

        $data = $request->validate([
            'status' => ['required', Rule::in(Schedule::STATUSES)],
        ]);

        try {
            $this->service->changeStatus($schedule, $data['status']);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('schedules.show', $schedule)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', __('ui.schedule_updated'));
    }

    public function addParticipant(Request $request, Schedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
        ]);

        try {
            $this->service->addParticipant($schedule, (int) $data['customer_id']);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('schedules.show', $schedule)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', __('ui.participant_added'));
    }

    public function removeParticipant(Schedule $schedule, ScheduleParticipant $participant): RedirectResponse
    {
        $this->authorize('update', $schedule);

        abort_unless($participant->schedule_id === $schedule->id, 404);

        $this->service->removeParticipant($participant);

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', __('ui.participant_removed'));
    }

    public function addStaff(Request $request, Schedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_in_trip' => ['required', Rule::in(ScheduleStaff::ROLES)],
        ]);

        try {
            /** @var User $user */
            $user = User::findOrFail((int) $data['user_id']);
            $this->service->addStaff($schedule, $user, $data['role_in_trip']);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('schedules.show', $schedule)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', __('ui.staff_added'));
    }

    public function removeStaff(Schedule $schedule, ScheduleStaff $staffMember): RedirectResponse
    {
        $this->authorize('update', $schedule);

        abort_unless($staffMember->schedule_id === $schedule->id, 404);

        $this->service->removeStaff($staffMember);

        return redirect()
            ->route('schedules.show', $schedule)
            ->with('success', __('ui.staff_removed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSchedule(Request $request, ?Schedule $schedule = null): array
    {
        return $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'product_id' => ['required', 'exists:products,id'],
            'date_start' => ['required', 'date', 'after_or_equal:' . now()->subDay()->toDateString()],
            'date_end' => ['nullable', 'date', 'after:date_start'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Produk yang boleh dijadwalkan: hanya trip/kelas (bukan makanan, merchandise, dll).
     */
    private function tripProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::active()
            ->whereHas('category', fn ($q) => $q->whereIn('type_slug', config('schedules.trip_category_slugs')))
            ->orderBy('name')
            ->get();
    }

    private function availableBranches(): \Illuminate\Database\Eloquent\Collection
    {        /** @var User|null $user */
        $user = auth()->user();

        if ($user && $user->hasRole('admin-cabang')) {
            return $user->branches()->where('is_active', true)->orderBy('name')->get();
        }

        return Branch::active()->orderBy('name')->get();
    }

    /**
     * Transisi status yang sah untuk tombol aksi cepat di halaman show.
     *
     * @return list<array{to: string, label: string}>
     */
    private function transitionsFor(Schedule $schedule): array
    {
        return match ($schedule->status) {
            'draft' => [['to' => 'confirmed', 'label' => __('ui.action_confirm')]],
            'confirmed' => [
                ['to' => 'ongoing', 'label' => __('ui.action_start')],
                ['to' => 'cancelled', 'label' => __('ui.action_cancel_trip')],
            ],
            'ongoing' => [['to' => 'completed', 'label' => __('ui.action_complete')]],
            default => [],
        };
    }
}
