<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\GitHubAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Ana Sayfa (Giriş yapılmışsa doğrudan Gösterge Paneline yönlendir) ──────
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// ─── Dashboard (Genel Gösterge Paneli: Push Geçmişi) ──────────────────────────
Route::get('/dashboard', function () {
    $query = \App\Models\PushLog::with('user')->latest();

    $logs = $query->paginate(20)->through(fn($log) => [
        'id' => $log->id,
        'user' => [
            'name' => $log->user?->name ?? 'Bilinmeyen Üye',
            'github_username' => $log->user?->github_username,
            'github_avatar' => $log->user?->github_avatar,
        ],
        'repo_full_name' => $log->repo_full_name,
        'repo_name' => $log->repo_name,
        'branch' => $log->branch,
        'original_filename' => $log->original_filename,
        'file_path' => $log->file_path,
        'commit_message' => $log->commit_message,
        'commit_sha' => substr($log->commit_sha ?? '', 0, 7),
        'diff_url' => $log->diff_url,
        'file_url' => $log->file_url,
        'status' => $log->status,
        'created_at' => $log->created_at->diffForHumans(),
        'date' => $log->created_at->format('d.m.Y H:i'),
    ]);

    $stats = [
        'total' => \App\Models\PushLog::count(),
        'success' => \App\Models\PushLog::where('status', 'success')->count(),
        'failed' => \App\Models\PushLog::where('status', 'failed')->count(),
    ];

    return Inertia::render('Dashboard', [
        'logs' => $logs,
        'stats' => $stats,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// ─── Auth Gerektiren Rotalar ─────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // GitHub OAuth
    Route::get('/auth/github', [GitHubAuthController::class, 'redirect'])->name('github.redirect');
    Route::get('/auth/github/callback', [GitHubAuthController::class, 'callback'])->name('github.callback');
    Route::delete('/auth/github', [GitHubAuthController::class, 'disconnect'])->name('github.disconnect');

    // Push Rotaları (Web üzerinden doğrudan push kaldırıldı, doğrudan loglara yönlendirilir)
    Route::get('/push', fn() => redirect()->route('push.logs'))->name('push.index');
    Route::post('/push', fn() => abort(403, 'Dosya push işlemleri yalnızca Masaüstü Uygulaması üzerinden yapılabilir.'))->name('push.store');
    Route::get('/push/branches', [PushController::class, 'getBranches'])->name('push.branches');

    // Push Logları (Gösterge Paneline yönlendirilir)
    Route::get('/push/logs', fn() => redirect()->route('dashboard'))->name('push.logs');
    Route::get('/push/logs/{pushLog}', [PushController::class, 'show'])->name('push.logs.show');

    // FCM Token
    Route::post('/fcm-token', [FcmTokenController::class, 'store'])->name('fcm.store');
    Route::delete('/fcm-token', [FcmTokenController::class, 'destroy'])->name('fcm.destroy');

    // Ekip Üyeleri API (Bildirim seçimi için)
    Route::get('/api/users', function () {
        return \App\Models\User::where('id', '!=', auth()->id())
            ->select('id', 'name', 'email')
            ->get();
    })->name('api.users');

    // Admin Rotaları
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::patch('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.role');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
    });
});

require __DIR__.'/auth.php';
