<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantAction;
use App\Models\AssistantMessage;
use App\Support\Assistant\Actions;
use App\Support\Assistant\Assistant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssistantController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.assistant.index', [
            'messages' => AssistantMessage::with('actions.user')->where('user_id', $request->user()->id)->where('content', '!=', '')->latest('id')->limit(40)->get()->reverse(),
            'briefing' => Assistant::briefing($request->user()),
            'quick' => Assistant::QUICK,
            'tenantTemplate' => Assistant::TENANT_TEMPLATE,
            'remaining' => Assistant::remaining(),
            'configured' => Assistant::configured(),
        ]);
    }

    public function ask(Request $request)
    {
        $data = $request->validate(['question' => ['required', 'string', 'max:2000']]);

        return $this->reply($request, Assistant::ask($request->user(), trim($data['question'])));
    }

    public function quick(Request $request)
    {
        $data = $request->validate(['key' => ['required', Rule::in(array_keys(Assistant::QUICK))]]);

        return $this->reply($request, Assistant::quick($request->user(), $data['key']));
    }

    public function confirm(Request $request, AssistantAction $action)
    {
        return $this->card($request, Actions::confirm($this->own($request, $action), $request->user()));
    }

    public function cancel(Request $request, AssistantAction $action)
    {
        $action = $this->own($request, $action);

        if ($action->isPending()) {
            $action->forceFill(['status' => 'cancelled', 'result_message' => 'Cancelled.'])->save();
        }

        return $this->card($request, $action);
    }

    public function undo(Request $request, AssistantAction $action)
    {
        return $this->card($request, Actions::undo($this->own($request, $action)));
    }

    /**
     * Everything the bot proposed, who confirmed it, and what happened.
     */
    public function activity()
    {
        return view('admin.assistant.activity', [
            'actions' => AssistantAction::with('user')->latest()->paginate(20),
        ]);
    }

    /**
     * Admins can only act on cards from their own chat.
     */
    private function own(Request $request, AssistantAction $action): AssistantAction
    {
        abort_unless($action->user_id === $request->user()->id, 403);

        return $action;
    }

    private function card(Request $request, AssistantAction $action)
    {
        if ($request->expectsJson()) {
            $card = $action->card();

            return response()->json(['action' => $card, 'html' => view('admin.assistant._card', ['card' => $card])->render()]);
        }

        return redirect()->route('admin.assistant.index')
            ->with($action->status === 'failed' ? 'error' : 'success', $action->title.': '.$action->result_message);
    }

    /**
     * Rows that used AI requests are blanked instead of deleted so they still
     * count toward today's free limit.
     */
    public function clear(Request $request)
    {
        AssistantMessage::where('user_id', $request->user()->id)->update(['content' => '']);
        AssistantMessage::where('user_id', $request->user()->id)->where('ai_requests', 0)->delete();

        return redirect()->route('admin.assistant.index')->with('success', 'Chat cleared.');
    }

    private function reply(Request $request, array $result)
    {
        if ($request->expectsJson()) {
            $result['actions_html'] = collect($result['actions'] ?? [])
                ->map(fn (array $card) => view('admin.assistant._card', ['card' => $card])->render())
                ->implode('');

            return response()->json($result);
        }

        return redirect()->route('admin.assistant.index');
    }
}
