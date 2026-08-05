<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('home');

Route::get('/fix-storage', function () {

    $publicStorage = public_path('storage');
    $realStorage = storage_path('app/public');

    // Function to delete a directory recursively
    function deleteDirectory($dir) {
        if (!file_exists($dir)) return;
        if (!is_dir($dir)) return unlink($dir);

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    // 1. If symlink/folder missing → create new
    if (!file_exists($publicStorage)) {
        Artisan::call('storage:link');
        return "✔ Storage link created (was missing).";
    }

    // 2. If exists BUT not a symlink → delete directory then create link
    if (!is_link($publicStorage)) {
        deleteDirectory($publicStorage);
        Artisan::call('storage:link');
        return "✔ Storage folder was not symlink → fixed by recreating!";
    }

    // 3. If symlink exists but points to wrong location → fix it
    if (realpath($publicStorage) !== realpath($realStorage)) {
        unlink($publicStorage);
        Artisan::call('storage:link');
        return "✔ Storage symlink incorrect → recreated successfully!";
    }

    return "✔ Storage link already correct.";
});

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
