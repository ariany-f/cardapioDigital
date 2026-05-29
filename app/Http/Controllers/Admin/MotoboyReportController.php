<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use App\Models\MotoboyReport;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MotoboyReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MotoboyReports/Index', [
            'reports' => MotoboyReport::query()
                ->with(['motoboy:id,name,phone', 'customer:id,name,email,phone', 'order:id,order_number'])
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (MotoboyReport $r) => [
                    'id' => $r->id,
                    'status' => $r->status,
                    'message' => $r->message,
                    'admin_response' => $r->admin_response,
                    'created_at' => $r->created_at?->toIso8601String(),
                    'motoboy' => $r->motoboy?->only(['id', 'name', 'phone']),
                    'customer' => $r->customer?->only(['name', 'email', 'phone']),
                    'order' => $r->order?->only(['order_number']),
                ]),
        ]);
    }

    public function update(Request $request, string $tenant, MotoboyReport $report, ActivityLogService $logger): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,reviewed,dismissed'],
            'admin_response' => ['nullable', 'string', 'max:2000'],
            'deactivate_motoboy' => ['sometimes', 'boolean'],
        ]);

        $report->update([
            'status' => $data['status'],
            'admin_response' => $data['admin_response'] ?? null,
            'handled_by_user_id' => auth()->id(),
            'handled_at' => now(),
        ]);

        if ($request->boolean('deactivate_motoboy')) {
            $motoboy = Motoboy::query()->findOrFail($report->motoboy_id);
            $motoboy->update(['is_active' => false]);
            $logger->log(
                $motoboy,
                'motoboy.deactivated',
                'Motoboy desativado após denúncia',
                ['report_id' => $report->id],
                'admin',
            );
        }

        if ($data['admin_response'] && $report->customer) {
            $logger->log(
                $report->order ?? $report,
                'motoboy.report_handled',
                'Resposta ao cliente sobre denúncia do entregador',
                ['admin_response' => $data['admin_response']],
                'admin',
            );
        }

        return back()->with('success', 'Denúncia atualizada.');
    }
}
