<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\User;
use App\Support\BranchAccess;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatService
{
    public function findOrCreateConversation(
        Branch $branch,
        ?Customer $customer = null,
        ?string $guestKey = null,
        ?string $guestName = null,
        ?string $guestPhone = null,
    ): ChatConversation {
        $query = ChatConversation::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'open');

        if ($customer) {
            $conversation = (clone $query)->where('customer_id', $customer->id)->first();
        } else {
            $guestKey ??= (string) Str::uuid();
            $conversation = (clone $query)->where('guest_key', $guestKey)->first();
        }

        if ($conversation) {
            return $conversation;
        }

        return ChatConversation::create([
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'customer_id' => $customer?->id,
            'guest_key' => $customer ? null : $guestKey,
            'guest_name' => $customer?->name ?? $guestName,
            'guest_phone' => $customer?->phone ?? $guestPhone,
            'status' => 'open',
        ]);
    }

    public function conversationByUuid(string $uuid): ChatConversation
    {
        return ChatConversation::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function assertCustomerCanAccess(ChatConversation $conversation, ?Customer $customer, ?string $guestKey): void
    {
        if ($conversation->customer_id) {
            if (! $customer || $customer->id !== $conversation->customer_id) {
                abort(403);
            }

            return;
        }

        if (! $guestKey || $conversation->guest_key !== $guestKey) {
            abort(403);
        }
    }

    public function messagesAfter(ChatConversation $conversation, ?int $afterId = null, int $limit = 50): Collection
    {
        return $conversation->messages()
            ->when($afterId !== null, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function sendCustomerMessage(
        ChatConversation $conversation,
        string $body,
        ?Customer $customer = null,
    ): ChatMessage {
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => ChatMessage::SENDER_CUSTOMER,
            'sender_customer_id' => $customer?->id,
            'body' => trim($body),
            'read_at_customer' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'staff_unread_count' => $conversation->staff_unread_count + 1,
            'guest_name' => $customer?->name ?? $conversation->guest_name,
            'guest_phone' => $customer?->phone ?? $conversation->guest_phone,
        ]);

        return $message;
    }

    public function sendStaffMessage(ChatConversation $conversation, User $user, string $body): ChatMessage
    {
        BranchAccess::assertCanAccessBranch($user, $conversation->branch_id);

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => ChatMessage::SENDER_STAFF,
            'sender_user_id' => $user->id,
            'body' => trim($body),
            'read_at_staff' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'customer_unread_count' => $conversation->customer_unread_count + 1,
            'staff_unread_count' => 0,
        ]);

        return $message;
    }

    public function markReadByStaff(ChatConversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_type', ChatMessage::SENDER_CUSTOMER)
            ->whereNull('read_at_staff')
            ->update(['read_at_staff' => now()]);

        $conversation->update(['staff_unread_count' => 0]);
    }

    public function markReadByCustomer(ChatConversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_type', ChatMessage::SENDER_STAFF)
            ->whereNull('read_at_customer')
            ->update(['read_at_customer' => now()]);

        $conversation->update(['customer_unread_count' => 0]);
    }

    public function staffUnreadTotal(?User $user): int
    {
        $tenant = TenantContext::get();

        return (int) ChatConversation::query()
            ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
            ->where('status', 'open')
            ->when(true, fn ($q) => BranchAccess::scopeBranchColumn($q, 'branch_id', $user))
            ->sum('staff_unread_count');
    }

    public function conversationsForAdmin(?User $user, ?string $status = 'open'): Collection
    {
        $tenant = TenantContext::get();

        return ChatConversation::query()
            ->with(['branch:id,name', 'customer:id,name,phone'])
            ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when(true, fn ($q) => BranchAccess::scopeBranchColumn($q, 'branch_id', $user))
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();
    }

    public function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'sender_name' => $message->sender_type === ChatMessage::SENDER_STAFF
                ? ($message->senderUser?->name ?? 'Equipe')
                : ($message->senderCustomer?->name ?? 'Cliente'),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    public function conversationPayload(ChatConversation $conversation, bool $forAdmin = false): array
    {
        $conversation->loadMissing(['branch', 'customer']);

        return [
            'uuid' => $conversation->uuid,
            'status' => $conversation->status,
            'display_name' => $conversation->displayName(),
            'guest_phone' => $conversation->guest_phone ?? $conversation->customer?->phone,
            'branch' => $conversation->branch?->only(['id', 'name', 'slug']),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'staff_unread_count' => $forAdmin ? $conversation->staff_unread_count : null,
            'customer_unread_count' => $forAdmin ? null : $conversation->customer_unread_count,
        ];
    }
}
