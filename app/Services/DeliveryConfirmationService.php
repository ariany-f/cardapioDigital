<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Validation\ValidationException;

class DeliveryConfirmationService
{
    public function ensureCode(Order $order): ?string
    {
        if ($order->type !== 'delivery') {
            return null;
        }

        if ($order->delivery_confirmation_code) {
            return $order->delivery_confirmation_code;
        }

        $code = $this->generateUnique($order->tenant_id);
        $order->update(['delivery_confirmation_code' => $code]);

        return $code;
    }

    /**
     * Código exibido ao cliente no acompanhamento (gera se ainda não existir).
     */
    public function codeForCustomerDisplay(Order $order): ?string
    {
        if ($order->type !== 'delivery' || $order->status !== 'out_for_delivery') {
            return null;
        }

        return $this->ensureCode($order->fresh());
    }

    public function confirm(Order $order, string $code): void
    {
        if ($order->type !== 'delivery') {
            throw ValidationException::withMessages([
                'confirmation_code' => ['Este pedido não é de entrega.'],
            ]);
        }

        if ($order->status === 'delivered') {
            throw ValidationException::withMessages([
                'confirmation_code' => ['Este pedido já foi marcado como entregue.'],
            ]);
        }

        if (! $order->delivery_confirmation_code) {
            throw ValidationException::withMessages([
                'confirmation_code' => ['Código de entrega ainda não foi gerado. Atualize o status para "saiu para entrega".'],
            ]);
        }

        $normalized = $this->normalize($code);

        if (! hash_equals($order->delivery_confirmation_code, $normalized)) {
            throw ValidationException::withMessages([
                'confirmation_code' => ['Código incorreto. Confira com o cliente.'],
            ]);
        }

        $order->update(['delivery_confirmed_at' => now()]);
    }

    public function normalize(string $code): string
    {
        return preg_replace('/\D/', '', $code) ?? '';
    }

    protected function generateUnique(int $tenantId): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $exists = Order::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('delivery_confirmation_code', $code)
                ->whereNotIn('status', ['delivered', 'cancelled', 'rejected'])
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
