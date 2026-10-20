# GitSüpar Tek Tıkla Kurulum Betiği (SmartScreen Korumasını Otomatik Açar)
$ErrorActionPreference = 'Stop'
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   GitSüpar Kurulum Sihirbazı İndiriliyor..." -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan

$setupUrl = "https://git.susluparantez.com.tr/downloads/GitSupar-Setup.exe"
$tempPath = Join-Path $env:TEMP "GitSupar-Setup.exe"

Write-Host "-> İndiriliyor: $setupUrl" -ForegroundColor Gray
Invoke-WebRequest -Uri $setupUrl -OutFile $tempPath -UseBasicParsing

Write-Host "-> Windows güvenlik etiketi temizleniyor (Unblock)..." -ForegroundColor Gray
Unblock-File -Path $tempPath -ErrorAction SilentlyContinue

Write-Host "-> Kurulum başlatılıyor..." -ForegroundColor Green
Start-Process -FilePath $tempPath

Write-Host "✅ Kurulum sihirbazı açıldı. Lütfen adımları takip edin." -ForegroundColor Cyan
