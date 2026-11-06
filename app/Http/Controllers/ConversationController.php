<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesCurrentEstablishment;
use App\Models\Conversation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    use ResolvesCurrentEstablishment;

    public function index(Request $request): View
    {
        $establishment = $this->currentEstablishment($request, 'settings');
        $status = (string) $request->query('status');

        $conversations = $establishment->conversations()
            ->withCount('messages')
            ->when(array_key_exists($status, Conversation::STATUS_LABELS), fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('conversations.index', [
            'title' => 'Conversaciones',
            'establishment' => $establishment,
            'establishments' => $this->establishmentOptions($request, 'settings'),
            'conversations' => $conversations,
            'status' => $status,
        ]);
    }

    public function show(Request $request, string $id): View
    {
        $conversation = $this->ownedConversations($request)
            ->with(['establishment', 'messages' => fn ($query) => $query->orderBy('id')])
            ->findOrFail($id);

        return view('conversations.show', [
            'title' => 'Conversación #'.$conversation->id,
            'conversation' => $conversation,
        ]);
    }

    /**
     * @return Builder<Conversation>
     */
    private function ownedConversations(Request $request): Builder
    {
        return Conversation::query()
            ->whereIn('establishment_id', $request->user()->accessibleEstablishments('settings')->select('id'));
    }
}
