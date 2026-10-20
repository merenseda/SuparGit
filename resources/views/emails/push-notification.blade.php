<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Git Push Bildirimi</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 20px; }
  .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
  .header { background: linear-gradient(135deg, #1e1b4b, #4f46e5); color: white; padding: 32px; }
  .header h1 { margin: 0; font-size: 24px; }
  .header p { margin: 8px 0 0; opacity: 0.8; font-size: 14px; }
  .body { padding: 32px; }
  .info-grid { display: grid; gap: 16px; }
  .info-item { background: #f8fafc; border-radius: 8px; padding: 16px; border-left: 4px solid #4f46e5; }
  .info-item .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 4px; }
  .info-item .value { font-size: 15px; font-weight: 600; color: #111827; }
  .commit-msg { background: #f0fdf4; border-left-color: #22c55e; }
  .commit-msg .value { font-family: monospace; font-size: 14px; }
  .btn { display: inline-block; margin-top: 24px; padding: 12px 24px; background: #4f46e5; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; }
  .footer { padding: 20px 32px; background: #f8fafc; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>🚀 Yeni Git Push</h1>
    <p>{{ $pushLog->created_at->format('d.m.Y H:i') }}</p>
  </div>
  <div class="body">
    <div class="info-grid">
      <div class="info-item">
        <div class="label">👤 Kim Yaptı</div>
        <div class="value">{{ $pushLog->user->name }} (@{{ $pushLog->user->github_username }})</div>
      </div>
      <div class="info-item">
        <div class="label">📁 Repo & Branch</div>
        <div class="value">{{ $pushLog->repo_full_name }} / {{ $pushLog->branch }}</div>
      </div>
      <div class="info-item">
        <div class="label">📄 Dosya</div>
        <div class="value">{{ $pushLog->original_filename }}</div>
      </div>
      <div class="info-item commit-msg">
        <div class="label">💬 Commit Mesajı</div>
        <div class="value">{{ $pushLog->commit_message }}</div>
      </div>
    </div>
    @if($pushLog->diff_url)
    <a href="{{ $pushLog->diff_url }}" class="btn">🔍 Değişiklikleri GitHub'da Gör</a>
    @endif
  </div>
  <div class="footer">
    Bu bildirim GitSüpar tarafından otomatik gönderilmiştir.<br>
    Bildirim almak istemiyorsanız profil ayarlarınızdan devre dışı bırakabilirsiniz.
  </div>
</div>
</body>
</html>
