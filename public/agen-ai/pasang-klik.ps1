<#
    WBL AI Monitor - pasang-klik.ps1
    ================================
    Dipanggil oleh KLIK-PASANG.bat (yang sudah menaikkan hak admin).
    Membaca ENDPOINT dari pengaturan.txt. ENROLLKEY: jika belum diisi di
    pengaturan.txt, pemasang MENANYAKANNYA saat dijalankan (ditempel dari
    dashboard MIC), lalu pasang.ps1 menyimpannya ke config per-perangkat.
    Dengan begitu kunci tak perlu ditaruh di berkas yang ada di server.
#>
$ErrorActionPreference = 'Stop'
$dir = Split-Path -Parent $MyInvocation.MyCommand.Path
$cfg = Join-Path $dir 'pengaturan.txt'

# Baca pengaturan (opsional). Baris '#' dan kosong diabaikan.
$map = @{}
if (Test-Path -LiteralPath $cfg) {
    foreach ($line in Get-Content -LiteralPath $cfg -Encoding UTF8) {
        if ($line -match '^\s*#') { continue }
        if ($line -match '=') {
            $parts = $line -split '=', 2
            $map[$parts[0].Trim().ToUpper()] = $parts[1].Trim()
        }
    }
}

$endpoint = $map['ENDPOINT']
$key      = $map['ENROLLKEY']
$label    = $map['LABEL']

Write-Host '=== Pemasangan WBL AI Monitor ===' -ForegroundColor Cyan

# ENDPOINT: biasanya sudah terisi; minta hanya bila kosong/placeholder.
if ([string]::IsNullOrWhiteSpace($endpoint) -or $endpoint -like '*GANTI*') {
    $endpoint = Read-Host 'Masukkan ENDPOINT enroll MIC (mis. https://mic.wbl-bsb.com/api/ai-monitor/enroll)'
}

# ENROLLKEY: jika belum diisi, TANYAKAN sekarang (tempel dari dashboard MIC).
while ([string]::IsNullOrWhiteSpace($key) -or $key -like '*GANTI*') {
    $key = Read-Host 'Masukkan Enroll Key (dashboard MIC > Pemantauan AI > Perangkat & Token)'
}

if ([string]::IsNullOrWhiteSpace($label)) { $label = $env:COMPUTERNAME }

Write-Host "Memasang untuk perangkat: $label" -ForegroundColor Cyan
& (Join-Path $dir 'pasang.ps1') -Endpoint $endpoint -EnrollKey $key -Label $label

Write-Host ''
Read-Host 'Selesai. Tekan Enter untuk menutup'
