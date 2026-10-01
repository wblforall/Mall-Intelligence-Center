<#
    WBL AI Monitor - kirim.ps1  (DIKELOLA IT, berjalan sebagai SYSTEM)
    ==================================================================
    Dijalankan oleh Scheduled Task "WBL AI Monitor" (principal SYSTEM). Agar
    approve/blokir/buka terasa dalam hitungan detik, skrip ini memakai LOOP
    INTERNAL: satu putaran (enroll-jika-perlu, poll status, tegakkan kunci hosts,
    kirim transkrip) diulang tiap `interval_detik` (default 30). Scheduled Task
    hanya bertindak sebagai WATCHDOG (repetition 1 menit, MultipleInstances=
    IgnoreNew) untuk menghidupkan loop bila prosesnya mati.

    Prinsip:
      - HANYA membaca berkas transkrip *.jsonl. Tidak membaca/mengirim berkas lain.
      - TIDAK PERNAH menghapus atau mengubah berkas transkrip.
      - Mengirim baris transkrip sebagai BASE64 (field `enc`) agar lolos WAF
        hosting; penyamaran kata sandi/token tetap dilakukan DI SERVER.
      - Bila server memerintahkan blokir/pending, akses Claude Code
        (api.anthropic.com) dimatikan via berkas hosts + flushdns secara DIAM-DIAM.
        Agen SYSTEM TIDAK memunculkan popup apa pun; notifikasi ke user adalah
        tugas notice.ps1 (konteks user). kirim.ps1 hanya menulis keadaan+alasan
        ke state.json agar notice.ps1 bisa membacanya.

    Anti-bloat log: putaran "idle" (tak ada perubahan keadaan & tak ada baris
    baru) TIDAK menulis log. Log hanya saat transisi keadaan, enroll, error, 401,
    atau saat benar-benar mengunggah baris baru.

    Tidak ada self-healing: skrip ini tidak memasang ulang dirinya bila dihapus.
#>

[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

# --- Lokasi tetap (IT-managed).
$DataDir    = Join-Path $env:ProgramData 'WBL-AiMonitor'
$ConfigPath = Join-Path $DataDir 'config.json'
$StatePath  = Join-Path $DataDir 'state.json'
$LogPath    = Join-Path $DataDir 'kirim.log'

$HostsPath   = Join-Path $env:SystemRoot 'System32\drivers\etc\hosts'
$BlockMarker = '# WBL-AiMonitor BLOCK'
$maxBatchBytes = 3.5MB

# --- Log sederhana (tidak pernah melempar error ke pemanggil).
function Write-Log {
    param([string]$Message)
    try {
        $stamp = (Get-Date).ToString('yyyy-MM-dd HH:mm:ss')
        Add-Content -LiteralPath $LogPath -Value "[$stamp] $Message" -Encoding UTF8
    } catch { }
}

# --- Config wajib ada di ProgramData (ditulis oleh pasang.ps1).
if (-not (Test-Path -LiteralPath $ConfigPath)) {
    Write-Log "Config tidak ditemukan di '$ConfigPath'. Jalankan pasang.ps1 sebagai admin dulu."
    exit 0
}
try {
    $cfg = Get-Content -LiteralPath $ConfigPath -Raw -Encoding UTF8 | ConvertFrom-Json
} catch {
    Write-Log "Config rusak/tak terbaca: $($_.Exception.Message)"
    exit 0
}

# --- Interval loop (detik). Default 30; minimal 5 untuk jaga-jaga.
$interval = 30
if (($cfg.PSObject.Properties.Name -contains 'interval_detik') -and $cfg.interval_detik) {
    try { $interval = [int]$cfg.interval_detik } catch { $interval = 30 }
}
if ($interval -lt 5) { $interval = 5 }

# --- Tulis config secara aman (tmp lalu Move).
function Save-Config {
    param($Cfg)
    try {
        $tmp = "$ConfigPath.tmp"
        ($Cfg | ConvertTo-Json -Depth 10) | Set-Content -LiteralPath $tmp -Encoding UTF8
        Move-Item -LiteralPath $tmp -Destination $ConfigPath -Force
    } catch {
        Write-Log "Gagal menyimpan config: $($_.Exception.Message)"
    }
}

# --- Simpan copot_hash dari respons server (enroll/ingest) ke config bila berubah.
function Sync-CopotHash {
    param($Resp)
    if (-not $Resp) { return }
    if (-not ($Resp.PSObject.Properties.Name -contains 'copot_hash')) { return }
    $incoming = $Resp.copot_hash
    $current  = $null
    if ($cfg.PSObject.Properties.Name -contains 'copot_hash') { $current = $cfg.copot_hash }
    if ("$incoming" -ne "$current") {
        if ($cfg.PSObject.Properties.Name -contains 'copot_hash') {
            $cfg.copot_hash = $incoming
        } else {
            $cfg | Add-Member -NotePropertyName 'copot_hash' -NotePropertyValue $incoming -Force
        }
        Save-Config -Cfg $cfg
        if ([string]::IsNullOrWhiteSpace("$incoming")) {
            Write-Log 'copot_hash dikosongkan sesuai server.'
        } else {
            Write-Log 'copot_hash tersinkron dari server.'
        }
    }
}

# --- State: { files: {path->count}, keadaan, alasan }.
#     keadaan : 'terkunci-pending' | 'terkunci-blokir' | 'terbuka'.
#     alasan  : teks alasan blokir dari server (dibaca notice.ps1 untuk notifikasi).
function Load-State {
    $result = @{ files = @{}; keadaan = ''; alasan = '' }
    if (Test-Path -LiteralPath $StatePath) {
        try {
            $raw = Get-Content -LiteralPath $StatePath -Raw -Encoding UTF8
            if (-not [string]::IsNullOrWhiteSpace($raw)) {
                $obj = $raw | ConvertFrom-Json
                if ($obj.PSObject.Properties.Name -contains 'files' -and $obj.files) {
                    foreach ($p in $obj.files.PSObject.Properties) {
                        $result.files[$p.Name] = [int]$p.Value
                    }
                }
                if ($obj.PSObject.Properties.Name -contains 'keadaan') { $result.keadaan = [string]$obj.keadaan }
                if ($obj.PSObject.Properties.Name -contains 'alasan')  { $result.alasan  = [string]$obj.alasan }
            }
        } catch {
            Write-Log "State rusak atau tak terbaca, mulai dari kosong: $($_.Exception.Message)"
        }
    }
    return $result
}

function Save-State {
    param([hashtable]$State)
    try {
        $tmp = "$StatePath.tmp"
        $out = [ordered]@{
            files   = $State.files
            keadaan = [string]$State.keadaan
            alasan  = [string]$State.alasan
        }
        ($out | ConvertTo-Json -Depth 6) | Set-Content -LiteralPath $tmp -Encoding UTF8
        Move-Item -LiteralPath $tmp -Destination $StatePath -Force
    } catch {
        Write-Log "Gagal menyimpan state: $($_.Exception.Message)"
    }
}

# --- Akun (user) yang sedang login (task jalan sebagai SYSTEM). Boleh null.
function Get-ActiveAccount {
    try {
        $u = (Get-CimInstance -ClassName Win32_ComputerSystem -ErrorAction Stop).UserName
        if ([string]::IsNullOrWhiteSpace($u)) { return $null }
        return $u
    } catch {
        return $null
    }
}

# CATATAN: agen SYSTEM TIDAK menampilkan notifikasi apa pun (tidak ada msg.exe /
# popup dari konteks SYSTEM). Notifikasi ke user adalah tugas notice.ps1.

# --- Terapkan/batalkan kunci akses Claude Code lewat hosts (api.anthropic.com).
#     Idempoten + flushdns saat konten berubah. Web claude.ai SENGAJA tidak diblok.
function Set-HostsBlock {
    param([bool]$On)
    try {
        $lines = @()
        if (Test-Path -LiteralPath $HostsPath) {
            $lines = @(Get-Content -LiteralPath $HostsPath -Encoding UTF8 -ErrorAction Stop)
        }
        $kept = @($lines | Where-Object { $_ -notmatch [regex]::Escape($BlockMarker) })
        if ($On) {
            $kept += "0.0.0.0 api.anthropic.com $BlockMarker"
        }
        $newContent = ($kept -join "`r`n")
        $oldContent = ($lines -join "`r`n")
        if ($newContent -ne $oldContent) {
            Set-Content -LiteralPath $HostsPath -Value $kept -Encoding ASCII -ErrorAction Stop
            # Flush cache DNS HANYA saat konten berubah, agar berlaku untuk koneksi BARU.
            try {
                Start-Process -FilePath "$env:SystemRoot\System32\ipconfig.exe" `
                    -ArgumentList '/flushdns' -WindowStyle Hidden -Wait -ErrorAction SilentlyContinue
            } catch {
                Write-Log "flushdns gagal (diabaikan): $($_.Exception.Message)"
            }
            if ($On) {
                Write-Log 'Hosts dikunci (api.anthropic.com) + flushdns.'
            } else {
                Write-Log 'Hosts dibuka + flushdns.'
            }
        }
    } catch {
        Write-Log "Gagal memperbarui hosts (blokir=$On): $($_.Exception.Message)"
    }
}

# --- Kunci akses + tandai pending. Dipakai saat token DITOLAK server (HTTP 401,
#     mis. perangkat dihapus): akses Claude Code ikut MATI, bukan dibiarkan terbuka.
function Set-PendingLock {
    param([hashtable]$State)
    Set-HostsBlock -On $true
    if ($State.keadaan -ne 'terkunci-pending') {
        Write-Log 'Keadaan -> terkunci-pending (token ditolak server).'
    }
    $State.keadaan = 'terkunci-pending'
    $State.alasan  = ''
}

# --- Variabel status per-putaran (script-scope agar Send-Batch bisa menulis).
$script:lastStatus   = $null
$script:lastBlokir   = $null
$script:lastAlasan   = ''
$script:unauthorized = $false
$script:netFailLogged = $false   # guard anti-bloat untuk kegagalan jaringan beruntun.

# Variabel per-putaran yang dibaca Send-Batch (dynamic scope dari Invoke-Cycle).
$script:deviceToken = $null
$script:account     = $null
$script:hostName    = $env:COMPUTERNAME
$script:headers     = @{}

# --- Kirim satu batch; $true bila sukses (2xx). Menyimpan status/blokir/copot_hash.
#     Isi transkrip dikirim sebagai BASE64 (field `enc`) agar OPAQUE dan lolos
#     WAF/ModSecurity hosting (yang memblokir body berisi kode/SQL/shell). Field
#     `lines` mentah TIDAK dikirim lagi. Poll kosong -> enc='[]' (tetap lolos).
function Send-Batch {
    param([array]$Lines)

    # Bangun JSON array yang DIJAMIN array (hindari PowerShell merender 1 elemen
    # sebagai objek tunggal). 0 elemen -> '[]'.
    $arrJson = '[' + (($Lines | ForEach-Object { $_ | ConvertTo-Json -Depth 50 -Compress }) -join ',') + ']'
    $enc = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($arrJson))

    $payload = @{
        device_token = $script:deviceToken
        host         = $script:hostName
        account      = $script:account
        enc          = $enc
    }
    $json = $payload | ConvertTo-Json -Compress
    try {
        $resp = Invoke-RestMethod -Uri $cfg.endpoint_ingest -Method Post -Headers $script:headers `
            -Body $json -TimeoutSec 60 -UseBasicParsing
        if ($resp -and ($resp.PSObject.Properties.Name -contains 'status')) {
            $script:lastStatus = [string]$resp.status
        }
        if ($resp -and ($resp.PSObject.Properties.Name -contains 'blokir')) {
            $script:lastBlokir = [bool]$resp.blokir
            if ($resp.PSObject.Properties.Name -contains 'blokir_alasan') {
                $script:lastAlasan = [string]$resp.blokir_alasan
            }
        }
        Sync-CopotHash -Resp $resp
        $script:netFailLogged = $false   # server terjangkau lagi.
        return $true
    } catch {
        $status = $null
        try { $status = [int]$_.Exception.Response.StatusCode.value__ } catch { }
        if ($status -eq 401) {
            $script:unauthorized = $true
            Write-Log 'Ingest 401 (token ditolak).'
        } elseif (-not $script:netFailLogged) {
            Write-Log "Gagal mengirim batch ($($Lines.Count) baris), status=$status : $($_.Exception.Message)"
            $script:netFailLogged = $true
        }
        return $false
    }
}

# =====================================================================
#  SATU PUTARAN (dipanggil berulang oleh loop). Pakai 'return', bukan 'exit'.
# =====================================================================
function Invoke-Cycle {
    # Reset status tiap putaran.
    $script:lastStatus = $null
    $script:lastBlokir = $null
    $script:lastAlasan = ''
    $script:unauthorized = $false

    $script:hostName = $env:COMPUTERNAME
    $script:account  = Get-ActiveAccount
    $script:deviceToken = $null
    if ($cfg.PSObject.Properties.Name -contains 'device_token') { $script:deviceToken = $cfg.device_token }

    # --- ENROLL bila device_token null (tanpa kunci enrollment).
    if ([string]::IsNullOrWhiteSpace($script:deviceToken)) {
        if ([string]::IsNullOrWhiteSpace($cfg.endpoint_enroll)) {
            if (-not $script:netFailLogged) { Write-Log 'endpoint_enroll kosong di config; tidak bisa enroll.'; $script:netFailLogged = $true }
            return
        }
        $enrollLabel = $script:hostName
        if (($cfg.PSObject.Properties.Name -contains 'label') -and -not [string]::IsNullOrWhiteSpace($cfg.label)) {
            $enrollLabel = $cfg.label
        }
        $enrollBody = @{
            machine_id = $cfg.machine_id
            host       = $script:hostName
            account    = $script:account
            label      = $enrollLabel
        } | ConvertTo-Json -Depth 5
        try {
            $resp = Invoke-RestMethod -Uri $cfg.endpoint_enroll -Method Post `
                -Body $enrollBody -ContentType 'application/json' `
                -TimeoutSec 60 -UseBasicParsing
        } catch {
            if (-not $script:netFailLogged) { Write-Log "Enroll gagal: $($_.Exception.Message)."; $script:netFailLogged = $true }
            return
        }
        if ($resp -and $resp.success -and -not [string]::IsNullOrWhiteSpace($resp.device_token)) {
            $script:deviceToken = $resp.device_token
            if ($cfg.PSObject.Properties.Name -contains 'device_token') {
                $cfg.device_token = $script:deviceToken
            } else {
                $cfg | Add-Member -NotePropertyName 'device_token' -NotePropertyValue $script:deviceToken -Force
            }
            if ($resp.PSObject.Properties.Name -contains 'device_id') {
                $cfg | Add-Member -NotePropertyName 'device_id' -NotePropertyValue $resp.device_id -Force
            }
            Save-Config -Cfg $cfg
            Sync-CopotHash -Resp $resp
            $script:netFailLogged = $false
            Write-Log 'Enroll sukses; device_token disimpan.'
        } else {
            Write-Log 'Enroll menolak (success=false atau token kosong).'
            return
        }
    }

    $script:headers = @{
        'Authorization' = "Bearer $($script:deviceToken)"
        'Content-Type'  = 'application/json'
    }

    $state = Load-State

    # --- POLL STATUS (ingest lines:[]) sebelum kirim transkrip.
    $pollOk = Send-Batch -Lines @()

    if ($script:unauthorized) {
        # 401: token ditolak -> kunci akses, tandai pending, reset token (enroll ulang).
        Set-PendingLock -State $state
        if ($cfg.PSObject.Properties.Name -contains 'device_token') { $cfg.device_token = $null }
        Save-Config -Cfg $cfg
        Save-State -State $state
        Write-Log 'device_token direset ke null (akan enroll ulang sebagai pending).'
        return
    }
    if (-not $pollOk) {
        # Offline transien (bukan 401): Send-Batch sudah mencatat sekali. Jangan ubah
        # hosts/state; tidak menotifikasi. Coba lagi putaran berikutnya.
        return
    }

    # --- Tentukan keadaan. Akses BOLEH hanya bila status=aktif DAN blokir=false.
    $status = $script:lastStatus
    $blokir = $script:lastBlokir
    if ($status -eq 'aktif') {
        if ($blokir -eq $true) { $keadaan = 'terkunci-blokir' } else { $keadaan = 'terbuka' }
    } else {
        $keadaan = 'terkunci-pending'
    }

    $prevKeadaan = [string]$state.keadaan
    if ([string]::IsNullOrWhiteSpace($prevKeadaan)) { $prevKeadaan = 'terbuka' }
    $transition = ($keadaan -ne $prevKeadaan)

    # Terapkan hosts (diam-diam; tak ada popup dari SYSTEM). Notifikasi user
    # ditangani notice.ps1 yang membaca state.json.
    if ($keadaan -eq 'terbuka') { Set-HostsBlock -On $false } else { Set-HostsBlock -On $true }
    if ($transition) { Write-Log "Keadaan: $prevKeadaan -> $keadaan." }

    $state.keadaan = $keadaan
    if ($keadaan -eq 'terkunci-blokir') { $state.alasan = [string]$script:lastAlasan } else { $state.alasan = '' }

    # --- Belum disetujui (status != aktif): jangan kirim transkrip / majukan penanda.
    if ($status -ne 'aktif') {
        Save-State -State $state
        return
    }

    # --- AKTIF -> kirim transkrip profil TIAP user.
    $files = @()
    try {
        $usersRoot = Join-Path $env:SystemDrive 'Users'
        $files = Get-ChildItem -Path (Join-Path $usersRoot '*\.claude\projects\*\*.jsonl') `
            -File -ErrorAction SilentlyContinue
    } catch {
        Write-Log "Gagal menelusuri transkrip: $($_.Exception.Message)"
    }

    $cycleSent = 0
    foreach ($file in $files) {
        if ($script:unauthorized) { break }
        $path = $file.FullName

        $already = 0
        if ($state.files.ContainsKey($path)) { $already = [int]$state.files[$path] }

        try {
            $allLines = Get-Content -LiteralPath $path -Encoding UTF8 -ErrorAction Stop
        } catch {
            Write-Log "Lewati berkas tak terbaca '$path': $($_.Exception.Message)"
            continue
        }

        if ($null -eq $allLines) { continue }
        $allLines = @($allLines)
        $total = $allLines.Count
        if ($total -le $already) { continue }

        $newLines = $allLines[$already..($total - 1)]

        $batch      = New-Object System.Collections.ArrayList
        $batchBytes = 0
        $failed     = $false

        foreach ($line in $newLines) {
            if ([string]::IsNullOrWhiteSpace($line)) { continue }
            $obj = $null
            try {
                $obj = $line | ConvertFrom-Json
            } catch {
                Write-Log "Baris tak bisa di-parse (dilewati) di '$path'."
                continue
            }
            $lineBytes = [System.Text.Encoding]::UTF8.GetByteCount($line)
            if (($batchBytes + $lineBytes) -gt $maxBatchBytes -and $batch.Count -gt 0) {
                if (Send-Batch -Lines ($batch.ToArray())) {
                    $batch.Clear() | Out-Null
                    $batchBytes = 0
                } else {
                    $failed = $true
                    break
                }
            }
            [void]$batch.Add($obj)
            $batchBytes += $lineBytes
        }

        if (-not $failed -and $batch.Count -gt 0) {
            if (-not (Send-Batch -Lines ($batch.ToArray()))) { $failed = $true }
        }

        if ($script:unauthorized) { break }

        if ($failed) {
            Write-Log "Berkas '$path': kirim gagal, penanda tetap di $already dari $total."
        } else {
            $cycleSent += ($total - $already)
            $state.files[$path] = $total
        }
    }

    # Token ditolak di tengah kirim: kunci + reset token.
    if ($script:unauthorized) {
        Set-PendingLock -State $state
        if ($cfg.PSObject.Properties.Name -contains 'device_token') { $cfg.device_token = $null }
        Save-Config -Cfg $cfg
        Save-State -State $state
        Write-Log 'device_token direset ke null (akan enroll ulang sebagai pending).'
        return
    }

    if ($cycleSent -gt 0) { Write-Log "Mengunggah $cycleSent baris baru." }
    Save-State -State $state
}

# =====================================================================
#  LOOP INTERNAL: satu kesalahan per putaran tidak menghentikan loop.
# =====================================================================
Write-Log "Agen mulai (loop internal tiap $interval detik)."
while ($true) {
    try {
        Invoke-Cycle
    } catch {
        Write-Log "Putaran error: $($_.Exception.Message)"
    }
    Start-Sleep -Seconds $interval
}
