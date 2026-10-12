<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\ChecklistReport;
use App\Models\User;
use App\Support\ChecklistSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyChecklistWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_morning_and_evening_checklists_remain_open_for_the_full_half_day(): void
    {
        $caretaker = User::factory()->create(['role' => 'caretaker']);
        $morning = ChecklistItem::create(['title' => 'Inspect faucets', 'period' => 'morning', 'position' => 1]);
        $evening = ChecklistItem::create(['title' => 'Clean hallway', 'period' => 'evening', 'position' => 2]);

        Carbon::setTestNow('2026-10-12 10:20:00');
        $this->actingAs($caretaker)->post(route('admin.checklists.submit'), ['completed' => [$morning->id]])
            ->assertRedirect();
        $this->assertTrue(ChecklistReport::where('user_id', $caretaker->id)->whereDate('report_date', '2026-10-12')
            ->where('period', 'morning')->where('status', 'completed')->exists());

        Carbon::setTestNow('2026-10-12 15:20:00');
        $this->actingAs($caretaker)->post(route('admin.checklists.submit'), ['completed' => [$evening->id]])
            ->assertRedirect();
        $this->assertTrue(ChecklistReport::where('user_id', $caretaker->id)->whereDate('report_date', '2026-10-12')
            ->where('period', 'evening')->where('status', 'completed')->exists());
    }

    public function test_closed_period_becomes_missed_then_requires_reason_and_admin_note_to_override(): void
    {
        Carbon::setTestNow('2026-10-11 13:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $caretaker = User::factory()->create(['role' => 'caretaker']);
        ChecklistItem::create(['title' => 'Inspect CR', 'period' => 'both', 'position' => 1]);
        Carbon::setTestNow('2026-10-12 13:00:00');

        $this->actingAs($caretaker)->get(route('admin.checklists.index'))->assertOk();
        $missed = ChecklistReport::where('user_id', $caretaker->id)
            ->where('period', 'morning')->where('status', ChecklistReport::STATUS_MISSED)->latest('report_date')->firstOrFail();
        $this->assertSame(ChecklistReport::STATUS_MISSED, $missed->status);

        $this->actingAs($caretaker)->post(route('admin.checklists.missed-reason', $missed), [])
            ->assertSessionHasErrors('missed_reason');
        $this->actingAs($caretaker)->post(route('admin.checklists.missed-reason', $missed), [
            'missed_reason' => 'Emergency water interruption.',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.checklists.override', $missed), [])
            ->assertSessionHasErrors('admin_note');
        $this->actingAs($admin)->post(route('admin.checklists.override', $missed), [
            'admin_note' => 'Reason reviewed and acknowledged.',
        ])->assertRedirect();

        $this->assertDatabaseHas('checklist_reports', [
            'id' => $missed->id,
            'status' => ChecklistReport::STATUS_MISSED_OVERRIDE,
            'missed_reason' => 'Emergency water interruption.',
            'admin_note' => 'Reason reviewed and acknowledged.',
            'overridden_by' => $admin->id,
        ]);
    }

    public function test_timing_labels_report_early_and_late_minutes(): void
    {
        $this->assertSame('You are 20 min early', ChecklistSchedule::timingLabel('morning', Carbon::parse('2026-10-12 06:40')));
        $this->assertSame('You are 20 min late', ChecklistSchedule::timingLabel('evening', Carbon::parse('2026-10-12 19:20')));
    }
}
