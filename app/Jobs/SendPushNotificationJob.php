<?php

namespace App\Jobs;

use App\Models\PushLog;
use App\Models\PushLogRecipient;
use App\Models\User;
use App\Mail\PushNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PushLog $pushLog,
        public array $recipientIds = []
    ) {}

    public function handle(): void
    {
        $recipients = User::whereIn('id', $this->recipientIds)
            ->where('notifications_enabled', true)
            ->get();

        foreach ($recipients as $recipient) {
            $logRecord = PushLogRecipient::firstOrCreate([
                'push_log_id' => $this->pushLog->id,
                'user_id' => $recipient->id,
            ]);

            // E-posta gönder
            try {
                Mail::to($recipient->email)->send(new PushNotificationMail($this->pushLog));
                $logRecord->update(['email_sent' => true]);
            } catch (\Throwable $e) {
                Log::error('Mail gönderilemedi', ['user' => $recipient->email, 'error' => $e->getMessage()]);
            }

            // FCM Push Notification gönder
            if ($recipient->fcm_token) {
                try {
                    $this->sendFcmNotification($recipient, $this->pushLog);
                    $logRecord->update(['push_sent' => true]);
                } catch (\Throwable $e) {
                    Log::error('FCM gönderilemedi', ['user' => $recipient->email, 'error' => $e->getMessage()]);
                }
            }

            $logRecord->update(['notified_at' => now()]);
        }

        // Webhook'ları tetikle
        $this->triggerWebhooks();
    }

    private function sendFcmNotification(User $recipient, PushLog $pushLog): void
    {
        $fcmServerKey = config('services.fcm.server_key');
        if (!$fcmServerKey || $fcmServerKey === 'your_fcm_server_key') return;

        Http::withHeaders([
            'Authorization' => 'key=' . $fcmServerKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $recipient->fcm_token,
            'notification' => [
                'title' => '🚀 Yeni Push: ' . $pushLog->repo_name,
                'body' => $pushLog->user->name . ': ' . $pushLog->commit_message,
                'icon' => '/icon-192x192.png',
                'click_action' => url('/push/logs/' . $pushLog->id),
            ],
            'data' => [
                'push_log_id' => (string) $pushLog->id,
                'url' => url('/push/logs/' . $pushLog->id),
            ],
        ]);
    }

    private function triggerWebhooks(): void
    {
        $webhooks = \App\Models\WebhookEndpoint::where('is_active', true)->get();

        foreach ($webhooks as $webhook) {
            try {
                $payload = [
                    'event' => 'push',
                    'pusher' => [
                        'name' => $this->pushLog->user->name,
                        'email' => $this->pushLog->user->email,
                        'github_username' => $this->pushLog->user->github_username,
                    ],
                    'repository' => [
                        'full_name' => $this->pushLog->repo_full_name,
                        'name' => $this->pushLog->repo_name,
                        'branch' => $this->pushLog->branch,
                    ],
                    'commit' => [
                        'sha' => $this->pushLog->commit_sha,
                        'message' => $this->pushLog->commit_message,
                        'file' => $this->pushLog->file_path,
                        'diff_url' => $this->pushLog->diff_url,
                    ],
                    'pushed_at' => $this->pushLog->created_at->toIso8601String(),
                ];

                $request = Http::timeout(10);

                if ($webhook->secret) {
                    $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret);
                    $request = $request->withHeaders([
                        'X-GitPush-Signature' => 'sha256=' . $signature,
                    ]);
                }

                $request->post($webhook->url, $payload);
            } catch (\Throwable $e) {
                Log::error('Webhook tetiklenemedi', ['url' => $webhook->url, 'error' => $e->getMessage()]);
            }
        }
    }
}
