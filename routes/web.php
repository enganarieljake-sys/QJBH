<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AssistantController;
use App\Http\Controllers\Admin\BillController;
use App\Http\Controllers\Admin\ChecklistController;
use App\Http\Controllers\Admin\ConcernController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataResetController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\MaintenanceRequestController;
use App\Http\Controllers\Admin\MoveOutController;
use App\Http\Controllers\Admin\PaymentChannelController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentSubmissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Admin\VisitorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfficialReceiptController;
use App\Http\Controllers\PaymentFileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PublicRoomController;
use App\Http\Controllers\Tenant\TenantAnnouncementController;
use App\Http\Controllers\Tenant\TenantBillController;
use App\Http\Controllers\Tenant\TenantConcernController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\TenantMaintenanceRequestController;
use App\Http\Controllers\Tenant\TenantPaymentController;
use App\Http\Controllers\Tenant\TenantReceiptController;
use App\Http\Controllers\Tenant\TenantVisitorController;
use App\Support\StaffAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicRoomController::class, 'landing'])->name('landing');
Route::get('/rooms/{room}/photo', [PublicRoomController::class, 'photo'])->name('rooms.photo');
Route::get('/house-rules', [PublicRoomController::class, 'houseRules'])->name('house-rules');
Route::get('/gallery/{landingPhoto}', [PublicRoomController::class, 'landingPhoto'])->name('landing-photos.show');

// The QR on an official receipt: a signed link anyone can scan to check the receipt is real.
Route::get('/receipts/verify/{payment}', [OfficialReceiptController::class, 'verify'])->middleware('signed')->name('receipts.verify');

Route::get('/cron/bill-reminders', [CronController::class, 'billReminders'])->name('cron.bill-reminders');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invitation', [InvitationController::class, 'store'])->middleware('throttle:10,1')->name('invitation.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/payment-channels/{paymentChannel}/qr', [PaymentFileController::class, 'qr'])->name('payment-channels.qr');
    Route::get('/receipts/{paymentSubmission}/image', [PaymentFileController::class, 'receipt'])->name('receipts.image');
    Route::get('/payments/{payment}/official-receipt', [OfficialReceiptController::class, 'show'])->name('payments.official-receipt');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1,password-change')->name('profile.password');
    Route::get('/avatars/{user}', [ProfileController::class, 'avatar'])->name('avatars.show');
});

Route::middleware(['auth', 'throttle:30,1'])->prefix('push')->name('push.')->group(function () {
    Route::get('/config', [PushSubscriptionController::class, 'config'])->name('config');
    Route::post('/subscriptions', [PushSubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::delete('/subscriptions', [PushSubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
    Route::post('/test', [PushSubscriptionController::class, 'test'])->middleware('throttle:3,10')->name('test');
});

Route::get('/dashboard', fn () => redirect(StaffAccess::home(auth()->user())))->middleware('auth')->name('dashboard');

// Owner, caretakers, and maintenance staff; StaffAccess decides which pages each may open.
Route::middleware(['auth', 'role:admin,caretaker,maintenance', 'staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/analytics', AnalyticsController::class)->name('analytics');
        Route::resource('rooms', RoomController::class);
        Route::post('tenants/{tenant}/invite', [TenantController::class, 'invite'])->name('tenants.invite');
        Route::get('tenants/{tenant}/move-out', [MoveOutController::class, 'create'])->name('tenants.move-out');
        Route::post('tenants/{tenant}/move-out', [MoveOutController::class, 'store'])->name('tenants.move-out.store');
        Route::resource('tenants', TenantController::class);
        Route::post('bills/generate-monthly', [BillController::class, 'generateMonthly'])->name('bills.generate');
        Route::post('bills/send-reminders', [BillController::class, 'sendReminders'])->name('bills.send-reminders');
        Route::get('utilities', [UtilityController::class, 'index'])->name('utilities.index');
        Route::post('utilities', [UtilityController::class, 'store'])->name('utilities.store');
        Route::get('landing-page', [LandingPageController::class, 'edit'])->name('landing.edit');
        Route::put('landing-page', [LandingPageController::class, 'updateContent'])->name('landing.update');
        Route::put('landing-page/background', [LandingPageController::class, 'setBackground'])->name('landing.background');
        Route::post('landing-page/photos', [LandingPageController::class, 'storePhotos'])->name('landing.photos.store');
        Route::patch('landing-page/photos/{landingPhoto}', [LandingPageController::class, 'updatePhoto'])->name('landing.photos.update');
        Route::patch('landing-page/photos/{landingPhoto}/move', [LandingPageController::class, 'movePhoto'])->name('landing.photos.move');
        Route::delete('landing-page/photos/{landingPhoto}', [LandingPageController::class, 'destroyPhoto'])->name('landing.photos.destroy');
        Route::post('data-reset/code', [DataResetController::class, 'sendCode'])->middleware('throttle:3,10,reset-code')->name('data-reset.code');
        Route::delete('data-reset', [DataResetController::class, 'destroy'])->middleware('throttle:6,10,reset-confirm')->name('data-reset.destroy');
        Route::get('assistant', [AssistantController::class, 'index'])->name('assistant.index');
        Route::post('assistant/ask', [AssistantController::class, 'ask'])->middleware('throttle:8,1,assistant-ask')->name('assistant.ask');
        Route::post('assistant/quick', [AssistantController::class, 'quick'])->middleware('throttle:30,1,assistant-quick')->name('assistant.quick');
        Route::delete('assistant', [AssistantController::class, 'clear'])->name('assistant.clear');
        Route::get('assistant/activity', [AssistantController::class, 'activity'])->name('assistant.activity');
        Route::post('assistant/actions/{action}/confirm', [AssistantController::class, 'confirm'])->middleware('throttle:30,1,assistant-action')->name('assistant.actions.confirm');
        Route::post('assistant/actions/{action}/cancel', [AssistantController::class, 'cancel'])->name('assistant.actions.cancel');
        Route::post('assistant/actions/{action}/undo', [AssistantController::class, 'undo'])->middleware('throttle:30,1,assistant-action')->name('assistant.actions.undo');
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::resource('bills', BillController::class);
        Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('payment-channels', PaymentChannelController::class)->except(['show']);
        Route::get('payment-submissions', [PaymentSubmissionController::class, 'index'])->name('payment-submissions.index');
        Route::get('payment-submissions/{paymentSubmission}', [PaymentSubmissionController::class, 'show'])->name('payment-submissions.show');
        Route::patch('payment-submissions/{paymentSubmission}/approve', [PaymentSubmissionController::class, 'approve'])->name('payment-submissions.approve');
        Route::patch('payment-submissions/{paymentSubmission}/reject', [PaymentSubmissionController::class, 'reject'])->name('payment-submissions.reject');
        Route::post('maintenance-requests/{maintenanceRequest}/assign', [MaintenanceRequestController::class, 'assign'])->name('maintenance-requests.assign');
        Route::patch('maintenance-requests/{maintenanceRequest}/progress', [MaintenanceRequestController::class, 'progress'])->name('maintenance-requests.progress');
        Route::post('maintenance-requests/{maintenanceRequest}/tools', [MaintenanceRequestController::class, 'uploadTools'])->name('maintenance-requests.tools.upload');
        Route::post('maintenance-requests/{maintenanceRequest}/tools/return', [MaintenanceRequestController::class, 'returnTools'])->name('maintenance-requests.tools.return');
        Route::get('maintenance-history', [MaintenanceRequestController::class, 'history'])->name('maintenance-history.index');
        Route::get('maintenance-history/{maintenanceRequest}', [MaintenanceRequestController::class, 'historyShow'])->name('maintenance-history.show');
        Route::post('maintenance-history/{maintenanceRequest}/trigger-admin', [MaintenanceRequestController::class, 'triggerAdmin'])->middleware('throttle:3,10,maintenance-trigger')->name('maintenance-history.trigger-admin');
        Route::patch('maintenance-requests/{maintenanceRequest}/tools/verify', [MaintenanceRequestController::class, 'verifyTools'])->name('maintenance-requests.tools.verify');
        Route::get('maintenance-requests/{maintenanceRequest}/tools/{kind}', [MaintenanceRequestController::class, 'toolPhoto'])->name('maintenance-requests.tools.photo');
        Route::resource('maintenance-requests', MaintenanceRequestController::class)
            ->parameters(['maintenance-requests' => 'maintenanceRequest']);
        Route::get('daily-checklist', [ChecklistController::class, 'index'])->name('checklists.index');
        Route::post('daily-checklist/report', [ChecklistController::class, 'submit'])->name('checklists.submit');
        Route::post('daily-checklist/reports/{checklistReport}/reason', [ChecklistController::class, 'missedReason'])->name('checklists.missed-reason');
        Route::post('daily-checklist/reports/{checklistReport}/override', [ChecklistController::class, 'override'])->name('checklists.override');
        Route::post('daily-checklist/items', [ChecklistController::class, 'storeItem'])->name('checklists.items.store');
        Route::put('daily-checklist/items/{checklistItem}', [ChecklistController::class, 'updateItem'])->name('checklists.items.update');
        Route::delete('daily-checklist/items/{checklistItem}', [ChecklistController::class, 'destroyItem'])->name('checklists.items.destroy');
        Route::resource('concerns', ConcernController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::get('visitors', [VisitorController::class, 'index'])->name('visitors.index');
        Route::patch('visitors/{visitor}/checkout', [VisitorController::class, 'checkout'])->name('visitors.checkout');
        Route::patch('users/{user}/approve-tenant', [UserController::class, 'approveTenant'])->name('users.approve-tenant');
        Route::post('users/invite-staff', [UserController::class, 'inviteStaff'])->name('users.invite-staff');
        Route::resource('users', UserController::class);
        Route::resource('announcements', AnnouncementController::class)->except(['show']);
        Route::resource('expenses', ExpenseController::class)->except(['show']);
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/tenants/export', [ReportController::class, 'exportTenants'])->name('reports.tenants.export');
        Route::get('reports/billing/export', [ReportController::class, 'exportBilling'])->name('reports.billing.export');
    });

Route::middleware(['auth', 'role:tenant'])
    ->prefix('tenant')
    ->name('tenant.')
    ->group(function () {
        Route::get('/dashboard', TenantDashboardController::class)->name('dashboard');
        Route::get('announcements', [TenantAnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('bills', [TenantBillController::class, 'index'])->name('bills.index');
        Route::get('bills/{bill}', [TenantBillController::class, 'show'])->name('bills.show');
        Route::get('payments', [TenantPaymentController::class, 'index'])->name('payments.index');
        Route::get('receipts', [TenantReceiptController::class, 'index'])->name('receipts.index');
        Route::get('receipts/create', [TenantReceiptController::class, 'create'])->name('receipts.create');
        Route::post('receipts', [TenantReceiptController::class, 'store'])->name('receipts.store');
        Route::get('maintenance-requests', [TenantMaintenanceRequestController::class, 'index'])->name('maintenance-requests.index');
        Route::get('maintenance-requests/create', [TenantMaintenanceRequestController::class, 'create'])->name('maintenance-requests.create');
        Route::post('maintenance-requests', [TenantMaintenanceRequestController::class, 'store'])->name('maintenance-requests.store');
        Route::get('maintenance-requests/{maintenanceRequest}', [TenantMaintenanceRequestController::class, 'show'])->name('maintenance-requests.show');
        Route::resource('concerns', TenantConcernController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('visitors', [TenantVisitorController::class, 'index'])->name('visitors.index');
        Route::post('visitors', [TenantVisitorController::class, 'store'])->name('visitors.store');
        Route::patch('visitors/{visitor}/checkout', [TenantVisitorController::class, 'checkout'])->name('visitors.checkout');
    });
