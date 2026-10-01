<#
    WBL AI Monitor - pasang-klik.ps1
    ================================
    Dipanggil oleh KLIK-PASANG.bat (yang sudah menaikkan hak admin).
    Membaca ENDPOINT & LABEL dari pengaturan.txt lalu memanggil pasang.ps1.

    TANPA kunci enrollment: perangkat akan "Menunggu persetujuan" di dashboard
    MIC sampai IT menekan Setujui. Tidak ada kunci yang perlu ditempel.
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
$label    = $map['LABEL']
$interval = $map['INTERVAL_DETIK']

Write-Host '=== Pemasangan WBL AI Monitor ===' -ForegroundColor Cyan

# ENDPOINT: biasanya sudah terisi; minta hanya bila kosong/placeholder.
if ([string]::IsNullOrWhiteSpace($endpoint) -or $endpoint -like '*GANTI*') {
    $endpoint = Read-Host 'Masukkan ENDPOINT enroll MIC (mis. https://mic.wbl-bsb.com/api/ai-monitor/enroll)'
}

if ([string]::IsNullOrWhiteSpace($label)) { $label = $env:COMPUTERNAME }

# Susun argumen; teruskan -IntervalDetik hanya bila INTERVAL_DETIK terisi angka.
$pasangArgs = @{ Endpoint = $endpoint; Label = $label }
if (-not [string]::IsNullOrWhiteSpace($interval)) {
    $n = 0
    if ([int]::TryParse($interval, [ref]$n)) {
        $pasangArgs['IntervalDetik'] = $n
    } else {
        Write-Host "INTERVAL_DETIK '$interval' bukan angka; memakai default 30." -ForegroundColor Yellow
    }
}

Write-Host "Memasang untuk perangkat: $label" -ForegroundColor Cyan
& (Join-Path $dir 'pasang.ps1') @pasangArgs

Write-Host ''
Write-Host 'Perangkat akan muncul di dashboard MIC sebagai "Menunggu persetujuan".' -ForegroundColor Yellow
Write-Host 'Minta IT menekan Setujui agar agen aktif.' -ForegroundColor Yellow
Write-Host ''
Read-Host 'Selesai. Tekan Enter untuk menutup'
