<?php

namespace App\Support\Assistant;

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\BillController;
use App\Http\Controllers\Admin\ChecklistController;
use App\Http\Controllers\Admin\ConcernController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\MaintenanceRequestController;
use App\Http\Controllers\Admin\MoveOutController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentSubmissionController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Admin\VisitorController;
use App\Models\AssistantAction;
use App\Models\Bill;
use App\Models\ChecklistItem;
use App\Models\Expense;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Changes QJ Bot can propose. Each one only prepares a plan (which existing
 * controller action to run, with what input) and a summary card; nothing
 * changes until the admin presses Confirm. Deleting, resetting, passwords,
 * roles, and creating admins are deliberately not here.
 */
class Actions
{
    public const PREFIX = 'propose_';

    /** Records an Undo may delete, and only when the bot just created them. */
    private const UNDOABLE = [Payment::class, Expense::class, \App\Models\Announcement::class, Bill::class, ChecklistItem::class];

    /**
     * Tool definitions for the AI, one per action.
     */
    public static function definitions(): array
    {
        return collect(self::catalog())->map(fn (array $spec, string $name) => [
            'type' => 'function',
            'function' => [
                'name' => self::PREFIX.$name,
                'description' => $spec[0].' Only shows a confirmation card; the admin must press Confirm.',
                'parameters' => ['type' => 'object', 'properties' => (object) $spec[1], 'required' => $spec[2]],
            ],
        ])->values()->all();
    }

    public static function isAction(string $toolName): bool
    {
        return str_starts_with($toolName, self::PREFIX) && isset(self::catalog()[Str::after($toolName, self::PREFIX)]);
    }

    /**
     * Prepares an action and saves it as a pending card.
     *
     * @throws ActionProblem
     */
    public static function propose(User $admin, string $toolName, array $args): AssistantAction
    {
        $type = Str::after($toolName, self::PREFIX);
        $method = Str::camel($type);
        $plan = self::$method(array_map(fn ($value) => is_string($value) ? trim($value) : $value, $args));

        return AssistantAction::create([
            'user_id' => $admin->id,
            'type' => $type,
            'title' => $plan['title'],
            'lines' => $plan['lines'],
            'plan' => [
                'controller' => $plan['controller'],
                'method' => $plan['method'],
                'models' => collect($plan['models'] ?? [])->map(fn (Model $model) => [get_class($model), $model->getKey()])->all(),
                'input' => $plan['input'] ?? [],
                'undo' => $plan['undo'] ?? false,
            ],
            'status' => 'pending',
        ]);
    }

    /**
     * Runs a confirmed action through its controller.
     */
    public static function confirm(AssistantAction $action, User $admin): AssistantAction
    {
        if (! $action->isPending() || $action->isExpired()) {
            return $action;
        }

        $plan = $action->plan;
        $models = [];

        foreach ($plan['models'] as $parameter => [$class, $id]) {
            $model = $class::find($id);

            if (! $model) {
                return self::finish($action, 'failed', 'That record no longer exists. It may have been deleted.');
            }

            $models[$parameter] = $model;
        }

        $result = ControllerRunner::run($admin, $plan['controller'], $plan['method'], $models, $plan['input']);

        if (! $result['ok']) {
            return self::finish($action, 'failed', $result['message']);
        }

        $undoable = $plan['undo']
            ? array_values(array_filter($result['created'], fn ($record) => in_array($record[0], self::UNDOABLE, true)))
            : [];

        $action->forceFill([
            'created_records' => $undoable,
            'confirmed_at' => now(),
            'undo_until' => $undoable ? now()->addMinutes(AssistantAction::UNDO_WITHIN_MINUTES) : null,
        ]);

        return self::finish($action, 'done', $result['message']);
    }

    /**
     * Deletes what a confirmed action just created. Notifications that already
     * went out to tenants can't be taken back.
     */
    public static function undo(AssistantAction $action): AssistantAction
    {
        if (! $action->canUndo()) {
            return $action;
        }

        foreach (array_reverse($action->created_records ?? []) as [$class, $id]) {
            $record = $class::find($id);

            if ($record instanceof Bill && $record->payments()->exists()) {
                continue;
            }

            $bill = $record instanceof Payment ? $record->bill : null;
            $record?->delete();
            $bill?->updatePaymentStatus();
        }

        $action->forceFill(['undo_until' => null]);

        return self::finish($action, 'undone', 'Undone.');
    }

    private static function finish(AssistantAction $action, string $status, string $message): AssistantAction
    {
        $action->forceFill(['status' => $status, 'result_message' => $message])->save();

        return $action;
    }

    /**
     * name => [description, JSON-schema properties, required].
     */
    private static function catalog(): array
    {
        $tenant = ['type' => 'string', 'description' => 'Tenant name as the admin said it.'];
        $room = ['type' => 'string', 'description' => 'Room number, e.g. "4".'];
        $month = ['type' => 'string', 'description' => 'Billing month YYYY-MM. Leave out for the oldest unpaid / current month.'];
        $date = ['type' => 'string', 'description' => 'Date YYYY-MM-DD. Leave out for today.'];
        $money = ['type' => 'number', 'description' => 'Amount in pesos.'];
        $text = fn (string $description) => ['type' => 'string', 'description' => $description];
        $flag = fn (string $description) => ['type' => 'boolean', 'description' => $description];

        return [
            'add_tenant' => ['Add a new tenant and email them an invitation to set their password.', [
                'full_name' => $text('Full name.'), 'email' => $text('Email address.'), 'room_number' => $room,
                'contact_number' => $text('Phone number, if given.'), 'emergency_contact' => $text('Emergency contact, if given.'),
                'valid_id_reference' => $text('ID type and safe reference, if given.'),
                'move_in_date' => $date, 'lease_end_date' => $date, 'deposit_amount' => $money,
            ], ['full_name', 'email']],
            'update_tenant' => ['Change a tenant\'s details. Only send the fields to change.', [
                'tenant' => $tenant, 'full_name' => $text('New full name.'), 'email' => $text('New email.'),
                'contact_number' => $text('New phone.'), 'emergency_contact' => $text('New emergency contact.'), 'lease_end_date' => $date,
            ], ['tenant']],
            'transfer_room' => ['Move an active tenant to another room.', ['tenant' => $tenant, 'room_number' => $room], ['tenant', 'room_number']],
            'move_out' => ['Record that a tenant moved out and work out the deposit refund.', [
                'tenant' => $tenant, 'move_out_date' => $date, 'deposit_deductions' => $money, 'notes' => $text('Reason for deductions or notes.'),
            ], ['tenant']],
            'resend_invitation' => ['Email a tenant the invitation to set their password again.', ['tenant' => $tenant], ['tenant']],
            'approve_applicant' => ['Approve someone who applied online as a tenant.', ['name_or_email' => $text('Applicant name or email.')], ['name_or_email']],
            'add_room' => ['Add a new room.', [
                'room_number' => $room, 'monthly_rate' => $money, 'capacity' => ['type' => 'integer', 'description' => 'Beds / tenants. Default 1.'],
                'type' => $text('single, double, or shared.'), 'floor' => ['type' => 'integer', 'description' => 'Floor number. Default 1.'],
                'electricity_rate' => ['type' => 'number', 'description' => 'Room rate in pesos per kWh.'],
                'water_rate' => ['type' => 'number', 'description' => 'Room rate in pesos per cubic meter.'],
            ], ['room_number', 'monthly_rate']],
            'update_room' => ['Change a room\'s rent, utility rates, capacity, type, or mark it under maintenance / available.', [
                'room_number' => $room, 'monthly_rate' => $money, 'capacity' => ['type' => 'integer', 'description' => 'New capacity.'],
                'type' => $text('single, double, or shared.'), 'under_maintenance' => $flag('true to mark under maintenance, false to open it again.'),
                'electricity_rate' => ['type' => 'number', 'description' => 'Room rate in pesos per kWh.'],
                'water_rate' => ['type' => 'number', 'description' => 'Room rate in pesos per cubic meter.'],
            ], ['room_number']],
            'generate_bills' => ['Create this month\'s bills for every active tenant (meter readings are included).', [
                'month' => $month, 'due_date' => $date,
            ], []],
            'create_bill' => ['Create one bill for a tenant.', [
                'tenant' => $tenant, 'month' => $month, 'electricity_amount' => $money, 'water_amount' => $money, 'other_charges' => $money, 'due_date' => $date,
            ], ['tenant']],
            'add_charge' => ['Add an extra charge (key, damage, etc.) to a tenant\'s bill.', [
                'tenant' => $tenant, 'amount' => $money, 'reason' => $text('What the charge is for.'), 'month' => $month,
            ], ['tenant', 'amount']],
            'change_due_date' => ['Change the due date of a tenant\'s bill.', ['tenant' => $tenant, 'due_date' => $date, 'month' => $month], ['tenant', 'due_date']],
            'record_payment' => ['Record a payment a tenant made (cash, GCash, or bank transfer).', [
                'tenant' => $tenant, 'amount' => $money, 'method' => $text('cash, GCash, or bank transfer. Default cash.'),
                'month' => $month, 'payment_date' => $date, 'reference_number' => $text('GCash/bank reference, if any.'),
            ], ['tenant', 'amount']],
            'approve_receipt' => ['Approve a receipt a tenant uploaded and record the payment.', ['tenant' => $tenant, 'amount' => $money], ['tenant']],
            'reject_receipt' => ['Reject a receipt a tenant uploaded, with the reason.', ['tenant' => $tenant, 'reason' => $text('Why it is rejected.')], ['tenant', 'reason']],
            'send_reminders' => ['Email reminders to tenants with bills due soon or overdue.', [], []],
            'save_meter_reading' => ['Save a room\'s electricity and/or water meter reading for a month.', [
                'room_number' => $room, 'month' => $month,
                'electricity_current' => ['type' => 'number', 'description' => 'Current electricity meter (kWh).'],
                'water_current' => ['type' => 'number', 'description' => 'Current water meter (cubic meters).'],
                'electricity_previous' => ['type' => 'number', 'description' => 'Previous electricity reading, if the admin gave it.'],
                'water_previous' => ['type' => 'number', 'description' => 'Previous water reading, if the admin gave it.'],
            ], ['room_number']],
            'update_settings' => ['Change reminder days or how long receipt images are kept.', [
                'reminder_days_before_due' => ['type' => 'integer', 'description' => '0-30.'], 'receipt_keep_months' => ['type' => 'integer', 'description' => '0 keeps forever.'],
            ], []],
            'add_expense' => ['Record a boarding house expense.', [
                'description' => $text('What was bought or paid.'), 'amount' => $money, 'expense_date' => $date,
                'category' => $text('One of: '.implode(', ', array_keys(Expense::CATEGORIES)).'.'),
            ], ['description', 'amount']],
            'post_announcement' => ['Post an announcement to all tenants. Write a clear, friendly title and body in Cebuano from what the admin said.', [
                'title' => $text('Short title.'), 'body' => $text('Full message.'), 'pinned' => $flag('Pin it to the top.'),
            ], ['title', 'body']],
            'pin_announcement' => ['Pin or unpin an existing announcement.', ['title' => $text('Title of the announcement.'), 'pinned' => $flag('true to pin, false to unpin.')], ['title', 'pinned']],
            'reply_concern' => ['Reply to a tenant\'s concern and/or change its status.', [
                'tenant' => $tenant, 'subject' => $text('Words from the concern subject, if needed to tell them apart.'),
                'reply' => $text('Reply the tenant will see.'), 'status' => $text('open, in-review, or resolved.'),
            ], []],
            'update_repair' => ['Mark a repair request as in progress. It closes only after Ate Jackie verifies the tool-return photo.', [
                'room_number' => $room, 'tenant' => $tenant, 'status' => $text('in-progress.'),
            ], ['status']],
            'forward_repair' => ['Forward an open repair to a maintenance worker (panday) with instructions; they get a ticket notification.', [
                'room_number' => $room, 'tenant' => $tenant, 'panday' => $text('Name of the panday (maintenance account). Leave out if there is only one.'),
                'instructions' => $text('What the panday should do, from what the admin said.'),
                'priority' => $text('low, medium, or high. Leave out to keep it.'), 'target_date' => $date,
            ], ['instructions']],
            'add_checklist_task' => ['Add a task to the caretaker morning/evening checklist.', [
                'task' => $text('The exact task the caretaker must complete.'),
                'period' => $text('morning, evening, or both.'),
            ], ['task', 'period']],
            'remove_checklist_task' => ['Remove a task from the caretaker checklist.', [
                'task' => $text('The existing task title or distinctive words from it.'),
            ], ['task']],
            'add_staff' => ['Add a caretaker or a maintenance worker (panday) account and email them an invitation to set their password. Never for admins.', [
                'name' => $text('Full name.'), 'email' => $text('Email they will log in with.'),
                'role' => $text('caretaker, or maintenance for a panday.'),
            ], ['name', 'email', 'role']],
            'visitor_left' => ['Mark a visitor who is still inside as having left.', ['visitor_name' => $text('Visitor name.')], ['visitor_name']],
            'update_landing_page' => ['Change the public landing page headline, intro, or which sections show.', [
                'headline' => $text('New headline.'), 'intro' => $text('New intro paragraph.'),
                'show_rooms' => $flag('Show vacant rooms.'), 'show_gallery' => $flag('Show the photo gallery.'),
            ], []],
        ];
    }

    // Each method below returns a plan: title, card lines, the controller
    // action to run on Confirm, its route models and form input.

    private static function addTenant(array $a): array
    {
        $room = filled($a['room_number'] ?? null) ? Resolve::room($a['room_number']) : null;

        if ($room && ! $room->hasAvailableCapacity()) {
            throw new ActionProblem("Puno na o under maintenance ang Room {$room->room_number}.");
        }

        $input = [
            'full_name' => $a['full_name'] ?? '',
            'email' => Str::lower($a['email'] ?? ''),
            'room_id' => $room?->id,
            'contact_number' => ($a['contact_number'] ?? '') ?: 'Pending update',
            'emergency_contact' => ($a['emergency_contact'] ?? '') ?: 'Pending update',
            'valid_id_reference' => ($a['valid_id_reference'] ?? '') ?: null,
            'move_in_date' => Resolve::date($a['move_in_date'] ?? null),
            'lease_end_date' => filled($a['lease_end_date'] ?? null) ? Resolve::date($a['lease_end_date']) : null,
            'status' => $room ? 'active' : 'reserved',
            'deposit_amount' => (float) ($a['deposit_amount'] ?? 0),
            'deposit_status' => (float) ($a['deposit_amount'] ?? 0) > 0 ? 'held' : 'none',
        ];

        return [
            'title' => 'Add tenant',
            'lines' => array_values(array_filter([
                ['Name', $input['full_name']],
                ['Email', $input['email']],
                ['Room', $room ? "Room {$room->room_number} (".Tools::peso($room->monthly_rate).'/month)' : 'None yet (reserved)'],
                ['Phone', $input['contact_number']],
                ['Move-in', $input['move_in_date']],
                $input['lease_end_date'] ? ['Contract end', $input['lease_end_date']] : null,
                $input['valid_id_reference'] ? ['Valid ID', $input['valid_id_reference']] : null,
                $input['deposit_amount'] > 0 ? ['Deposit', Tools::peso($input['deposit_amount'])] : null,
                ['Invitation', 'Will be sent by email'],
            ])),
            'controller' => TenantController::class, 'method' => 'store', 'input' => $input,
        ];
    }

    private static function tenantInput(Tenant $tenant, array $changes = []): array
    {
        return array_merge([
            'user_id' => $tenant->user_id,
            'room_id' => $tenant->room_id,
            'full_name' => $tenant->full_name,
            'contact_number' => $tenant->contact_number,
            'email' => $tenant->email,
            'emergency_contact' => $tenant->emergency_contact,
            'valid_id_reference' => $tenant->valid_id_reference,
            'move_in_date' => $tenant->move_in_date?->toDateString(),
            'lease_end_date' => $tenant->lease_end_date?->toDateString(),
            'status' => $tenant->status,
            'deposit_amount' => $tenant->deposit_amount,
            'deposit_status' => in_array($tenant->deposit_status, ['none', 'held'], true) ? $tenant->deposit_status : null,
        ], $changes);
    }

    private static function updateTenant(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null);
        $labels = ['full_name' => 'Name', 'email' => 'Email', 'contact_number' => 'Phone', 'emergency_contact' => 'Emergency contact', 'lease_end_date' => 'Lease end'];
        $changes = array_filter(array_intersect_key($a, $labels), 'filled');

        if (! $changes) {
            throw new ActionProblem('Unsa man ang usbon? Wala koy nadawat nga bag-ong detalye.');
        }

        if (isset($changes['lease_end_date'])) {
            $changes['lease_end_date'] = Resolve::date($changes['lease_end_date']);
        }

        return [
            'title' => 'Update tenant',
            'lines' => array_merge([['Tenant', $tenant->full_name]], collect($changes)->map(fn ($value, $key) => [$labels[$key], $tenant->{$key} instanceof \DateTimeInterface ? $tenant->{$key}->toDateString()." → {$value}" : ($tenant->{$key} ?: '—')." → {$value}"])->values()->all()),
            'controller' => TenantController::class, 'method' => 'update',
            'models' => ['tenant' => $tenant], 'input' => self::tenantInput($tenant, $changes),
        ];
    }

    private static function transferRoom(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null, activeOnly: true);
        $room = Resolve::room($a['room_number'] ?? null);

        if ($room->id === $tenant->room_id) {
            throw new ActionProblem("Naa na si {$tenant->full_name} sa Room {$room->room_number}.");
        }

        if (! $room->hasAvailableCapacity()) {
            throw new ActionProblem("Puno na o under maintenance ang Room {$room->room_number}.");
        }

        return [
            'title' => 'Transfer room',
            'lines' => [['Tenant', $tenant->full_name], ['From', $tenant->room ? "Room {$tenant->room->room_number}" : 'No room'], ['To', "Room {$room->room_number} (".Tools::peso($room->monthly_rate).'/month)'], ['Note', 'Next bills use the new rate']],
            'controller' => TenantController::class, 'method' => 'update',
            'models' => ['tenant' => $tenant], 'input' => self::tenantInput($tenant, ['room_id' => $room->id, 'status' => 'active']),
        ];
    }

    private static function moveOut(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null, activeOnly: true);
        $deposit = (float) $tenant->deposit_amount;
        $deductions = (float) ($a['deposit_deductions'] ?? 0);
        $refund = max($deposit - $deductions, 0);

        return [
            'title' => 'Move out',
            'lines' => [['Tenant', $tenant->full_name], ['Room', $tenant->room ? "Room {$tenant->room->room_number}" : '—'], ['Move-out', Resolve::date($a['move_out_date'] ?? null)], ['Deposit', Tools::peso($deposit)], ['Deductions', Tools::peso($deductions)], ['Refund', Tools::peso($refund)]],
            'controller' => MoveOutController::class, 'method' => 'store', 'models' => ['tenant' => $tenant],
            'input' => ['move_out_date' => Resolve::date($a['move_out_date'] ?? null), 'deposit_deductions' => $deductions, 'move_out_notes' => $a['notes'] ?? null],
        ];
    }

    private static function resendInvitation(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null);

        return [
            'title' => 'Send invitation',
            'lines' => [['Tenant', $tenant->full_name], ['Email', $tenant->email]],
            'controller' => TenantController::class, 'method' => 'invite', 'models' => ['tenant' => $tenant],
        ];
    }

    private static function approveApplicant(array $a): array
    {
        $user = Resolve::applicant($a['name_or_email'] ?? null);
        $room = $user->preferredRoom;

        return [
            'title' => 'Approve applicant',
            'lines' => [['Name', $user->name], ['Email', $user->email], ['Preferred room', $room ? "Room {$room->room_number}" : 'None'], ['Applied', $user->created_at->format('M j, Y')]],
            'controller' => UserController::class, 'method' => 'approveTenant', 'models' => ['user' => $user],
        ];
    }

    private static function roomInput(Room $room, array $changes = []): array
    {
        return array_merge($room->only(['room_number', 'floor', 'type', 'capacity', 'monthly_rate', 'electricity_rate', 'water_rate', 'status']), $changes);
    }

    private static function addRoom(array $a): array
    {
        $type = Str::lower($a['type'] ?? 'single');

        if (! in_array($type, Room::TYPES, true)) {
            throw new ActionProblem('Ang type kay single, double, o shared ra.');
        }

        $input = [
            'room_number' => trim(preg_replace('/^room\s*/i', '', (string) ($a['room_number'] ?? ''))),
            'floor' => (int) ($a['floor'] ?? 1),
            'type' => $type,
            'capacity' => max((int) ($a['capacity'] ?? 1), 1),
            'monthly_rate' => Resolve::amount($a['monthly_rate'] ?? null, 'monthly rate'),
            'electricity_rate' => (float) ($a['electricity_rate'] ?? Setting::number('electricity_rate')),
            'water_rate' => (float) ($a['water_rate'] ?? Setting::number('water_rate')),
            'status' => 'available',
        ];

        return [
            'title' => 'Add room',
            'lines' => [['Room', $input['room_number']], ['Type', $type], ['Capacity', $input['capacity']], ['Rate', Tools::peso($input['monthly_rate']).'/month'], ['Floor', $input['floor']]],
            'controller' => RoomController::class, 'method' => 'store', 'input' => $input,
        ];
    }

    private static function updateRoom(array $a): array
    {
        $room = Resolve::room($a['room_number'] ?? null);
        $changes = [];
        $lines = [['Room', $room->room_number]];

        if (filled($a['monthly_rate'] ?? null)) {
            $changes['monthly_rate'] = Resolve::amount($a['monthly_rate'], 'rate');
            $lines[] = ['Rate', Tools::peso($room->monthly_rate).' → '.Tools::peso($changes['monthly_rate'])];
        }

        if (filled($a['electricity_rate'] ?? null)) {
            $changes['electricity_rate'] = Resolve::amount($a['electricity_rate'], 'electricity rate');
            $lines[] = ['Electricity', Tools::peso($room->electricity_rate).' → '.Tools::peso($changes['electricity_rate']).' per kWh'];
        }

        if (filled($a['water_rate'] ?? null)) {
            $changes['water_rate'] = Resolve::amount($a['water_rate'], 'water rate');
            $lines[] = ['Water', Tools::peso($room->water_rate).' → '.Tools::peso($changes['water_rate']).' per m³'];
        }

        if (filled($a['capacity'] ?? null)) {
            $changes['capacity'] = max((int) $a['capacity'], 1);
            $lines[] = ['Capacity', "{$room->capacity} → {$changes['capacity']}"];
        }

        if (filled($a['type'] ?? null)) {
            $changes['type'] = Str::lower($a['type']);
            $lines[] = ['Type', "{$room->type} → {$changes['type']}"];
        }

        if (array_key_exists('under_maintenance', $a) && $a['under_maintenance'] !== null) {
            $changes['status'] = filter_var($a['under_maintenance'], FILTER_VALIDATE_BOOL) ? 'under maintenance' : 'available';
            $lines[] = ['Status', "{$room->status} → {$changes['status']}"];
        }

        if (! $changes) {
            throw new ActionProblem('Unsa man ang usbon sa room? Rent, utility rate, capacity, type, o maintenance?');
        }

        return ['title' => 'Update room', 'lines' => $lines, 'controller' => RoomController::class, 'method' => 'update', 'models' => ['room' => $room], 'input' => self::roomInput($room, $changes)];
    }

    private static function generateBills(array $a): array
    {
        $month = Resolve::month($a['month'] ?? null);
        $due = Resolve::date($a['due_date'] ?? null, $month->copy()->day(10));
        $count = Tenant::where('status', 'active')->whereNotNull('room_id')
            ->whereDoesntHave('bills', fn ($query) => $query->whereBetween('billing_month', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]))
            ->count();

        if ($count === 0) {
            throw new ActionProblem("Naa nay bill ang tanang active nga tenant para sa {$month->format('F Y')}.");
        }

        return [
            'title' => 'Generate monthly bills',
            'lines' => [['Month', $month->format('F Y')], ['Due date', $due], ['Tenants', "{$count} bills will be made"], ['Electricity/water', 'From meter readings when available']],
            'controller' => BillController::class, 'method' => 'generateMonthly',
            'input' => ['billing_month' => $month->format('Y-m'), 'due_date' => $due], 'undo' => true,
        ];
    }

    private static function createBill(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null, activeOnly: true);

        if (! $tenant->room) {
            throw new ActionProblem("Walay room si {$tenant->full_name}, so dili mahimoan og bill.");
        }

        $month = Resolve::month($a['month'] ?? null);
        $input = [
            'tenant_id' => $tenant->id, 'room_id' => $tenant->room_id, 'billing_month' => $month->format('Y-m'),
            'electricity_amount' => (float) ($a['electricity_amount'] ?? 0), 'water_amount' => (float) ($a['water_amount'] ?? 0),
            'other_charges' => (float) ($a['other_charges'] ?? 0), 'due_date' => Resolve::date($a['due_date'] ?? null, $month->copy()->day(10)),
        ];
        $total = (float) $tenant->room->monthly_rate + $input['electricity_amount'] + $input['water_amount'] + $input['other_charges'];

        return [
            'title' => 'Create bill',
            'lines' => [['Tenant', $tenant->full_name], ['Month', $month->format('F Y')], ['Rent', Tools::peso($tenant->room->monthly_rate)], ['Electricity', Tools::peso($input['electricity_amount'])], ['Water', Tools::peso($input['water_amount'])], ['Other', Tools::peso($input['other_charges'])], ['Total', Tools::peso($total)], ['Due', $input['due_date']]],
            'controller' => BillController::class, 'method' => 'store', 'input' => $input, 'undo' => true,
        ];
    }

    private static function billInput(Bill $bill, array $changes = []): array
    {
        return array_merge([
            'tenant_id' => $bill->tenant_id, 'room_id' => $bill->room_id, 'billing_month' => $bill->billing_month->format('Y-m'),
            'electricity_amount' => $bill->electricity_amount, 'water_amount' => $bill->water_amount, 'other_charges' => $bill->other_charges,
            'due_date' => $bill->due_date->toDateString(),
        ], $changes);
    }

    private static function addCharge(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null);
        $bill = Resolve::bill($tenant, $a['month'] ?? null);
        $amount = Resolve::amount($a['amount'] ?? null, 'charge');

        return [
            'title' => 'Add charge',
            'lines' => [['Tenant', $tenant->full_name], ['Bill', $bill->billing_month->format('F Y')], ['Added', Tools::peso($amount).(filled($a['reason'] ?? null) ? " ({$a['reason']})" : '')], ['New total', Tools::peso($bill->total_amount + $amount)]],
            'controller' => BillController::class, 'method' => 'update', 'models' => ['bill' => $bill],
            'input' => self::billInput($bill, ['other_charges' => (float) $bill->other_charges + $amount]),
        ];
    }

    private static function changeDueDate(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null);
        $bill = Resolve::bill($tenant, $a['month'] ?? null);
        $due = Resolve::date($a['due_date'] ?? null);

        return [
            'title' => 'Change due date',
            'lines' => [['Tenant', $tenant->full_name], ['Bill', $bill->billing_month->format('F Y')], ['Due date', $bill->due_date->toDateString()." → {$due}"]],
            'controller' => BillController::class, 'method' => 'update', 'models' => ['bill' => $bill], 'input' => self::billInput($bill, ['due_date' => $due]),
        ];
    }

    private static function recordPayment(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null);
        $bill = Resolve::bill($tenant, $a['month'] ?? null);
        $amount = Resolve::amount($a['amount'] ?? null, 'bayad');
        $method = collect(Payment::METHODS)->first(fn ($option) => Str::lower($option) === Str::lower($a['method'] ?? 'cash')) ?? 'cash';

        if ($amount > $bill->balance) {
            throw new ActionProblem("Ang balanse ni {$tenant->full_name} sa {$bill->billing_month->format('F Y')} kay ".Tools::peso($bill->balance).' ra. Dili molapas ang bayad.');
        }

        return [
            'title' => 'Record payment',
            'lines' => array_values(array_filter([['Tenant', $tenant->full_name], ['Bill', $bill->billing_month->format('F Y')], ['Amount', Tools::peso($amount)." ({$method})"], ['Date', Resolve::date($a['payment_date'] ?? null)], filled($a['reference_number'] ?? null) ? ['Reference', $a['reference_number']] : null, ['Balance left', Tools::peso($bill->balance - $amount)]])),
            'controller' => PaymentController::class, 'method' => 'store', 'undo' => true,
            'input' => ['bill_id' => $bill->id, 'tenant_id' => $tenant->id, 'payment_date' => Resolve::date($a['payment_date'] ?? null), 'amount' => $amount, 'payment_method' => $method, 'reference_number' => $a['reference_number'] ?? null, 'remarks' => 'Recorded via QJ Bot'],
        ];
    }

    private static function approveReceipt(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null);
        $submission = Resolve::pendingReceipt($tenant);
        $amount = filled($a['amount'] ?? null) ? Resolve::amount($a['amount']) : (float) $submission->amount;

        return [
            'title' => 'Approve receipt',
            'lines' => [['Tenant', $tenant->full_name], ['Receipt', Tools::peso($submission->amount).', uploaded '.$submission->created_at->format('M j')], ['Reference', $submission->reference_number ?: '—'], ['Record', Tools::peso($amount)], ['Reminder', 'Check the screenshot on the Receipts page first']],
            'controller' => PaymentSubmissionController::class, 'method' => 'approve', 'models' => ['paymentSubmission' => $submission], 'input' => ['amount' => $amount],
        ];
    }

    private static function rejectReceipt(array $a): array
    {
        $tenant = Resolve::tenant($a['tenant'] ?? null);
        $submission = Resolve::pendingReceipt($tenant);

        return [
            'title' => 'Reject receipt',
            'lines' => [['Tenant', $tenant->full_name], ['Receipt', Tools::peso($submission->amount)], ['Reason', $a['reason'] ?? '']],
            'controller' => PaymentSubmissionController::class, 'method' => 'reject', 'models' => ['paymentSubmission' => $submission], 'input' => ['rejection_reason' => $a['reason'] ?? ''],
        ];
    }

    private static function sendReminders(array $a): array
    {
        $unpaid = Bill::where('status', '!=', 'paid')->count();

        return [
            'title' => 'Send bill reminders',
            'lines' => [['Unpaid bills', $unpaid], ['Who', 'Tenants due soon or overdue who were not reminded yet']],
            'controller' => BillController::class, 'method' => 'sendReminders',
        ];
    }

    private static function saveMeterReading(array $a): array
    {
        $room = Resolve::room($a['room_number'] ?? null);
        $month = Resolve::month($a['month'] ?? null);
        $last = MeterReading::where('room_id', $room->id)->whereDate('billing_month', '<', $month)->orderByDesc('billing_month')->first();
        $reading = [
            'electricity_previous' => $a['electricity_previous'] ?? $last?->electricity_current,
            'electricity_current' => $a['electricity_current'] ?? null,
            'water_previous' => $a['water_previous'] ?? $last?->water_current,
            'water_current' => $a['water_current'] ?? null,
        ];

        if ($reading['electricity_current'] === null && $reading['water_current'] === null) {
            throw new ActionProblem('Unsa man ang reading? Ihatag ang kuryente (kWh) o tubig (cubic meter).');
        }

        $lines = [['Room', $room->room_number], ['Month', $month->format('F Y')]];
        if ($reading['electricity_current'] !== null) {
            $lines[] = ['Electricity', ($reading['electricity_previous'] ?? 0).' → '.$reading['electricity_current'].' kWh'];
        }
        if ($reading['water_current'] !== null) {
            $lines[] = ['Water', ($reading['water_previous'] ?? 0).' → '.$reading['water_current'].' m³'];
        }

        return [
            'title' => 'Save meter reading', 'lines' => $lines,
            'controller' => UtilityController::class, 'method' => 'store',
            'input' => ['month' => $month->format('Y-m'), 'readings' => [$room->id => array_merge($reading, [
                'electricity_rate' => $room->electricity_rate,
                'water_rate' => $room->water_rate,
            ])]],
        ];
    }

    private static function updateSettings(array $a): array
    {
        $labels = ['reminder_days_before_due' => 'Reminder (days)', 'receipt_keep_months' => 'Receipt storage (months)'];
        $changes = array_filter(array_intersect_key($a, $labels), fn ($value) => $value !== null && $value !== '');

        if (! $changes) {
            throw new ActionProblem('Unsa nga setting ang usbon?');
        }

        $current = collect(['reminder_days_before_due', 'receipt_keep_months', 'house_rules'])->mapWithKeys(fn ($key) => [$key => Setting::get($key)])->all();

        return [
            'title' => 'Update settings',
            'lines' => collect($changes)->map(fn ($value, $key) => [$labels[$key], "{$current[$key]} → {$value}"])->values()->all(),
            'controller' => SettingController::class, 'method' => 'update', 'input' => array_merge($current, $changes),
        ];
    }

    private static function addExpense(array $a): array
    {
        $category = Str::lower($a['category'] ?? 'other');
        $category = array_key_exists($category, Expense::CATEGORIES) ? $category : 'other';
        $input = ['expense_date' => Resolve::date($a['expense_date'] ?? null), 'category' => $category, 'description' => Str::limit($a['description'] ?? '', 255, ''), 'amount' => Resolve::amount($a['amount'] ?? null, 'gasto')];

        return [
            'title' => 'Add expense',
            'lines' => [['What', $input['description']], ['Amount', Tools::peso($input['amount'])], ['Category', Expense::CATEGORIES[$category]], ['Date', $input['expense_date']]],
            'controller' => ExpenseController::class, 'method' => 'store', 'input' => $input, 'undo' => true,
        ];
    }

    private static function postAnnouncement(array $a): array
    {
        $pinned = filter_var($a['pinned'] ?? false, FILTER_VALIDATE_BOOL);

        return [
            'title' => 'Post announcement',
            'lines' => [['Title', $a['title'] ?? ''], ['Message', $a['body'] ?? ''], ['Pinned', $pinned ? 'Yes' : 'No'], ['Notify', 'All active tenants']],
            'controller' => AnnouncementController::class, 'method' => 'store', 'undo' => true,
            'input' => ['title' => $a['title'] ?? '', 'body' => $a['body'] ?? '', 'is_pinned' => $pinned ? '1' : '0'],
        ];
    }

    private static function pinAnnouncement(array $a): array
    {
        $announcement = Resolve::announcement($a['title'] ?? null);
        $pinned = filter_var($a['pinned'] ?? true, FILTER_VALIDATE_BOOL);

        return [
            'title' => $pinned ? 'Pin announcement' : 'Unpin announcement',
            'lines' => [['Announcement', $announcement->title], ['Pinned', $pinned ? 'Yes' : 'No']],
            'controller' => AnnouncementController::class, 'method' => 'update', 'models' => ['announcement' => $announcement],
            'input' => ['title' => $announcement->title, 'body' => $announcement->body, 'is_pinned' => $pinned ? '1' : '0'],
        ];
    }

    private static function replyConcern(array $a): array
    {
        $concern = Resolve::concern($a['tenant'] ?? null, $a['subject'] ?? null);
        $status = in_array($a['status'] ?? null, \App\Models\Concern::STATUSES, true) ? $a['status'] : (filled($a['reply'] ?? null) ? 'in-review' : $concern->status);

        return [
            'title' => 'Reply to concern',
            'lines' => array_values(array_filter([['Tenant', $concern->tenant?->full_name], ['Concern', $concern->subject], filled($a['reply'] ?? null) ? ['Reply', $a['reply']] : null, ['Status', "{$concern->status} → {$status}"]])),
            'controller' => ConcernController::class, 'method' => 'update', 'models' => ['concern' => $concern],
            'input' => ['status' => $status, 'admin_reply' => ($a['reply'] ?? null) ?: $concern->admin_reply],
        ];
    }

    private static function updateRepair(array $a): array
    {
        $repair = Resolve::repair($a['room_number'] ?? null, $a['tenant'] ?? null);
        $status = Str::lower($a['status'] ?? '');

        if ($status !== 'in-progress') {
            throw new ActionProblem('Ma-mark ra nako kini nga in-progress. Ang ticket ma-close human ma-verify ni Ate Jackie ang tool-return photo.');
        }

        return [
            'title' => 'Update repair',
            'lines' => [['Room', $repair->room?->room_number], ['Issue', Str::limit($repair->description, 80)], ['Status', "{$repair->status} → in-progress"]],
            'controller' => MaintenanceRequestController::class, 'method' => 'progress', 'models' => ['maintenanceRequest' => $repair],
            'input' => ['status' => 'in-progress', 'resolution_details' => $repair->resolution_details],
        ];
    }

    private static function forwardRepair(array $a): array
    {
        $repair = Resolve::repair($a['room_number'] ?? null, $a['tenant'] ?? null);
        $pandays = User::where('role', 'maintenance')
            ->when(filled($a['panday'] ?? null), fn ($query) => \App\Support\Search::apply($query, $a['panday'], ['name']))
            ->get();

        if ($pandays->isEmpty()) {
            throw new ActionProblem(filled($a['panday'] ?? null) ? "Walay panday nga ginganlan og \"{$a['panday']}\"." : 'Wala pay panday account. Himoa una sa Users & Approvals, role Maintenance (Panday).');
        }

        if ($pandays->count() > 1) {
            throw new ActionProblem('Kinsa nga panday? '.$pandays->pluck('name')->implode(', ').'.');
        }

        $panday = $pandays->first();
        $priority = in_array($a['priority'] ?? null, \App\Models\MaintenanceRequest::PRIORITIES, true) ? $a['priority'] : $repair->priority;
        $target = filled($a['target_date'] ?? null) ? Resolve::date($a['target_date']) : null;

        return [
            'title' => 'Forward repair to panday',
            'lines' => array_values(array_filter([['Room', $repair->room?->room_number], ['Issue', Str::limit($repair->description, 80)], ['Panday', $panday->name], ['Instructions', $a['instructions'] ?? ''], ['Priority', $priority], $target ? ['Target', $target] : null])),
            'controller' => MaintenanceRequestController::class, 'method' => 'assign', 'models' => ['maintenanceRequest' => $repair],
            'input' => ['assigned_to' => $panday->id, 'assignment_notes' => $a['instructions'] ?? '', 'priority' => $priority, 'target_date' => $target],
        ];
    }

    private static function addChecklistTask(array $a): array
    {
        $period = Str::lower($a['period'] ?? '');
        if (! in_array($period, ChecklistItem::PERIODS, true)) {
            throw new ActionProblem('Ang schedule kay morning, evening, o both ra.');
        }

        return [
            'title' => 'Add caretaker checklist task',
            'lines' => [['Task', $a['task'] ?? ''], ['Schedule', ucfirst($period)]],
            'controller' => ChecklistController::class,
            'method' => 'storeItem',
            'input' => ['title' => $a['task'] ?? '', 'period' => $period],
            'undo' => true,
        ];
    }

    private static function removeChecklistTask(array $a): array
    {
        $term = trim((string) ($a['task'] ?? ''));
        $matches = ChecklistItem::where('title', 'like', '%'.$term.'%')->get();

        if ($matches->isEmpty()) {
            throw new ActionProblem('Wala nako nakit-i kana nga checklist task.');
        }
        if ($matches->count() > 1) {
            throw new ActionProblem('Daghan ang pareho. Pilia pinaagi sa mas klaro nga words: '.$matches->pluck('title')->implode('; '));
        }

        $item = $matches->first();
        return [
            'title' => 'Remove caretaker checklist task',
            'lines' => [['Task', $item->title], ['Schedule', ucfirst($item->period)]],
            'controller' => ChecklistController::class,
            'method' => 'destroyItem',
            'models' => ['checklistItem' => $item],
        ];
    }

    private static function addStaff(array $a): array
    {
        $role = match (Str::lower($a['role'] ?? '')) {
            'caretaker' => 'caretaker',
            'maintenance', 'panday', 'carpenter' => 'maintenance',
            default => throw new ActionProblem('Caretaker o panday (maintenance) ra ang ma-add nako. Ang admin, himoa sa Users & Approvals > Add User.'),
        };
        $email = Str::lower($a['email'] ?? '');

        if (User::where('email', $email)->exists()) {
            throw new ActionProblem("Naa nay account nga naggamit sa {$email}.");
        }

        return [
            'title' => $role === 'caretaker' ? 'Add caretaker' : 'Add panday',
            'lines' => [['Name', $a['name'] ?? ''], ['Email', $email], ['Role', \App\Support\StaffAccess::LABELS[$role]], ['Invitation', 'Will be sent by email']],
            'controller' => UserController::class, 'method' => 'inviteStaff',
            'input' => ['name' => $a['name'] ?? '', 'email' => $email, 'role' => $role],
        ];
    }

    private static function visitorLeft(array $a): array
    {
        $visitor = Resolve::visitorInside($a['visitor_name'] ?? null);

        return [
            'title' => 'Visitor left',
            'lines' => [['Visitor', $visitor->visitor_name], ['Visiting', $visitor->tenant?->full_name], ['Time in', \App\Models\Visitor::formatTime($visitor->time_in)]],
            'controller' => VisitorController::class, 'method' => 'checkout', 'models' => ['visitor' => $visitor],
        ];
    }

    private static function updateLandingPage(array $a): array
    {
        $current = [
            'landing_headline' => Setting::get('landing_headline'), 'landing_intro' => Setting::get('landing_intro'),
            'landing_show_rooms' => Setting::get('landing_show_rooms') === '1', 'landing_show_gallery' => Setting::get('landing_show_gallery') === '1',
        ];
        $input = [
            'landing_headline' => ($a['headline'] ?? '') ?: $current['landing_headline'],
            'landing_intro' => ($a['intro'] ?? '') ?: $current['landing_intro'],
            'landing_show_rooms' => isset($a['show_rooms']) ? filter_var($a['show_rooms'], FILTER_VALIDATE_BOOL) : $current['landing_show_rooms'],
            'landing_show_gallery' => isset($a['show_gallery']) ? filter_var($a['show_gallery'], FILTER_VALIDATE_BOOL) : $current['landing_show_gallery'],
        ];
        $lines = [];
        filled($a['headline'] ?? null) && $lines[] = ['Headline', $input['landing_headline']];
        filled($a['intro'] ?? null) && $lines[] = ['Intro', $input['landing_intro']];
        isset($a['show_rooms']) && $lines[] = ['Vacant rooms', $input['landing_show_rooms'] ? 'Show' : 'Hide'];
        isset($a['show_gallery']) && $lines[] = ['Gallery', $input['landing_show_gallery'] ? 'Show' : 'Hide'];

        if (! $lines) {
            throw new ActionProblem('Unsa man ang usbon sa landing page?');
        }

        $input['landing_show_rooms'] = $input['landing_show_rooms'] ? '1' : '0';
        $input['landing_show_gallery'] = $input['landing_show_gallery'] ? '1' : '0';

        return ['title' => 'Update landing page', 'lines' => $lines, 'controller' => LandingPageController::class, 'method' => 'updateContent', 'input' => $input];
    }
}
