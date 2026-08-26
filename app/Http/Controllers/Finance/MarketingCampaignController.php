<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MarketingCampaign::class);

        $query = MarketingCampaign::with('branch')->orderByDesc('start_date');

        if (auth()->user()->hasRole('admin-cabang')) {
            $branchIds = auth()->user()->branches()->pluck('branches.id');
            $query->forBranches($branchIds->all());
        }

        return view('finance.campaigns.index', [
            'campaigns' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MarketingCampaign::class);

        return view('finance.campaigns.create', [
            'branches' => $this->editableBranches(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MarketingCampaign::class);

        $campaign = MarketingCampaign::create($this->validated($request));

        AuditLogger::log('campaign_created', $campaign, null, $campaign->only(
            ['branch_id', 'name', 'channel', 'budget', 'start_date', 'end_date']
        ));

        return redirect()
            ->route('marketing-campaigns.index')
            ->with('success', __('ui.campaign_created'));
    }

    public function edit(MarketingCampaign $marketing_campaign): View
    {
        $this->authorize('update', $marketing_campaign);

        return view('finance.campaigns.edit', [
            'campaign' => $marketing_campaign,
            'branches' => $this->editableBranches(),
        ]);
    }

    public function update(Request $request, MarketingCampaign $marketing_campaign): RedirectResponse
    {
        $this->authorize('update', $marketing_campaign);

        $before = $marketing_campaign->only(['branch_id', 'name', 'channel', 'budget', 'start_date', 'end_date']);

        $marketing_campaign->update($this->validated($request));

        AuditLogger::log('campaign_updated', $marketing_campaign, $before, $marketing_campaign->only(array_keys($before)));

        return redirect()
            ->route('marketing-campaigns.index')
            ->with('success', __('ui.campaign_updated'));
    }

    public function destroy(MarketingCampaign $marketing_campaign): RedirectResponse
    {
        $this->authorize('delete', $marketing_campaign);

        abort_if($marketing_campaign->expenses()->exists(), 422, __('messages.campaign_has_expenses'));

        $before = $marketing_campaign->toArray();

        $marketing_campaign->delete();

        AuditLogger::log('campaign_deleted', $marketing_campaign, $before, null);

        return redirect()
            ->route('marketing-campaigns.index')
            ->with('success', __('ui.campaign_deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'max:64'],
            'budget' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    private function editableBranches()
    {
        if (auth()->user()->hasRole('admin-cabang')) {
            return auth()->user()->branches()->where('is_active', true)->orderBy('name')->get();
        }

        return Branch::where('is_active', true)->orderBy('name')->get();
    }
}
