<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GitHubAuthController extends Controller
{
    /**
     * GitHub OAuth sayfasına yönlendir
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('github')
            ->scopes(['repo', 'user:email'])
            ->redirect();
    }

    /**
     * GitHub callback — token'ı kaydet veya güncelle
     */
    public function callback(): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Exception $e) {
            return redirect()->route('profile.edit')
                ->with('error', 'GitHub bağlantısı başarısız: ' . $e->getMessage());
        }

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'github_token' => $githubUser->token,
            'github_username' => $githubUser->getNickname(),
            'github_avatar' => $githubUser->getAvatar(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'GitHub hesabınız başarıyla bağlandı! (@' . $githubUser->getNickname() . ')');
    }

    /**
     * GitHub bağlantısını kaldır
     */
    public function disconnect(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'github_token' => null,
            'github_username' => null,
            'github_avatar' => null,
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'GitHub bağlantısı kaldırıldı.');
    }
}
