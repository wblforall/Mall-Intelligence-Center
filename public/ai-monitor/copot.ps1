<#
    WBL AI Monitor - copot.ps1  (WAJIB Administrator + password IT)
    ===============================================================
    Mencopot fitur "Pemantauan AI" dari laptop ini:
      - Menghapus Scheduled Task "WBL AI Monitor".
      - Menghapus folder program  C:\Program Files\WBL-AiMonitor
      - Menghapus folder data     C:\ProgramData\WBL-AiMonitor
      - Membersihkan baris blokir bertanda "# WBL-AiMonitor BLOCK" dari hosts.

    Hanya IT yang BISA mencopot:
      - Wajib dijalankan sebagai Administrator.
      - Wajib memasukkan password IT yang cocok dengan hash sha256 di config.json.

    Berkas transkrip Claude Code milik pengguna TIDAK disentuh sama sekali.
#>

[CmdletBinding()]
param()

$ErrorActionPreference = 'Continue'

$TaskName    = 'WBL AI Monitor'
$ProgramDir  = Join-Path $env:ProgramFiles 'WBL-AiMonitor'
$DataDir     = Join-Path $env:ProgramData  'WBL-AiMonitor'
$ConfigPath  = Join-Path $DataDir 'config.json'
$HostsPath   = Join-Path $env:SystemRoot 'System32\drivers\etc\hosts'
$BlockMarker = '# WBL-AiMonitor BLOCK'

# --- 0) WAJIB ADMINISTRATOR.
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltinRole]::Administrator)
if (-not $isAdmin) {
    Write-Host 'GAGAL: copot.ps1 harus dijalankan sebagai Administrator.' -ForegroundColor Red
    Write-Host 'Buka PowerShell dengan "Run as administrator", lalu ulangi.' -ForegroundColor Yellow
    exit 1
}

# --- 1) VERIFIKASI PASSWORD IT terhadap hash di config.json.
#        copot_hash dikelola terpusat di MIC dan disinkron ke config oleh kirim.ps1
#        lewat respons enroll/ingest. Di sini kita hanya memvalidasi.
if (-not (Test-Path -LiteralPath $ConfigPath)) {
    Write-Host "Config tidak ditemukan di '$ConfigPath'. Agen mungkin belum terpasang." -ForegroundColor Red
    Write-Host 'Pencopotan dibatalkan. Jalankan pasang.ps1 lebih dulu, atau bersihkan manual.' -ForegroundColor Yellow
    exit 1
}

$cfg = $null
try {
    $cfg = Get-Content -LiteralPath $ConfigPath -Raw -Encoding UTF8 | ConvertFrom-Json
} catch {
    Write-Host "Config tak terbaca: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

$storedHash = $null
if ($cfg -and ($cfg.PSObject.Properties.Name -contains 'copot_hash')) {
    $storedHash = $cfg.copot_hash
}

if ([string]::IsNullOrWhiteSpace($storedHash)) {
    Write-Host 'Password copot belum diatur di MIC atau belum tersinkron ke perangkat ini.' -ForegroundColor Red
    Write-Host 'Atur di dashboard MIC lalu tunggu laptop melapor, baru copot.' -ForegroundColor Yellow
    exit 1
}

$secure = Read-Host 'Masukkan password IT untuk mencopot' -AsSecureString
$bstr   = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try {
    $pwPlain = [System.Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
} finally {
    [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
}

# sha256 HEX lowercase dari BYTES UTF-8 password (cocok dengan PHP hash('sha256',$pw)).
$sha   = [System.Security.Cryptography.SHA256]::Create()
try {
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($pwPlain)
    $hex   = ([BitConverter]::ToString($sha.ComputeHash($bytes))).Replace('-', '').ToLower()
} finally {
    $sha.Dispose()
}
$pwPlain = $null

if ($hex -ne ([string]$storedHash).ToLower()) {
    Write-Host 'GAGAL: password IT salah. Pencopotan dibatalkan.' -ForegroundColor Red
    exit 1
}
Write-Host 'Password IT cocok. Melanjutkan pencopotan...' -ForegroundColor Green

Write-Host ''
Write-Host 'Mencopot WBL AI Monitor...' -ForegroundColor Cyan
Write-Host ''

# --- 2) Hapus Scheduled Task.
$task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if ($task) {
    try {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "Scheduled Task '$TaskName' telah dihapus." -ForegroundColor Green
    } catch {
        Write-Host "Gagal menghapus task: $($_.Exception.Message)" -ForegroundColor Red
    }
} else {
    Write-Host "Scheduled Task '$TaskName' tidak ditemukan (mungkin sudah dihapus)." -ForegroundColor Yellow
}

# --- 3) Bersihkan baris blokir dari hosts (pulihkan akses Claude Code).
try {
    if (Test-Path -LiteralPath $HostsPath) {
        $lines = @(Get-Content -LiteralPath $HostsPath -Encoding UTF8)
        $kept  = @($lines | Where-Object { $_ -notmatch [regex]::Escape($BlockMarker) })
        if (($kept -join "`r`n") -ne ($lines -join "`r`n")) {
            Set-Content -LiteralPath $HostsPath -Value $kept -Encoding ASCII
            Write-Host 'Baris blokir dibersihkan dari hosts (akses Claude Code dipulihkan).' -ForegroundColor Green
        } else {
            Write-Host 'Tidak ada baris blokir di hosts.' -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "Gagal membersihkan hosts: $($_.Exception.Message)" -ForegroundColor Red
}

# --- 4) Hapus folder program & data.
foreach ($dir in @($ProgramDir, $DataDir)) {
    if (Test-Path -LiteralPath $dir) {
        try {
            Remove-Item -LiteralPath $dir -Recurse -Force
            Write-Host "Folder dihapus: $dir" -ForegroundColor Green
        } catch {
            Write-Host "Gagal menghapus '$dir': $($_.Exception.Message)" -ForegroundColor Red
        }
    } else {
        Write-Host "Folder tidak ditemukan: $dir" -ForegroundColor Yellow
    }
}

Write-Host ''
Write-Host 'Selesai. Pemantauan AI sudah dihentikan & dicopot dari laptop ini.' -ForegroundColor Cyan
Write-Host 'Catatan: berkas transkrip Claude Code pengguna TIDAK disentuh sama sekali.'
Write-Host ''
exit 0
