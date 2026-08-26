<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\WhatsAppLog;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $this->authorize('notifications.view');

        $whatsapp = WhatsAppLog::query()
            ->with(['customer', 'transaction'])
            ->when(auth()->user()->hasRole('admin-cabang'), function ($q) {
                $branchIds = auth()->user()->branches()->pluck('branches.id');

                $q->whereHas('transaction', fn ($t) => $t->whereIn('branch_id', $branchIds));
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $emails = EmailLog::query()
            ->with(['customer', 'transaction'])
            ->when(auth()->user()->hasRole('admin-cabang'), function ($q) {
                $branchIds = auth()->user()->branches()->pluck('branches.id');

                $q->whereHas('transaction', fn ($t) => $t->whereIn('branch_id', $branchIds));
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('notification.index', [
            'whatsappLogs' => $whatsapp,
            'emailLogs' => $emails,
        ]);
    }
}
