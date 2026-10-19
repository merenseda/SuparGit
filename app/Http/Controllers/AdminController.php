<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()?->isAdmin()) {
                abort(403, 'Bu sayfaya erişim yetkiniz yok.');
            }
            return $next($request);
        });
    }

    /**
     * Admin paneli — kullanıcı listesi
     */
    public function users(): Response
    {
        $users = User::latest()->get()->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'github_username' => $user->github_username,
            'github_avatar' => $user->github_avatar,
            'notifications_enabled' => $user->notifications_enabled,
            'created_at' => $user->created_at->format('d.m.Y'),
        ]);

        return Inertia::render('Admin/Users', ['users' => $users]);
    }

    /**
     * Kullanıcı rolünü güncelle
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', Rule::in(['admin', 'developer', 'viewer'])],
        ]);

        // Kendisinin rolünü değiştiremesin
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Kendi rolünüzü değiştiremezsiniz.');
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', $user->name . ' kullanıcısının rolü "' . $request->role . '" olarak güncellendi.');
    }

    /**
     * Kullanıcıyı sil
     */
    public function deleteUser(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Kendi hesabınızı silemezsiniz.');
        }

        $user->delete();

        return back()->with('success', $user->name . ' kullanıcısı silindi.');
    }

    /**
     * Dashboard istatistikleri
     */
    public function dashboard(): Response
    {
        $stats = [
            'total_users' => User::count(),
            'total_developers' => User::where('role', 'developer')->count(),
            'total_pushes' => \App\Models\PushLog::count(),
            'pushes_today' => \App\Models\PushLog::whereDate('created_at', today())->count(),
            'recent_pushes' => \App\Models\PushLog::with('user')->latest()->limit(10)->get()->map(fn($log) => [
                'id' => $log->id,
                'user_name' => $log->user->name,
                'repo_name' => $log->repo_name,
                'branch' => $log->branch,
                'commit_message' => $log->commit_message,
                'status' => $log->status,
                'created_at' => $log->created_at->diffForHumans(),
            ]),
        ];

        return Inertia::render('Admin/Dashboard', ['stats' => $stats]);
    }
}
