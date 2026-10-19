<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'repo_name',
        'repo_full_name',
        'branch',
        'file_path',
        'original_filename',
        'commit_message',
        'commit_sha',
        'diff_url',
        'file_url',
        'status',
        'error_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipients()
    {
        return $this->hasMany(PushLogRecipient::class);
    }
}
