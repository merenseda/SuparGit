<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    private string $baseUrl = 'https://api.github.com';
    private ?string $token;
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = $user->github_token;
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    /**
     * Kullanıcının erişebildiği repoları durum bilgisi ile birlikte listele
     */
    public function fetchRepositories(int $page = 1, int $perPage = 50): array
    {
        if (!$this->token) {
            return ['success' => false, 'error' => 'GitHub bağlantısı veya tokenı bulunamadı.', 'repos' => []];
        }

        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/user/repos", [
                'sort' => 'updated',
                'per_page' => $perPage,
                'page' => $page,
                'affiliation' => 'owner,collaborator,organization_member',
            ]);

        if ($response->failed()) {
            $msg = $response->json()['message'] ?? 'GitHub ile bağlantı kurulamadı.';
            if ($response->status() === 401) {
                $msg = 'GitHub tokenının süresi dolmuş veya geçersiz. Lütfen tokenınızı yenileyin.';
            }
            Log::error('GitHub repos fetch failed', ['status' => $response->status(), 'msg' => $msg]);
            return ['success' => false, 'error' => $msg, 'repos' => []];
        }

        return ['success' => true, 'repos' => $response->json()];
    }

    /**
     * Kullanıcının erişebildiği repoları listele
     */
    public function getRepositories(int $page = 1, int $perPage = 50): array
    {
        $res = $this->fetchRepositories($page, $perPage);
        return $res['success'] ? $res['repos'] : [];
    }

    /**
     * Repo branch listesi
     */
    public function getBranches(string $owner, string $repo): array
    {
        if (!$this->token) return [];

        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/branches");

        if ($response->failed()) {
            return [];
        }

        return array_column($response->json(), 'name');
    }

    /**
     * Dosya push et (create veya update)
     */
    public function pushFile(
        string $owner,
        string $repo,
        string $branch,
        string $filePath,
        string $fileContent,
        string $commitMessage
    ): array {
        if (!$this->token) {
            return ['success' => false, 'error' => 'GitHub bağlantısı bulunamadı'];
        }

        $sha = $this->getFileSha($owner, $repo, $branch, $filePath);

        $payload = [
            'message' => $commitMessage,
            'content' => base64_encode($fileContent),
            'branch' => $branch,
        ];

        if ($sha) {
            $payload['sha'] = $sha;
        }

        $response = Http::withHeaders($this->headers())
            ->put("{$this->baseUrl}/repos/{$owner}/{$repo}/contents/" . ltrim($filePath, '/'), $payload);

        if ($response->failed()) {
            Log::error('GitHub push failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Push işlemi başarısız oldu.',
            ];
        }

        $data = $response->json();
        return [
            'success' => true,
            'commit_sha' => $data['commit']['sha'] ?? null,
            'diff_url' => $data['commit']['html_url'] ?? null,
            'file_url' => $data['content']['html_url'] ?? null,
        ];
    }

    private function getFileSha(string $owner, string $repo, string $branch, string $filePath): ?string
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/contents/" . ltrim($filePath, '/'), [
                'ref' => $branch,
            ]);

        if ($response->ok()) {
            return $response->json()['sha'] ?? null;
        }

        return null;
    }
}
