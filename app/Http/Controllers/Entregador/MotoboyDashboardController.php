<?php

namespace App\Http\Controllers\Entregador;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Motoboy;
use App\Services\DeliveryStatusService;
use App\Support\MotoboyBranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MotoboyDashboardController extends Controller
{
    public function index(): Response
    {
        $motoboy = Auth::guard('motoboy')->user();

        return Inertia::render('Entregador/Dashboard', [
            'pendingAssignments' => $this->deliveriesQuery($motoboy)
                ->inProgressForMotoboy()
                ->where('motoboy_assignment_status', 'pending')
                ->get()
                ->map(fn ($d) => $this->deliveryPayload($d)),
            'activeDeliveries' => $this->deliveriesQuery($motoboy)
                ->inProgressForMotoboy()
                ->where('motoboy_assignment_status', 'accepted')
                ->get()
                ->map(fn ($d) => $this->deliveryPayload($d)),
        ]);
    }

    public function poll(): JsonResponse
    {
        $motoboy = Auth::guard('motoboy')->user();

        $pending = $this->deliveriesQuery($motoboy)
            ->inProgressForMotoboy()
            ->where('motoboy_assignment_status', 'pending')
            ->count();

        return response()->json(['pending_count' => $pending]);
    }

    public function respond(Request $request, string $tenant, Delivery $delivery, DeliveryStatusService $service): RedirectResponse
    {
        $motoboy = Auth::guard('motoboy')->user();

        $data = $request->validate([
            'accept' => ['required', 'boolean'],
            'reject_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $service->motoboyRespond($delivery, $motoboy, $data['accept'], $data['reject_reason'] ?? null);

        return back()->with('success', $data['accept'] ? 'Entrega aceita.' : 'Entrega recusada.');
    }

    public function updateStatus(Request $request, string $tenant, Delivery $delivery, DeliveryStatusService $service): RedirectResponse
    {
        $motoboy = Auth::guard('motoboy')->user();

        if ($delivery->motoboy_id !== $motoboy->id || $delivery->motoboy_assignment_status !== 'accepted') {
            abort(403);
        }

        $delivery->loadMissing('order');
        MotoboyBranchAccess::assertCanServeBranch($motoboy, (int) $delivery->order->branch_id);

        $data = $request->validate([
            'delivery_status' => ['required', 'string', 'in:picked_up,on_route,delivered'],
            'confirmation_code' => ['nullable', 'string', 'min:4', 'max:8'],
        ]);

        $order = $delivery->order;

        $service->updateStatus(
            $order,
            $data['delivery_status'],
            $motoboy->id,
            $data['confirmation_code'] ?? null,
            null,
            'motoboy',
            $motoboy->id,
        );

        return back()->with('success', 'Status atualizado.');
    }

    protected function deliveriesQuery(Motoboy $motoboy)
    {
        $query = Delivery::query()
            ->where('motoboy_id', $motoboy->id)
            ->with(['order.branch:id,name', 'order:id,order_number,status,type,guest_name,guest_phone,delivery_address,total,delivery_confirmation_code,branch_id']);

        $allowed = MotoboyBranchAccess::allowedBranchIds($motoboy);

        if ($allowed !== null) {
            $query->whereHas('order', fn ($q) => $q->whereIn('branch_id', $allowed));
        }

        return $query;
    }

    protected function deliveryPayload(Delivery $delivery): array
    {
        $order = $delivery->order;

        return [
            'id' => $delivery->id,
            'status' => $delivery->status,
            'assignment_status' => $delivery->motoboy_assignment_status,
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'guest_name' => $order->guest_name,
                'guest_phone' => $order->guest_phone,
                'delivery_address' => $order->delivery_address,
                'total' => $order->total,
                'needs_code' => $delivery->status === 'on_route' || $delivery->status === 'picked_up',
                'has_code' => (bool) $order->delivery_confirmation_code,
            ],
            'branch' => $order->branch?->only(['name']),
        ];
    }
}
