<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'allowed_branches',
        'github_token',
        'github_username',
        'github_avatar',
        'fcm_token',
        'notifications_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'github_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notifications_enabled' => 'boolean',
            'github_token' => 'encrypted',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDeveloper(): bool
    {
        return in_array($this->role, ['admin', 'developer']);
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function canPushToBranch(string $branch): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $allowedStr = trim($this->allowed_branches ?? '');
        if ($allowedStr === '' || $allowedStr === '*') {
            return true;
        }

        $patterns = array_map('trim', explode(',', $allowedStr));
        foreach ($patterns as $pattern) {
            if ($pattern === '' || $pattern === '*') {
                return true;
            }
            if (fnmatch($pattern, $branch) || strcasecmp($pattern, $branch) === 0) {
                return true;
            }
        }

        return false;
    }

    public function pushLogs()
    {
        return $this->hasMany(PushLog::class);
    }

    public function receivedNotifications()
    {
        return $this->hasMany(PushLogRecipient::class);
    }
}
