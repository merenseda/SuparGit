<?php

namespace App\Http\Controllers;

use App\Models\PushLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
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
            'allowed_branches' => $user->allowed_branches ?: '*',
            'notifications_enabled' => (bool)$user->notifications_enabled,
            'created_at' => $user->created_at?->format('d.m.Y') ?? '-',
        ]);

        return Inertia::render('Admin/Users', ['users' => $users]);
    }

    /**
     * Kullanıcı rolünü ve izin verilen branch'leri güncelle
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['sometimes', 'required', Rule::in(['admin', 'developer', 'viewer'])],
            'allowed_branches' => ['nullable', 'string', 'max:500'],
        ]);

        // Kendisinin admin rolünü değiştiremesin
        if (isset($validated['role']) && $user->id === Auth::id() && $validated['role'] !== 'admin') {
            return back()->with('error', 'Kendi admin rolünüzü değiştiremezsiniz.');
        }

        if (array_key_exists('allowed_branches', $validated)) {
            $user->allowed_branches = $validated['allowed_branches'] ?: '*';
        }

        if (isset($validated['role'])) {
            $user->role = $validated['role'];
        }

        $user->save();

        return back()->with('success', $user->name . ' kullanıcısının yetkileri ve branch izinleri güncellendi.');
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
            'total_pushes' => PushLog::count(),
            'pushes_today' => PushLog::whereDate('created_at', today())->count(),
            'recent_pushes' => PushLog::with('user')->latest()->limit(10)->get()->map(fn($log) => [
                'id' => $log->id,
                'user_name' => $log->user?->name ?? 'Bilinmeyen Üye',
                'repo_name' => $log->repo_name,
                'branch' => $log->branch,
                'commit_message' => $log->commit_message,
                'status' => $log->status,
                'created_at' => $log->created_at?->diffForHumans() ?? '-',
            ]),
        ];

        return Inertia::render('Admin/Dashboard', ['stats' => $stats]);
    }
}
