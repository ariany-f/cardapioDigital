<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request, ActivityLogService $logger): Response
    {
        $logs = ActivityLog::query()
            ->with(['actorUser:id,name', 'actorCustomer:id,name'])
            ->when($request->action, fn ($q, $action) => $q->where('action', $action))
            ->when($request->subject, function ($q, $subject) {
                $map = [
                    'order' => \App\Models\Order::class,
                    'support' => \App\Models\SupportRequest::class,
                    'branch' => \App\Models\Branch::class,
                    'product' => \App\Models\Product::class,
                    'coupon' => \App\Models\Coupon::class,
                    'user' => \App\Models\User::class,
                ];
                if (isset($map[$subject])) {
                    $q->where('subject_type', $map[$subject]);
                }
            })
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->q, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('description', 'like', "%{$term}%")
                        ->orWhere('action', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => [
                ...$logger->formatForUi($log),
                'subject_type' => class_basename($log->subject_type),
                'subject_id' => $log->subject_id,
            ]);

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only('action', 'subject', 'date_from', 'date_to', 'q'),
            'subjectOptions' => [
                ['value' => '', 'label' => 'Todos'],
                ['value' => 'order', 'label' => 'Pedidos'],
                ['value' => 'support', 'label' => 'Suporte'],
                ['value' => 'branch', 'label' => 'Filiais'],
                ['value' => 'product', 'label' => 'Produtos'],
                ['value' => 'coupon', 'label' => 'Cupons'],
                ['value' => 'user', 'label' => 'Usuários'],
            ],
            'actionOptions' => collect(ActivityLogService::ACTION_LABELS)
                ->map(fn ($label, $key) => ['value' => $key, 'label' => $label])
                ->values(),
        ]);
    }
}
