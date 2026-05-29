<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Services\ActivityLogService;
use App\Services\ReturnWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportRequestController extends Controller
{
    public function index(ActivityLogService $logger): Response
    {
        return Inertia::render('Admin/Support/Index', [
            'requests' => SupportRequest::query()
                ->with([
                    'customer:id,name,email,phone',
                    'order:id,order_number',
                    'lastRespondedByUser:id,name',
                    'closedByUser:id,name',
                    'activityLogs' => fn ($q) => $q->with(['actorUser:id,name'])->limit(15),
                ])
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (SupportRequest $r) => $this->formatRequest($r, $logger)),
        ]);
    }

    public function update(Request $request, string $tenant, SupportRequest $supportRequest, ActivityLogService $logger): RedirectResponse
    {
        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:open,in_progress,closed'],
        ]);

        $previousStatus = $supportRequest->status;
        $previousNotes = $supportRequest->admin_notes;
        $updates = [];

        if (array_key_exists('admin_notes', $data) && $data['admin_notes'] !== $previousNotes) {
            $updates['admin_notes'] = $data['admin_notes'];
            $updates['last_responded_at'] = now();
            $updates['last_responded_by_user_id'] = auth()->id();
        }

        if (($data['status'] ?? null) === 'closed' && $supportRequest->status !== 'closed') {
            $updates['status'] = 'closed';
            $updates['closed_at'] = now();
            $updates['closed_by'] = auth()->id();
        } elseif (($data['status'] ?? null) === 'open') {
            $updates['status'] = 'open';
            $updates['closed_at'] = null;
            $updates['closed_by'] = null;
        } elseif (($data['status'] ?? null) === 'in_progress') {
            $updates['status'] = 'in_progress';
        }

        $supportRequest->update($updates);
        $supportRequest->refresh();

        if (array_key_exists('admin_notes', $data) && $data['admin_notes'] !== $previousNotes) {
            $logger->log(
                $supportRequest,
                'support.note_updated',
                'Resposta registrada no suporte',
                ['notes_length' => strlen((string) $data['admin_notes'])],
                'admin',
            );
        }

        $newStatus = $supportRequest->status;
        if ($newStatus !== $previousStatus) {
            if ($newStatus === 'closed') {
                $logger->log(
                    $supportRequest,
                    'support.closed',
                    'Solicitação encerrada',
                    ['from' => $previousStatus, 'to' => $newStatus],
                    'admin',
                );
            } elseif ($previousStatus === 'closed') {
                $logger->log(
                    $supportRequest,
                    'support.reopened',
                    'Solicitação reaberta',
                    ['from' => $previousStatus, 'to' => $newStatus],
                    'admin',
                );
            } else {
                $logger->log(
                    $supportRequest,
                    'support.status_changed',
                    sprintf('Status: %s → %s', $previousStatus, $newStatus),
                    ['from' => $previousStatus, 'to' => $newStatus],
                    'admin',
                );
            }
        }

        return back()->with('success', 'Solicitação atualizada.');
    }

    public function processReturn(
        Request $request,
        string $tenant,
        SupportRequest $supportRequest,
        ReturnWorkflowService $returns,
    ): RedirectResponse {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $returns->processReturn($supportRequest, $data['notes'] ?? null);

        return back()->with('success', 'Devolução processada. Pedido '.$order->order_number.' cancelado e estoque restaurado.');
    }

    protected function formatRequest(SupportRequest $r, ActivityLogService $logger): array
    {
        $name = $r->guest_name ?? $r->customer?->name;
        $phone = $r->guest_phone ?? $r->customer?->phone;
        $email = $r->guest_email ?? $r->customer?->email;

        return [
            'id' => $r->id,
            'type' => $r->type,
            'subject' => $r->subject,
            'message' => $r->message,
            'status' => $r->status,
            'admin_notes' => $r->admin_notes,
            'customer_id' => $r->customer_id,
            'contact' => [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'whatsapp_url' => $this->whatsappUrl($phone),
                'has_account' => (bool) $r->customer_id,
            ],
            'order' => $r->order ? [
                'id' => $r->order->id,
                'order_number' => $r->order->order_number,
            ] : null,
            'created_at' => $r->created_at?->format('d/m/Y H:i'),
            'closed_at' => $r->closed_at?->format('d/m/Y H:i'),
            'closed_by_name' => $r->closedByUser?->name,
            'last_responded_at' => $r->last_responded_at?->format('d/m/Y H:i'),
            'last_responded_by_name' => $r->lastRespondedByUser?->name,
            'activity_logs' => $r->activityLogs->map(fn ($log) => $logger->formatForUi($log)),
        ];
    }

    protected function whatsappUrl(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) < 10) {
            return null;
        }

        if (! str_starts_with($digits, '55')) {
            $digits = '55'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }
}
