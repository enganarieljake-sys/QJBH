<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\ChecklistReport;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Support\ChecklistSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ChecklistController extends Controller
{
    public function index(Request $request)
    {
        $this->syncMissedReports();
        $period = ChecklistSchedule::currentPeriod();
        $items = ChecklistItem::forPeriod($period)->get();
        $report = ChecklistReport::where('user_id', $request->user()->id)
            ->whereDate('report_date', today())->where('period', $period)->first();

        return view('admin.checklists.index', [
            'period' => $period,
            'items' => $items,
            'report' => $report,
            'timingMessage' => ChecklistSchedule::timingLabel($period, now()),
            'missedReports' => $request->user()->role === 'caretaker'
                ? ChecklistReport::where('user_id', $request->user()->id)->where('status', ChecklistReport::STATUS_MISSED)->latest('report_date')->get()
                : collect(),
            'allItems' => $request->user()->isAdmin() ? ChecklistItem::orderBy('position')->orderBy('id')->get() : collect(),
            'reports' => $request->user()->isAdmin()
                ? ChecklistReport::with(['user', 'overrider'])->orderByDesc('report_date')->orderByDesc('id')->paginate(20)
                : null,
            'periods' => ChecklistItem::PERIODS,
        ]);
    }

    public function storeItem(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'period' => ['required', Rule::in(ChecklistItem::PERIODS)],
        ]);
        $data['position'] = (int) ChecklistItem::max('position') + 1;
        ChecklistItem::create($data);

        return back()->with('success', 'Checklist task added.');
    }

    public function updateItem(Request $request, ChecklistItem $checklistItem)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $checklistItem->update($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'period' => ['required', Rule::in(ChecklistItem::PERIODS)],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Checklist task updated.');
    }

    public function destroyItem(Request $request, ChecklistItem $checklistItem)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $checklistItem->delete();

        return back()->with('success', 'Checklist task removed.');
    }

    public function submit(Request $request)
    {
        abort_unless($request->user()->role === 'caretaker', 403);
        $period = ChecklistSchedule::currentPeriod();
        $items = ChecklistItem::forPeriod($period)->get();
        abort_if($items->isEmpty(), 422, 'Ate Jackie has not added tasks for this checklist yet.');
        $data = $request->validate([
            'completed' => ['required', 'array'],
            'completed.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $completed = collect($data['completed'])->map(fn ($id) => (int) $id)->unique();
        abort_unless($items->pluck('id')->diff($completed)->isEmpty(), 422, 'Complete every checklist item before sending the report.');

        $report = ChecklistReport::updateOrCreate([
            'user_id' => $request->user()->id,
            'report_date' => today(),
            'period' => $period,
        ], [
            'status' => ChecklistReport::STATUS_COMPLETED,
            'completed_items' => $items->pluck('title')->values()->all(),
            'notes' => $data['notes'] ?? null,
            'submitted_at' => now(),
            'missed_reason' => null,
            'admin_note' => null,
            'overridden_at' => null,
            'overridden_by' => null,
        ]);

        Notification::send(User::staff(['admin']), new ActivityNotification([
            'title' => ucfirst($period).' caretaker report completed',
            'message' => $request->user()->name.' completed '.count($report->completed_items).' checklist tasks ('.$report->timingLabel().').',
            'url' => route('admin.checklists.index'),
            'kind' => 'checklist-'.$report->id,
        ]));

        return back()->with('success', ucfirst($period).' checklist report sent to Ate Jackie.');
    }

    public function missedReason(Request $request, ChecklistReport $checklistReport)
    {
        abort_unless($request->user()->role === 'caretaker' && $checklistReport->user_id === $request->user()->id, 403);
        abort_unless($checklistReport->status === ChecklistReport::STATUS_MISSED, 422, 'This report is no longer marked as missed.');
        $data = $request->validate(['missed_reason' => ['required', 'string', 'max:2000']]);
        $checklistReport->update($data);

        Notification::send(User::staff(['admin']), new ActivityNotification([
            'title' => 'Reason filed for missed checklist',
            'message' => $request->user()->name.' filed a reason for the missed '.strtolower($checklistReport->period).' checklist.',
            'url' => route('admin.checklists.index'),
            'kind' => 'checklist-reason-'.$checklistReport->id,
        ]));

        return back()->with('success', 'Your reason was sent to Ate Jackie.');
    }

    public function override(Request $request, ChecklistReport $checklistReport)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($checklistReport->status === ChecklistReport::STATUS_MISSED, 422, 'Only a missed checklist can be overridden.');
        $data = $request->validate(['admin_note' => ['required', 'string', 'max:2000']]);
        $checklistReport->update($data + [
            'status' => ChecklistReport::STATUS_MISSED_OVERRIDE,
            'overridden_at' => now(),
            'overridden_by' => $request->user()->id,
        ]);

        $checklistReport->user->notify(new ActivityNotification([
            'title' => 'Missed checklist reviewed',
            'message' => 'Ate Jackie reviewed your missed checklist and marked it Missed Override.',
            'url' => route('admin.checklists.index'),
            'kind' => 'checklist-override-'.$checklistReport->id,
        ]));

        return back()->with('success', 'The missed report is now marked Missed Override.');
    }

    private function syncMissedReports(): void
    {
        $firstTask = ChecklistItem::where('is_active', true)->oldest('created_at')->first();
        if (! $firstTask) {
            return;
        }

        $now = now();
        foreach (User::where('role', 'caretaker')->get() as $caretaker) {
            $date = Carbon::createFromTimestamp(
                max($firstTask->created_at->timestamp, $caretaker->created_at->timestamp),
                config('app.timezone')
            )->startOfDay();
            $lastDate = $now->copy()->startOfDay();
            while ($date->lte($lastDate)) {
                foreach (['morning', 'evening'] as $period) {
                    $periodEnd = $date->copy()->addHours($period === 'morning' ? 12 : 24);
                    if ($periodEnd->gt($now)) {
                        continue;
                    }

                    $tasks = ChecklistItem::forPeriod($period)->where('created_at', '<=', $periodEnd)->pluck('title')->values()->all();
                    if ($tasks === []) {
                        continue;
                    }

                    ChecklistReport::firstOrCreate([
                        'user_id' => $caretaker->id,
                        'report_date' => $date->toDateString(),
                        'period' => $period,
                    ], [
                        'status' => ChecklistReport::STATUS_MISSED,
                        'completed_items' => [],
                        'submitted_at' => null,
                    ]);
                }
                $date = $date->copy()->addDay();
            }
        }
    }
}
