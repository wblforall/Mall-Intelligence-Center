<#
    WBL AI Monitor - notice.ps1  (NOTIFIER sesi user - KOSMETIK)
    ============================================================
    Berjalan di KONTEKS USER (task "WBL AI Monitor Notice", trigger AtLogOn).
    Tujuannya KOSMETIK: memberi tahu user saat ia mencoba memakai Claude Code
    padahal akses sedang TERKUNCI (pending/blokir), supaya tidak sekadar loading.

    PENTING: ini BUKAN penegak. Penegakan sebenarnya (kunci hosts) dilakukan oleh
    agen SYSTEM (kirim.ps1 / task "WBL AI Monitor"). Bila user mematikan notifier
    ini, enforcement SYSTEM TETAP berjalan.

    Membaca keadaan dari: %ProgramData%\WBL-AiMonitor\state.json (field keadaan,
    alasan). Tidak menulis apa pun. Semua tampilan dibungkus try/catch agar tak
    pernah crash.
#>

[CmdletBinding()]
param()

$ErrorActionPreference = 'Continue'

$DataDir   = Join-Path $env:ProgramData 'WBL-AiMonitor'
$StatePath = Join-Path $DataDir 'state.json'

# Debounce: jangan menotifikasi lebih sering dari sekali per 60 detik.
$script:lastNotify = [datetime]::MinValue

# --- Baca keadaan + alasan dari state.json (read-only). Gagal -> kosong.
function Read-State {
    $res = @{ keadaan = ''; alasan = '' }
    try {
        if (Test-Path -LiteralPath $StatePath) {
            $raw = Get-Content -LiteralPath $StatePath -Raw -Encoding UTF8 -ErrorAction Stop
            if (-not [string]::IsNullOrWhiteSpace($raw)) {
                $obj = $raw | ConvertFrom-Json
                if ($obj.PSObject.Properties.Name -contains 'keadaan') { $res.keadaan = [string]$obj.keadaan }
                if ($obj.PSObject.Properties.Name -contains 'alasan')  { $res.alasan  = [string]$obj.alasan }
            }
        }
    } catch { }
    return $res
}

# --- Deteksi user sedang memakai Claude Code (proses claude.exe, atau node.exe
#     dengan command line memuat 'claude').
function Test-ClaudeInUse {
    try {
        $p = Get-Process -Name 'claude' -ErrorAction SilentlyContinue
        if ($p) { return $true }
    } catch { }
    try {
        $procs = Get-CimInstance Win32_Process -Filter "Name='node.exe' OR Name='claude.exe'" -ErrorAction SilentlyContinue
        foreach ($pr in $procs) {
            if ($pr.CommandLine -and ($pr.CommandLine -match 'claude')) { return $true }
        }
    } catch { }
    return $false
}

# --- Tampilkan notifikasi. Urutan: BurntToast (bila module ada) -> balloon
#     NotifyIcon (Windows.Forms) -> msg.exe. Semua dibungkus try/catch.
function Show-Notice {
    param([string]$Title, [string]$Message)

    # 1) BurntToast (hanya bila module sudah terpasang; tidak memaksa instal).
    try {
        if (Get-Module -ListAvailable -Name BurntToast -ErrorAction SilentlyContinue) {
            Import-Module BurntToast -ErrorAction Stop
            New-BurntToastNotification -Text $Title, $Message -ErrorAction Stop
            return
        }
    } catch { }

    # 2) Balloon tip via NotifyIcon (andal tanpa instalasi module).
    try {
        Add-Type -AssemblyName System.Windows.Forms -ErrorAction Stop
        Add-Type -AssemblyName System.Drawing -ErrorAction Stop
        $ni = New-Object System.Windows.Forms.NotifyIcon
        $ni.Icon = [System.Drawing.SystemIcons]::Warning
        $ni.Visible = $true
        $ni.BalloonTipTitle = $Title
        $ni.BalloonTipText  = $Message
        $ni.BalloonTipIcon  = [System.Windows.Forms.ToolTipIcon]::Warning
        $ni.ShowBalloonTip(8000)
        Start-Sleep -Seconds 9
        $ni.Visible = $false
        $ni.Dispose()
        return
    } catch { }

    # 3) Fallback terakhir: msg.exe ke sesi sendiri.
    try {
        $msgExe = Join-Path $env:SystemRoot 'System32\msg.exe'
        if (Test-Path -LiteralPath $msgExe) {
            & $msgExe '*' '/TIME:0' $Message 2>$null
        }
    } catch { }
}

# =====================================================================
#  LOOP RINGAN selama sesi login (task berakhir saat user logoff).
# =====================================================================
while ($true) {
    try {
        $st = Read-State
        $keadaan = $st.keadaan
        if ($keadaan -eq 'terkunci-pending' -or $keadaan -eq 'terkunci-blokir') {
            if (Test-ClaudeInUse) {
                $now = Get-Date
                if (($now - $script:lastNotify).TotalSeconds -ge 60) {
                    if ($keadaan -eq 'terkunci-pending') {
                        $msg = 'Akses Claude Code belum diizinkan Tim IT untuk perangkat ini (menunggu persetujuan).'
                    } else {
                        $msg = 'Akses Claude Code dinonaktifkan oleh Tim IT.'
                        if (-not [string]::IsNullOrWhiteSpace($st.alasan)) {
                            $msg = "$msg Alasan: $($st.alasan)"
                        }
                    }
                    Show-Notice -Title 'WBL AI Monitor' -Message $msg
                    $script:lastNotify = $now
                }
            }
        }
    } catch { }
    Start-Sleep -Seconds 10
}
