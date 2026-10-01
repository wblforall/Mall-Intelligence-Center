<#
    WBL AI Monitor - copot.ps1  (WAJIB Administrator + password IT)
    ===============================================================
    Mencopot fitur "Pemantauan AI" dari laptop ini:
      - Menghapus Scheduled Task "WBL AI Monitor" (penegak SYSTEM) DAN
        "WBL AI Monitor Notice" (notifier sesi user).
      - MEMATIKAN proses PowerShell agen/notifier yang masih loop di memori
        (kirim.ps1/notice.ps1) supaya notifikasi berhenti TANPA perlu restart.
      - Membersihkan baris blokir bertanda "# WBL-AiMonitor BLOCK" dari hosts
        (+ ipconfig /flushdns).
      - Menghapus folder program C:\Program Files\WBL-AiMonitor (kirim.ps1 +
        notice.ps1) dan folder data C:\ProgramData\WBL-AiMonitor.

    Hanya IT yang BISA mencopot:
      - Wajib dijalankan sebagai Administrator.
      - Wajib memasukkan password IT. Password diverifikasi KE SERVER MIC (ikut
        dashboard terbaru) lewat endpoint verify-copot; bila server tak terjangkau
        atau token ditolak, jatuh ke CADANGAN LOKAL (hash sha256 di config.json).

    Berkas transkrip Claude Code milik pengguna TIDAK disentuh sama sekali.
#>

[CmdletBinding()]
param()

$ErrorActionPreference = 'Continue'

$TaskName       = 'WBL AI Monitor'
$NoticeTaskName = 'WBL AI Monitor Notice'
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

# --- 1) BACA CONFIG: endpoint_ingest, device_token, copot_hash (cadangan lokal).
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

$endpointIngest = $null
$deviceToken    = $null
$storedHash     = $null
if ($cfg) {
    if ($cfg.PSObject.Properties.Name -contains 'endpoint_ingest') { $endpointIngest = $cfg.endpoint_ingest }
    if ($cfg.PSObject.Properties.Name -contains 'device_token')    { $deviceToken    = $cfg.device_token }
    if ($cfg.PSObject.Properties.Name -contains 'copot_hash')       { $storedHash     = $cfg.copot_hash }
}

# URL verify diturunkan dari endpoint_ingest: '/ingest' di akhir -> '/verify-copot'.
$verifyUrl = $null
if (-not [string]::IsNullOrWhiteSpace($endpointIngest)) {
    if ($endpointIngest -match '/ingest/?$') {
        $verifyUrl = $endpointIngest -replace '/ingest/?$', '/verify-copot'
    } else {
        $verifyUrl = $endpointIngest.Replace('/ingest', '/verify-copot')
    }
}

# --- 2) Minta password -> sha256 HEX lowercase dari BYTES UTF-8
#        (cocok dengan PHP hash('sha256',$pw)).
$secure = Read-Host 'Masukkan password IT untuk mencopot' -AsSecureString
$bstr   = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try {
    $pwPlain = [System.Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
} finally {
    [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
}
$sha = [System.Security.Cryptography.SHA256]::Create()
try {
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($pwPlain)
    $hex   = ([BitConverter]::ToString($sha.ComputeHash($bytes))).Replace('-', '').ToLower()
} finally {
    $sha.Dispose()
}
$pwPlain = $null

# --- 3) VERIFIKASI KE SERVER (ikut dashboard terbaru); cadangan lokal bila offline.
$verified   = $false
$serverDone = $false   # $true bila server memberi jawaban tegas (benar/salah/unset)

if ($deviceToken -and $verifyUrl) {
    $headers = @{
        'Authorization' = "Bearer $deviceToken"
        'Content-Type'  = 'application/json'
    }
    $body = @{ hash = $hex } | ConvertTo-Json -Compress
    try {
        $resp = Invoke-RestMethod -Uri $verifyUrl -Method Post -Headers $headers `
            -Body $body -TimeoutSec 20 -UseBasicParsing
        $serverDone = $true
        if ($resp -and $resp.ok) {
            $verified = $true
            Write-Host 'Terverifikasi ke server MIC.' -ForegroundColor Green
        } elseif ($resp -and $resp.unset) {
            Write-Host 'Password copot belum diatur di MIC. Atur dulu di dashboard.' -ForegroundColor Red
            exit 1
        } else {
            Write-Host 'GAGAL: password copot salah.' -ForegroundColor Red
            exit 1
        }
    } catch {
        $status = $null
        try { $status = [int]$_.Exception.Response.StatusCode.value__ } catch { }
        if ($status -eq 401) {
            Write-Host 'Token perangkat ditolak server (mungkin perangkat sudah dihapus).' -ForegroundColor Yellow
        } else {
            Write-Host 'Server verifikasi tak terjangkau (jaringan/timeout).' -ForegroundColor Yellow
        }
        Write-Host 'Beralih ke verifikasi cadangan lokal...' -ForegroundColor Yellow
        # $serverDone tetap $false -> jatuh ke cadangan lokal (langkah 4).
    }
}

# --- 4) CADANGAN LOKAL (server tak terjangkau / token ditolak / tak ada token).
if (-not $verified -and -not $serverDone) {
    if (-not [string]::IsNullOrWhiteSpace($storedHash)) {
        if ($hex -eq ([string]$storedHash).ToLower()) {
            $verified = $true
            Write-Host 'Terverifikasi via cadangan lokal (offline).' -ForegroundColor Green
        } else {
            Write-Host 'GAGAL: password copot salah.' -ForegroundColor Red
            exit 1
        }
    } else {
        Write-Host 'Tidak bisa memverifikasi: server tak terjangkau dan belum ada cadangan di' -ForegroundColor Red
        Write-Host 'perangkat ini. Sambungkan ke jaringan lalu coba lagi.' -ForegroundColor Yellow
        exit 1
    }
}

if (-not $verified) {
    # Jaring pengaman; seharusnya tak tercapai.
    Write-Host 'GAGAL: verifikasi password tidak berhasil.' -ForegroundColor Red
    exit 1
}
Write-Host 'Password IT cocok. Melanjutkan pencopotan...' -ForegroundColor Green

Write-Host ''
Write-Host 'Mencopot WBL AI Monitor...' -ForegroundColor Cyan
Write-Host ''

# --- 2) Hapus Scheduled Task (penegak SYSTEM + notifier user).
foreach ($tn in @($TaskName, $NoticeTaskName)) {
    $task = Get-ScheduledTask -TaskName $tn -ErrorAction SilentlyContinue
    if ($task) {
        try {
            Unregister-ScheduledTask -TaskName $tn -Confirm:$false
            Write-Host "Scheduled Task '$tn' telah dihapus." -ForegroundColor Green
        } catch {
            Write-Host "Gagal menghapus task '$tn': $($_.Exception.Message)" -ForegroundColor Red
        }
    } else {
        Write-Host "Scheduled Task '$tn' tidak ditemukan (mungkin sudah dihapus)." -ForegroundColor Yellow
    }
}

# --- 2b) MATIKAN proses PowerShell agen/notifier yang masih loop di memori.
#         Tanpa ini, instance lama (notice.ps1/kirim.ps1) tetap hidup sampai
#         logoff/restart dan notifikasi masih bisa muncul. Filter commandline
#         WAJIB cocok WBL-AiMonitor/notice.ps1/kirim.ps1 -> tidak mematikan
#         PowerShell lain, dan TIDAK cocok dengan copot.ps1 (aman untuk diri sendiri).
try {
    $killed = 0
    Get-CimInstance Win32_Process -Filter "Name='powershell.exe'" -ErrorAction SilentlyContinue |
        Where-Object {
            $_.CommandLine -and (
                $_.CommandLine -like '*WBL-AiMonitor*' -or
                $_.CommandLine -like '*notice.ps1*'    -or
                $_.CommandLine -like '*kirim.ps1*'
            )
        } |
        ForEach-Object {
            try { Stop-Process -Id $_.ProcessId -Force -ErrorAction Stop; $killed++ } catch { }
        }
    Write-Host "Proses agen/notifier yang berjalan dihentikan: $killed." -ForegroundColor Green
} catch {
    Write-Host "Gagal menghentikan proses agen/notifier: $($_.Exception.Message)" -ForegroundColor Red
}

# --- 3) Bersihkan baris blokir dari hosts (pulihkan akses Claude Code) + flushdns.
try {
    if (Test-Path -LiteralPath $HostsPath) {
        $lines = @(Get-Content -LiteralPath $HostsPath -Encoding UTF8)
        $kept  = @($lines | Where-Object { $_ -notmatch [regex]::Escape($BlockMarker) })
        if (($kept -join "`r`n") -ne ($lines -join "`r`n")) {
            Set-Content -LiteralPath $HostsPath -Value $kept -Encoding ASCII
            try {
                Start-Process -FilePath "$env:SystemRoot\System32\ipconfig.exe" `
                    -ArgumentList '/flushdns' -WindowStyle Hidden -Wait -ErrorAction SilentlyContinue
            } catch { }
            Write-Host 'Baris blokir dibersihkan dari hosts + flushdns (akses dipulihkan).' -ForegroundColor Green
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
