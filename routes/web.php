<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('home');

Route::get('/test-queue-worker', function () {

    Artisan::call('queue:work database --queue=notifications --stop-when-empty');

    return [
        'message' => 'Notifications queue processed until empty',
        'output' => Artisan::output(),
    ];
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user?->hasAnyRole(['super_admin', 'admin'])) {
        return redirect()->route('admin.dashboard.index');
    }

    abort(403);
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
