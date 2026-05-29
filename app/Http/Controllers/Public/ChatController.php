<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ChatConversation;
use App\Services\ChatService;
use App\Support\ChatEligibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chat,
    ) {}

    public function start(Request $request, string $tenant, string $branch): JsonResponse
    {
        $branchModel = Branch::query()
            ->where('slug', $branch)
            ->where('is_active', true)
            ->firstOrFail();

        $customer = $request->user('customer');
        ChatEligibility::assertCanStart($branchModel, $customer, $request);

        $data = $request->validate([
            'guest_name' => [$customer ? 'nullable' : 'required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $sessionKey = "chat_guest.{$branchModel->tenant_id}.{$branchModel->id}";
        $guestKeyFromClient = $customer
            ? null
            : ($request->header('X-Chat-Guest-Key') ?: $request->input('guest_key'));

        if ($uuid = $request->input('conversation_uuid')) {
            $conversation = $this->chat->conversationByUuid($uuid);

            if ($conversation->branch_id !== $branchModel->id) {
                abort(404);
            }

            $this->chat->assertCustomerCanAccess($conversation, $customer, $guestKeyFromClient);

            if (! $customer && $conversation->guest_key) {
                $request->session()->put($sessionKey, $conversation->guest_key);
            }

            return response()->json([
                'conversation' => $this->chat->conversationPayload($conversation),
                'guest_key' => $customer ? null : $conversation->guest_key,
            ]);
        }

        $guestKey = $customer
            ? null
            : ($request->session()->get($sessionKey) ?: $guestKeyFromClient);

        if (! $customer && ! $guestKey) {
            $guestKey = (string) \Illuminate\Support\Str::uuid();
        }

        if (! $customer) {
            $request->session()->put($sessionKey, $guestKey);
        }

        $conversation = $this->chat->findOrCreateConversation(
            $branchModel,
            $customer,
            $guestKey,
            $data['guest_name'] ?? null,
            $data['guest_phone'] ?? null,
        );

        return response()->json([
            'conversation' => $this->chat->conversationPayload($conversation),
            'guest_key' => $customer ? null : $conversation->guest_key,
        ]);
    }

    public function messages(Request $request, string $tenant, string $uuid): JsonResponse
    {
        $conversation = $this->chat->conversationByUuid($uuid);
        $customer = $request->user('customer');
        $guestKey = $request->header('X-Chat-Guest-Key') ?: $request->query('guest_key');

        $this->assertConversationAccess($conversation, $customer, $guestKey, $request);

        if ($request->boolean('mark_read')) {
            $this->chat->markReadByCustomer($conversation);
        }

        $afterId = $request->filled('after_id') ? $request->integer('after_id') : null;
        $messages = $this->chat->messagesAfter($conversation, $afterId);

        return response()->json([
            'messages' => $messages->map(fn ($m) => $this->chat->messagePayload($m)),
            'conversation' => $this->chat->conversationPayload($conversation),
        ]);
    }

    public function send(Request $request, string $tenant, string $uuid): JsonResponse
    {
        $conversation = $this->chat->conversationByUuid($uuid);
        $customer = $request->user('customer');
        $guestKey = $request->header('X-Chat-Guest-Key') ?: $request->input('guest_key');

        $this->assertConversationAccess($conversation, $customer, $guestKey, $request);

        if ($conversation->status !== 'open') {
            return response()->json(['message' => 'Conversa encerrada.'], 422);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $this->chat->sendCustomerMessage($conversation, $data['body'], $customer);

        return response()->json([
            'message' => $this->chat->messagePayload($message->load(['senderUser', 'senderCustomer'])),
        ]);
    }

    protected function assertConversationAccess(
        ChatConversation $conversation,
        $customer,
        ?string $guestKey,
        Request $request,
    ): void {
        $conversation->loadMissing('branch');
        ChatEligibility::assertCanStart($conversation->branch, $customer, $request);
        $this->chat->assertCustomerCanAccess($conversation, $customer, $guestKey);
    }
}
