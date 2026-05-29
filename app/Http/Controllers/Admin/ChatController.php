<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chat,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->get('status', 'open');
        $conversations = $this->chat->conversationsForAdmin($request->user(), $status === 'all' ? null : $status);

        return Inertia::render('Admin/Chat/Index', [
            'conversations' => $conversations->map(fn ($c) => $this->chat->conversationPayload($c, true)),
            'filters' => ['status' => $status],
            'branches' => BranchAccess::branchesForUser($request->user())->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
            ]),
        ]);
    }

    public function messages(Request $request, string $tenant, string $uuid): JsonResponse
    {
        $conversation = $this->chat->conversationByUuid($uuid);
        BranchAccess::assertCanAccessBranch($request->user(), (int) $conversation->branch_id);

        $this->chat->markReadByStaff($conversation);

        $afterId = $request->filled('after_id') ? $request->integer('after_id') : null;
        $messages = $this->chat->messagesAfter($conversation, $afterId);

        return response()->json([
            'messages' => $messages->map(fn ($m) => $this->chat->messagePayload($m->load(['senderUser', 'senderCustomer']))),
            'conversation' => $this->chat->conversationPayload($conversation->load(['branch', 'customer']), true),
        ]);
    }

    public function updates(Request $request): JsonResponse
    {
        $status = $request->get('status', 'open');
        $conversations = $this->chat->conversationsForAdmin($request->user(), $status === 'all' ? null : $status);

        return response()->json([
            'conversations' => $conversations->map(fn ($c) => $this->chat->conversationPayload($c, true)),
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        return response()->json([
            'total' => $this->chat->staffUnreadTotal($request->user()),
        ]);
    }

    public function send(Request $request, string $tenant, string $uuid): JsonResponse
    {
        $conversation = $this->chat->conversationByUuid($uuid);

        if ($conversation->status !== 'open') {
            return response()->json(['message' => 'Conversa encerrada.'], 422);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $this->chat->sendStaffMessage($conversation, $request->user(), $data['body']);

        return response()->json([
            'message' => $this->chat->messagePayload($message->load('senderUser')),
        ]);
    }

    public function close(Request $request, string $tenant, string $uuid): JsonResponse
    {
        $conversation = $this->chat->conversationByUuid($uuid);
        BranchAccess::assertCanAccessBranch($request->user(), (int) $conversation->branch_id);

        $conversation->update(['status' => 'closed']);

        return response()->json(['ok' => true]);
    }
}
