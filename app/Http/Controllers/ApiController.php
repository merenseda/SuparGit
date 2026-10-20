<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotificationJob;
use App\Models\PushLog;
use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    /**
     * Masaüstü ve Mobil için Kullanıcı Girişi
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'E-posta veya şifre hatalı.',
            ], 401);
        }

        $token = $user->createToken('gitpush-app-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'github_connected' => !empty($user->github_token),
                'github_username' => $user->github_username,
                'github_avatar' => $user->github_avatar,
            ],
        ]);
    }

    /**
     * Kullanıcı Profil Bilgisi
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'allowed_branches' => $user->isAdmin() ? '*' : ($user->allowed_branches ?: '*'),
            'notifications_enabled' => (bool) ($user->notifications_enabled ?? true),
            'github_connected' => !empty($user->github_token),
            'github_username' => $user->github_username,
            'github_avatar' => $user->github_avatar,
        ]);
    }

    /**
     * Kullanıcı Profilini Güncelle
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'notifications_enabled' => 'nullable|boolean',
            'github_token' => 'nullable|string',
        ]);

        if (!empty($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (!empty($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (!empty($validated['password'])) {
            $user->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }
        if (isset($validated['notifications_enabled'])) {
            $user->notifications_enabled = (bool) $validated['notifications_enabled'];
        }
        if (array_key_exists('github_token', $validated) && !empty($validated['github_token'])) {
            $user->github_token = $validated['github_token'];
            try {
                $ghRes = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $validated['github_token'],
                    'Accept' => 'application/vnd.github+json',
                ])->get('https://api.github.com/user');

                if ($ghRes->ok()) {
                    $ghData = $ghRes->json();
                    $user->github_username = $ghData['login'] ?? null;
                    $user->github_avatar = $ghData['avatar_url'] ?? null;
                }
            } catch (\Exception $e) {
                // Sessizce devam et
            }
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil bilgileri başarıyla güncellendi.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'notifications_enabled' => (bool) $user->notifications_enabled,
                'github_connected' => !empty($user->github_token),
                'github_username' => $user->github_username,
                'github_avatar' => $user->github_avatar,
            ],
        ]);
    }

    /**
     * GitHub Repoları Listesi
     */
    public function repositories(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->github_token) {
            return response()->json([
                'success' => false,
                'message' => 'GitHub hesabınız henüz bağlı değil. Lütfen Personal Access Token (PAT) girin.',
                'repos' => [],
            ], 400);
        }

        $github = new GitHubService($user);
        $result = $github->fetchRepositories();

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'repos' => [],
            ], 400);
        }

        $repoList = collect($result['repos'])->map(fn($r) => [
            'id' => $r['id'],
            'name' => $r['name'],
            'full_name' => $r['full_name'],
            'private' => $r['private'],
            'default_branch' => $r['default_branch'],
        ])->values();

        return response()->json([
            'success' => true,
            'repos' => $repoList,
        ]);
    }

    /**
     * Repo Branch Listesi
     */
    public function branches(Request $request): JsonResponse
    {
        $request->validate([
            'owner' => 'required|string',
            'repo' => 'required|string',
        ]);

        $user = $request->user();
        $github = new GitHubService($user);
        $branches = $github->getBranches($request->owner, $request->repo);

        if (!$user->isAdmin()) {
            $branches = array_values(array_filter($branches, fn($b) => $user->canPushToBranch($b)));
        }

        return response()->json([
            'success' => true,
            'branches' => $branches,
            'allowed_branches' => $user->isAdmin() ? '*' : ($user->allowed_branches ?: '*'),
        ]);
    }

    /**
     * Ekip Üyeleri Listesi (Bildirim alıcıları)
     */
    public function teamMembers(Request $request): JsonResponse
    {
        $members = User::where('id', '!=', $request->user()->id)
            ->select('id', 'name', 'email', 'role', 'github_username')
            ->get();

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    /**
     * Dosya Push Et
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'repo_full_name' => 'required|string',
            'branch' => 'required|string',
            'file_path' => 'required|string',
            'commit_message' => 'required|string|min:3',
            'file' => 'required|file|max:25600',
            'recipient_ids' => 'nullable|array',
        ]);

        $user = $request->user();

        // Branch izin kontrolü (Admin hariç)
        if (!$user->canPushToBranch($request->branch)) {
            return response()->json([
                'success' => false,
                'message' => "Bu branch'e ('{$request->branch}') dosya yükleme izniniz bulunmamaktadır. İzin verilen branch'ler: " . ($user->allowed_branches ?: '*'),
            ], 403);
        }

        if (!$user->github_token) {
            return response()->json(['success' => false, 'message' => 'GitHub hesabınız bağlı değil.'], 400);
        }

        $file = $request->file('file');
        $fileContent = file_get_contents($file->getRealPath());
        $originalFilename = $file->getClientOriginalName();

        [$owner, $repo] = explode('/', $request->repo_full_name);

        $github = new GitHubService($user);
        $result = $github->pushFile(
            owner: $owner,
            repo: $repo,
            branch: $request->branch,
            filePath: $request->file_path,
            fileContent: $fileContent,
            commitMessage: $request->commit_message,
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Push başarısız oldu.',
            ], 422);
        }

        $pushLog = PushLog::create([
            'user_id' => $user->id,
            'repo_name' => $repo,
            'repo_full_name' => $request->repo_full_name,
            'branch' => $request->branch,
            'file_path' => $request->file_path,
            'original_filename' => $originalFilename,
            'commit_message' => $request->commit_message,
            'commit_sha' => $result['commit_sha'],
            'diff_url' => $result['diff_url'],
            'file_url' => $result['file_url'],
            'status' => 'success',
        ]);

        $recipientIds = $request->input('recipient_ids', []);
        if (!empty($recipientIds)) {
            SendPushNotificationJob::dispatch($pushLog, $recipientIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dosya başarıyla push edildi!',
            'log' => $pushLog,
        ]);
    }

    /**
     * Push Kayıtları (Mobil feed & geçmiş için)
     */
    public function logs(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PushLog::with('user')->latest();
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $logs = $query->paginate(20);

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Mobil Cihaz FCM Token Kaydı
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['success' => true, 'message' => 'FCM token güncellendi.']);
    }
}
