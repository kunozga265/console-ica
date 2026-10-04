<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Web\AnnotationController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\MemberAuthController;
use App\Http\Controllers\Web\UI\AboutController as UIAboutController;
use App\Http\Controllers\Web\UI\AccountDeletionController as UIAccountDeletionController;
use App\Http\Controllers\Web\UI\AttendanceSheetController as UIAttendanceSheetController;
use App\Http\Controllers\Web\UI\CellController as UICellController;
use App\Http\Controllers\Web\UI\CheckInController as UICheckInController;
use App\Http\Controllers\Web\UI\DashboardController as UIDashboardController;
use App\Http\Controllers\Web\UI\EngagementController as UIEngagementController;
use App\Http\Controllers\Web\UI\EventController as UIEventController;
use App\Http\Controllers\Web\UI\GiveController as UIGiveController;
use App\Http\Controllers\Web\UI\MemberController as UIMemberController;
use App\Http\Controllers\Web\UI\PrayerController as UIPrayerController;
use App\Http\Controllers\Web\UI\ProfileController as UIProfileController;
use App\Http\Controllers\Web\UI\ResourceController as UIResourceController;
use App\Http\Controllers\Web\UI\SermonController as UISermonController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirect'])->name('auth.provider');
Route::get('/auth/{provider}/callback', [AuthController::class, 'callback']);

// The site used to live under /ui; keep old links and printed QR codes working.
Route::get('/ui/{path?}', fn (?string $path = null) => redirect('/' . $path, 301))->where('path', '.*');

$auth = ['auth:sanctum', config('jetstream.auth_session'), 'verified'];

/*
|--------------------------------------------------------------------------
| The site (route names keep their historical "ui." prefix)
|--------------------------------------------------------------------------
*/
Route::name('ui.')->group(function () use ($auth) {
    Route::get('/', UIDashboardController::class)->name('dashboard');
    Route::get('/sermons', [UISermonController::class, 'index'])->name('sermons.index');
    Route::get('/sermons/{sermon}', [UISermonController::class, 'show'])->whereNumber('sermon')->name('sermons.show');
    Route::get('/give', UIGiveController::class)->name('give');
    Route::get('/events', [UIEventController::class, 'index'])->name('events');
    Route::get('/prayer', [UIPrayerController::class, 'index'])->name('prayer');
    Route::get('/prayer/{prayer}', [UIPrayerController::class, 'show'])->whereNumber('prayer')->name('prayer.show');
    Route::get('/resources', [UIResourceController::class, 'index'])->name('resources');
    Route::get('/about', UIAboutController::class)->name('about');

    // App-store account deletion: enter email → emailed signed link → confirm → deleted.
    Route::get('/deactivate-account', [UIAccountDeletionController::class, 'show'])->name('deactivate');
    Route::post('/deactivate-account', [UIAccountDeletionController::class, 'request'])->middleware('throttle:5,1')->name('deactivate.request');
    Route::get('/deactivate-account/confirm/{user}/{hash}', [UIAccountDeletionController::class, 'confirm'])->middleware('signed')->name('deactivate.confirm');
    Route::post('/deactivate-account/confirm/{user}/{hash}', [UIAccountDeletionController::class, 'destroy'])->middleware('signed')->name('deactivate.destroy');

    // Member annotations on the sermon reader.
    Route::middleware([...$auth, 'has.member'])->group(function () {
        Route::post('/sermons/{sermon}/highlights', [AnnotationController::class, 'storeHighlight'])->name('highlights.store');
        Route::delete('/highlights/{highlight}', [AnnotationController::class, 'destroyHighlight'])->name('highlights.destroy');
        Route::post('/sermons/{sermon}/notes', [AnnotationController::class, 'storeNote'])->name('notes.store');
        Route::delete('/notes/{note}', [AnnotationController::class, 'destroyNote'])->name('notes.destroy');
        Route::post('/sermons/{sermon}/bookmarks', [AnnotationController::class, 'storeBookmark'])->name('bookmarks.store');
        Route::delete('/bookmarks/{bookmark}', [AnnotationController::class, 'destroyBookmark'])->name('bookmarks.destroy');
    });

    // Signed-in users (member profile not required).
    Route::middleware($auth)->group(function () {
        Route::post('/sermons/{sermon}/saves', [UIEngagementController::class, 'toggleSermonSave'])->name('sermons.saves');
        Route::post('/events/{event}/response', [UIEngagementController::class, 'respondToEvent'])->name('events.respond');
        Route::post('/prayer/{prayer}/praying', [UIEngagementController::class, 'togglePraying'])->name('prayer.praying');
        Route::post('/notifications/read', [UIEngagementController::class, 'readNotifications'])->name('notifications.read');

        // Cells: the member's cell dashboard, finding/joining a cell, and
        // management actions (authorised per cell in the controller).
        Route::get('/cells', [UICellController::class, 'index'])->name('cells');
        Route::get('/cells/{cell:code}', [UICellController::class, 'show'])->name('cells.show');
        Route::post('/cells/{cell:code}/join', [UICellController::class, 'requestToJoin'])->name('cells.join');
        Route::post('/cells/{cell:code}/requests/{joinRequest}', [UICellController::class, 'decideJoinRequest'])->name('cells.requests.decide');
        Route::post('/cells/{cell:code}/members', [UICellController::class, 'attachMember'])->name('cells.members.attach');
        Route::delete('/cells/{cell:code}/members/{member}', [UICellController::class, 'detachMember'])->name('cells.members.detach');
        Route::post('/cells/{cell:code}/members/{member}/transfer', [UICellController::class, 'transferMember'])->name('cells.members.transfer');
        Route::post('/cells/{cell:code}/meetings', [UICellController::class, 'storeMeeting'])->name('cells.meetings.store');
        Route::put('/cells/{cell:code}/meetings/{meeting:code}', [UICellController::class, 'updateMeeting'])->name('cells.meetings.update');
        Route::post('/cells/{cell:code}/transactions', [UICellController::class, 'storeTransaction'])->name('cells.transactions.store');

        Route::get('/profile', UIProfileController::class)->name('profile');

        // QR self check-in to a service register.
        // Signed-out scanners log in / sign up first; anyone without a member
        // profile is onboarded (has.member) and then returned here.
        Route::middleware('has.member')->group(function () {
            Route::get('/check-in/{register}', [UICheckInController::class, 'show'])->name('check-in');
            Route::post('/check-in/{register}', [UICheckInController::class, 'store'])->name('check-in.store');
        });

        // Front-of-house tools for admins: service registers and the people directory.
        Route::middleware(['role:admin,super'])->group(function () {
            Route::get('/attendance', [UIAttendanceSheetController::class, 'index'])->name('attendance');
            Route::post('/attendance', [UIAttendanceSheetController::class, 'store'])->name('attendance.store');
            Route::get('/attendance/{register}', [UIAttendanceSheetController::class, 'show'])->name('attendance.show');
            Route::put('/attendance/{register}', [UIAttendanceSheetController::class, 'update'])->name('attendance.update');
            Route::delete('/attendance/{register}', [UIAttendanceSheetController::class, 'destroy'])->name('attendance.destroy');
            Route::post('/attendance/{register}/toggle', [UIAttendanceSheetController::class, 'toggle'])->name('attendance.toggle');

            Route::post('/members', [UIMemberController::class, 'store'])->name('members.store');
            Route::put('/members/{member}', [UIMemberController::class, 'update'])->name('members.update');
            Route::post('/members/{member}/register', [UIMemberController::class, 'register'])->name('members.register');
        });
    });
});

// Member-link confirmation: any signed-in user, since its purpose is linking
// a user who has no member profile yet.
Route::middleware($auth)->prefix('member-link')->name('member-link.')->group(function () {
    Route::get('/complete', [MemberAuthController::class, 'complete'])->name('complete');
    Route::post('/complete', [MemberAuthController::class, 'storeComplete'])->name('complete.store');
    Route::get('/confirm', [MemberAuthController::class, 'confirmLink'])->name('confirm');
    Route::post('/link-existing', [MemberAuthController::class, 'linkExisting'])->name('link-existing');
    Route::post('/create-new', [MemberAuthController::class, 'createNew'])->name('create-new');
});

/*
|--------------------------------------------------------------------------
| Admin (/admin) — admins only
|--------------------------------------------------------------------------
*/
Route::middleware([...$auth, 'role:admin,super'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/sermons', [Admin\SermonController::class, 'index'])->name('sermons.index');
    Route::get('/sermons/create', [Admin\SermonController::class, 'create'])->name('sermons.create');
    Route::post('/sermons', [Admin\SermonController::class, 'store'])->name('sermons.store');
    Route::get('/sermons/{sermon}/edit', [Admin\SermonController::class, 'edit'])->withTrashed()->name('sermons.edit');
    Route::put('/sermons/{sermon}', [Admin\SermonController::class, 'update'])->withTrashed()->name('sermons.update');
    Route::delete('/sermons/{sermon}', [Admin\SermonController::class, 'destroy'])->name('sermons.destroy');
    Route::post('/sermons/{sermon}/restore', [Admin\SermonController::class, 'restore'])->withTrashed()->name('sermons.restore');

    Route::get('/series', [Admin\SeriesController::class, 'index'])->name('series.index');
    Route::post('/series', [Admin\SeriesController::class, 'store'])->name('series.store');
    Route::put('/series/{series}', [Admin\SeriesController::class, 'update'])->name('series.update');
    Route::delete('/series/{series}', [Admin\SeriesController::class, 'destroy'])->name('series.destroy');

    Route::get('/ministers', [Admin\MinisterController::class, 'index'])->name('ministers.index');
    Route::post('/ministers', [Admin\MinisterController::class, 'store'])->name('ministers.store');
    Route::post('/ministers/{author}', [Admin\MinisterController::class, 'update'])->name('ministers.update'); // POST: multipart upload
    Route::delete('/ministers/{author}', [Admin\MinisterController::class, 'destroy'])->name('ministers.destroy');

    Route::get('/resources', [Admin\ResourceController::class, 'index'])->name('resources.index');
    Route::post('/resources', [Admin\ResourceController::class, 'store'])->name('resources.store');
    Route::put('/resources/{download}', [Admin\ResourceController::class, 'update'])->name('resources.update');
    Route::delete('/resources/{download}', [Admin\ResourceController::class, 'destroy'])->name('resources.destroy');

    Route::get('/members', [Admin\MemberController::class, 'index'])->name('members.index');
    Route::delete('/members/{member}', [Admin\MemberController::class, 'destroy'])->name('members.destroy');
    Route::get('/attendance', Admin\AttendanceController::class)->name('attendance');

    // Registers: CRUD + marking attendance (same rules as the site's attendance sheets).
    Route::get('/registers', [Admin\RegisterController::class, 'index'])->name('registers.index');
    Route::post('/registers', [Admin\RegisterController::class, 'store'])->name('registers.store');
    Route::get('/registers/{register}', [Admin\RegisterController::class, 'show'])->name('registers.show');
    Route::put('/registers/{register}', [Admin\RegisterController::class, 'update'])->name('registers.update');
    Route::delete('/registers/{register}', [Admin\RegisterController::class, 'destroy'])->name('registers.destroy');
    Route::post('/registers/{register}/toggle', [Admin\RegisterController::class, 'toggle'])->name('registers.toggle');

    Route::get('/ministries', [Admin\MinistryController::class, 'index'])->name('ministries.index');
    Route::post('/ministries', [Admin\MinistryController::class, 'store'])->name('ministries.store');
    Route::put('/ministries/{ministry}', [Admin\MinistryController::class, 'update'])->name('ministries.update');
    Route::delete('/ministries/{ministry}', [Admin\MinistryController::class, 'destroy'])->name('ministries.destroy');

    Route::get('/giving', [Admin\GivingController::class, 'index'])->name('giving.index');
    Route::post('/giving', [Admin\GivingController::class, 'store'])->name('giving.store');
    Route::put('/giving/{option}', [Admin\GivingController::class, 'update'])->name('giving.update');
    Route::delete('/giving/{option}', [Admin\GivingController::class, 'destroy'])->name('giving.destroy');

    Route::get('/cells', [Admin\CellController::class, 'index'])->name('cells.index');
    Route::post('/cells', [Admin\CellController::class, 'store'])->name('cells.store');
    Route::put('/cells/{cell}', [Admin\CellController::class, 'update'])->name('cells.update');
    Route::delete('/cells/{cell}', [Admin\CellController::class, 'destroy'])->name('cells.destroy');
    Route::post('/cells/{cell}/verify', [Admin\CellController::class, 'verify'])->name('cells.verify');

    Route::get('/events', [Admin\EventController::class, 'index'])->name('events.index');
    Route::post('/events', [Admin\EventController::class, 'store'])->name('events.store');
    Route::post('/events/{event}', [Admin\EventController::class, 'update'])->name('events.update'); // POST: multipart upload
    Route::delete('/events/{event}', [Admin\EventController::class, 'destroy'])->name('events.destroy');

    Route::get('/prayer', [Admin\PrayerController::class, 'index'])->name('prayer.index');
    Route::post('/prayer', [Admin\PrayerController::class, 'store'])->name('prayer.store');
    Route::put('/prayer/{prayer}', [Admin\PrayerController::class, 'update'])->name('prayer.update');
    Route::delete('/prayer/{prayer}', [Admin\PrayerController::class, 'destroy'])->name('prayer.destroy');

    Route::get('/announcements', [Admin\AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements', [Admin\AnnouncementController::class, 'update'])->name('announcements.update');

    Route::get('/favorites', Admin\FavoritesController::class)->name('favorites');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/role', [Admin\UserController::class, 'toggleAdmin'])->name('users.role');

    // Devotionals has no data model yet — the nav item is a placeholder.
});
