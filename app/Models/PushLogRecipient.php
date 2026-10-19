<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushLogRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'push_log_id',
        'user_id',
        'email_sent',
        'push_sent',
        'notified_at',
    ];

    protected $casts = [
        'email_sent' => 'boolean',
        'push_sent' => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function pushLog()
    {
        return $this->belongsTo(PushLog::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
