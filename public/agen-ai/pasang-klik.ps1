<#
    WBL AI Monitor - pasang-klik.ps1
    ================================
    Dipanggil oleh KLIK-PASANG.bat (yang sudah menaikkan hak admin).
    Membaca pengaturan.txt di folder yang sama, lalu menjalankan pasang.ps1
    tanpa perlu mengetik apa pun di tiap laptop. Isi pengaturan.txt cukup
    sekali oleh IT.
#>
$ErrorActionPreference = 'Stop'
$dir = Split-Path -Parent $MyInvocation.MyCommand.Path
$cfg = Join-Path $dir 'pengaturan.txt'

if (-not (Test-Path -LiteralPath $cfg)) {
    Write-Host '[GAGAL] pengaturan.txt tidak ditemukan di folder ini.' -ForegroundColor Red
    Read-Host 'Tekan Enter untuk menutup'; exit 1
}

# Baca pasangan KUNCI=nilai, abaikan baris komentar (#) dan kosong.
$map = @{}
foreach ($line in Get-Content -LiteralPath $cfg -Encoding UTF8) {
    if ($line -match '^\s*#') { continue }
    if ($line -match '=') {
        $parts = $line -split '=', 2
        $map[$parts[0].Trim().ToUpper()] = $parts[1].Trim()
    }
}

$endpoint = $map['ENDPOINT']
$key      = $map['ENROLLKEY']
$label    = $map['LABEL']
if ([string]::IsNullOrWhiteSpace($label)) { $label = $env:COMPUTERNAME }

if ([string]::IsNullOrWhiteSpace($endpoint) -or $endpoint -like '*GANTI*' -or
    [string]::IsNullOrWhiteSpace($key) -or $key -like '*GANTI*') {
    Write-Host '[GAGAL] ENDPOINT dan ENROLLKEY di pengaturan.txt belum diisi.' -ForegroundColor Red
    Write-Host '        Buka pengaturan.txt, isi kedua baris itu, simpan, lalu klik lagi.' -ForegroundColor Yellow
    Read-Host 'Tekan Enter untuk menutup'; exit 1
}

Write-Host "Memasang WBL AI Monitor untuk perangkat: $label" -ForegroundColor Cyan
& (Join-Path $dir 'pasang.ps1') -Endpoint $endpoint -EnrollKey $key -Label $label

Write-Host ''
Read-Host 'Selesai. Tekan Enter untuk menutup'
