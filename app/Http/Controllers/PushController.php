<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotificationJob;
use App\Models\PushLog;
use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PushController extends Controller
{
    /**
     * Push sayfasını göster — repo listesi + form
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->github_token) {
            return Inertia::render('Push/Index', [
                'repos' => [],
                'githubConnected' => false,
            ]);
        }

        $github = new GitHubService($user);
        $repos = $github->getRepositories();

        // Sadece temel bilgileri frontend'e gönder
        $repoList = collect($repos)->map(fn($repo) => [
            'id' => $repo['id'],
            'name' => $repo['name'],
            'full_name' => $repo['full_name'],
            'private' => $repo['private'],
            'default_branch' => $repo['default_branch'],
            'updated_at' => $repo['updated_at'],
        ])->values();

        return Inertia::render('Push/Index', [
            'repos' => $repoList,
            'githubConnected' => true,
            'githubUsername' => $user->github_username,
        ]);
    }

    /**
     * Seçilen reponun branch listesini döndür (AJAX)
     */
    public function getBranches(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'owner' => 'required|string',
            'repo' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $github = new GitHubService($user);
        $branches = $github->getBranches($request->owner, $request->repo);

        return response()->json(['branches' => $branches]);
    }

    /**
     * Dosya push et
     */
    public function push(Request $request): RedirectResponse
    {
        $request->validate([
            'repo_full_name' => 'required|string|max:255',
            'branch' => 'required|string|max:100',
            'file_path' => 'required|string|max:500',
            'commit_message' => 'required|string|min:5|max:500',
            'file' => 'required|file|max:25600', // max 25 MB (GitHub limiti)
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => 'exists:users,id',
        ], [
            'repo_full_name.required' => 'Lütfen bir repo seçin.',
            'branch.required' => 'Lütfen bir branch seçin.',
            'file_path.required' => 'Dosya yolu gereklidir.',
            'commit_message.required' => 'Commit mesajı zorunludur.',
            'commit_message.min' => 'Commit mesajı en az 5 karakter olmalıdır.',
            'file.required' => 'Lütfen bir dosya seçin.',
            'file.max' => 'Dosya boyutu 25 MB\'ı geçemez (GitHub limiti).',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!$user->github_token) {
            return back()->with('error', 'GitHub hesabınızı bağlamadan push yapamazsınız.');
        }

        if (!$user->isDeveloper()) {
            return back()->with('error', 'Bu işlem için yetkiniz bulunmuyor.');
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
            return back()->with('error', 'Push başarısız: ' . ($result['error'] ?? 'Bilinmeyen hata'));
        }

        // Push log kaydet
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

        // Bildirim gönder (kuyrukta)
        $recipientIds = $request->input('recipient_ids', []);
        if (!empty($recipientIds)) {
            SendPushNotificationJob::dispatch($pushLog, $recipientIds);
        }

        return redirect()->route('push.logs')
            ->with('success', "✅ '{$originalFilename}' dosyası başarıyla push edildi! Commit: " . substr($result['commit_sha'], 0, 7));
    }

    /**
     * Push geçmişi
     */
    public function logs(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $query = PushLog::with('user')->latest();

        // Admin tüm logları görür, developer/viewer sadece kendini
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $logs = $query->paginate(20)->through(fn($log) => [
            'id' => $log->id,
            'user' => ['name' => $log->user->name, 'github_username' => $log->user->github_username],
            'repo_full_name' => $log->repo_full_name,
            'branch' => $log->branch,
            'original_filename' => $log->original_filename,
            'file_path' => $log->file_path,
            'commit_message' => $log->commit_message,
            'commit_sha' => substr($log->commit_sha ?? '', 0, 7),
            'diff_url' => $log->diff_url,
            'file_url' => $log->file_url,
            'status' => $log->status,
            'created_at' => $log->created_at->diffForHumans(),
        ]);

        return Inertia::render('Push/Logs', ['logs' => $logs]);
    }

    /**
     * Tek log detayı + diff
     */
    public function show(PushLog $pushLog): Response
    {
        /** @var User $user */
        $user = Auth::user();

        // Yetki kontrolü
        if (!$user->isAdmin() && $pushLog->user_id !== $user->id) {
            abort(403, 'Bu log kaydını görüntüleme yetkiniz yok.');
        }

        return Inertia::render('Push/Show', [
            'log' => [
                'id' => $pushLog->id,
                'user' => ['name' => $pushLog->user->name, 'github_username' => $pushLog->user->github_username],
                'repo_full_name' => $pushLog->repo_full_name,
                'branch' => $pushLog->branch,
                'original_filename' => $pushLog->original_filename,
                'file_path' => $pushLog->file_path,
                'commit_message' => $pushLog->commit_message,
                'commit_sha' => $pushLog->commit_sha,
                'diff_url' => $pushLog->diff_url,
                'file_url' => $pushLog->file_url,
                'status' => $pushLog->status,
                'error_message' => $pushLog->error_message,
                'created_at' => $pushLog->created_at->format('d.m.Y H:i:s'),
            ],
        ]);
    }
}
