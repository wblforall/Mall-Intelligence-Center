<#
    WBL AI Monitor - kirim.ps1  (DIKELOLA IT, berjalan sebagai SYSTEM)
    ==================================================================
    Dipanggil oleh Scheduled Task "WBL AI Monitor" (principal SYSTEM, tiap 30
    menit + saat startup). Mengirim baris transkrip Claude Code (*.jsonl) dari
    profil TIAP user di laptop ini ke server MIC.

    Prinsip:
      - HANYA membaca berkas transkrip *.jsonl. Tidak membaca/mengirim berkas lain.
      - TIDAK PERNAH menghapus atau mengubah berkas transkrip.
      - Mengirim baris mentah; penyamaran kata sandi/token dilakukan DI SERVER.
      - Bila server memerintahkan blokir, akses Claude Code (api.anthropic.com)
        dimatikan via berkas hosts, dan user yang login diberi notifikasi.

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
#     Password copot dikelola terpusat di MIC; laptop hanya menyimpan hash-nya
#     agar validasi copot bisa dilakukan offline. Null -> simpan null (kosongkan).
function Sync-CopotHash {
    param($Resp)
    if (-not $Resp) { return }
    if (-not ($Resp.PSObject.Properties.Name -contains 'copot_hash')) { return }
    $incoming = $Resp.copot_hash
    $current  = $null
    if ($cfg.PSObject.Properties.Name -contains 'copot_hash') { $current = $cfg.copot_hash }
    # Bandingkan sebagai string (null -> ""), supaya tidak menulis bila sama.
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

# --- State: { files: { path -> jumlah baris terkirim }, keadaan: string, alasan: string }.
#     keadaan: 'terkunci-pending' | 'terkunci-blokir' | 'terbuka' (deteksi transisi notif).
#     alasan : teks alasan blokir dari server (kosong untuk pending); dipakai notice.ps1.
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
                if ($obj.PSObject.Properties.Name -contains 'keadaan') {
                    $result.keadaan = [string]$obj.keadaan
                }
                if ($obj.PSObject.Properties.Name -contains 'alasan') {
                    $result.alasan = [string]$obj.alasan
                }
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

# --- Akun (user) yang sedang login. Task jalan sebagai SYSTEM, jadi ambil dari
#     sesi aktif; boleh null (mis. tidak ada yang login saat startup).
function Get-ActiveAccount {
    try {
        $u = (Get-CimInstance -ClassName Win32_ComputerSystem -ErrorAction Stop).UserName
        if ([string]::IsNullOrWhiteSpace($u)) { return $null }
        return $u
    } catch {
        return $null
    }
}

# --- Notifikasi ke sesi konsol user (SYSTEM -> msg * ke sesi aktif).
function Send-UserNotice {
    param([string]$Message)
    try {
        $msgExe = Join-Path $env:SystemRoot 'System32\msg.exe'
        if (Test-Path -LiteralPath $msgExe) {
            & $msgExe '*' '/TIME:0' $Message 2>$null
        } else {
            Write-Log 'msg.exe tidak tersedia (edisi Windows ini); notifikasi user dilewati.'
        }
    } catch {
        Write-Log "Gagal mengirim notifikasi user: $($_.Exception.Message)"
    }
}

# --- Terapkan/batalkan blokir akses Claude Code lewat hosts (api.anthropic.com).
#     Idempoten, bertanda komentar agar mudah dicari & dihapus. Web claude.ai
#     SENGAJA tidak diblok; yang dimatikan khusus akses Claude Code.
function Set-HostsBlock {
    param([bool]$On)
    try {
        $lines = @()
        if (Test-Path -LiteralPath $HostsPath) {
            $lines = @(Get-Content -LiteralPath $HostsPath -Encoding UTF8 -ErrorAction Stop)
        }
        # Buang baris lama bertanda (apa pun isinya) supaya idempoten.
        $kept = @($lines | Where-Object { $_ -notmatch [regex]::Escape($BlockMarker) })
        if ($On) {
            $kept += "0.0.0.0 api.anthropic.com $BlockMarker"
        }
        $newContent = ($kept -join "`r`n")
        $oldContent = ($lines -join "`r`n")
        if ($newContent -ne $oldContent) {
            # hosts memakai ASCII; SYSTEM berhak menulis berkas ini.
            Set-Content -LiteralPath $HostsPath -Value $kept -Encoding ASCII -ErrorAction Stop

            # Flush cache DNS HANYA saat konten berubah, agar perubahan langsung
            # berlaku untuk koneksi BARU (tanpa ini, blokir tertunda oleh cache DNS).
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
#     mis. perangkat dihapus dari dashboard): akses Claude Code ikut MATI, bukan
#     dibiarkan terbuka. Notifikasi dikirim SETIAP putaran selama terkunci.
function Set-PendingLock {
    param([hashtable]$State)
    Set-HostsBlock -On $true
    Send-UserNotice -Message 'Claude Code belum diotorisasi Tim IT untuk perangkat ini. Sedang menunggu persetujuan.'
    Write-Log 'Keadaan terkunci-pending (token ditolak server); notifikasi dikirim.'
    $State.keadaan = 'terkunci-pending'
    $State.alasan  = ''
}

# =====================================================================
#  ENROLL bila device_token masih null
# =====================================================================
$deviceToken = $null
if ($cfg.PSObject.Properties.Name -contains 'device_token') { $deviceToken = $cfg.device_token }

$account  = Get-ActiveAccount
$hostName = $env:COMPUTERNAME

if ([string]::IsNullOrWhiteSpace($deviceToken)) {
    if ([string]::IsNullOrWhiteSpace($cfg.endpoint_enroll)) {
        Write-Log 'endpoint_enroll kosong di config; tidak bisa enroll.'
        exit 0
    }
    # TANPA kunci enrollment. Perangkat baru akan "menunggu persetujuan" di MIC
    # sampai IT menekan Setujui.
    # Label AWAL: pakai alias dari config bila ada & tidak kosong, selain itu COMPUTERNAME.
    # Setelah enroll pertama, IT bisa mengganti nama perangkat dari dashboard MIC;
    # server tidak menimpa label saat re-enroll, jadi nama dashboard yang berlaku.
    $enrollLabel = $hostName
    if (($cfg.PSObject.Properties.Name -contains 'label') -and -not [string]::IsNullOrWhiteSpace($cfg.label)) {
        $enrollLabel = $cfg.label
    }
    $enrollBody = @{
        machine_id = $cfg.machine_id
        host       = $hostName
        account    = $account
        label      = $enrollLabel
    } | ConvertTo-Json -Depth 5

    try {
        $resp = Invoke-RestMethod -Uri $cfg.endpoint_enroll -Method Post `
            -Body $enrollBody -ContentType 'application/json' `
            -TimeoutSec 60 -UseBasicParsing
    } catch {
        Write-Log "Enroll gagal: $($_.Exception.Message). Coba lagi putaran berikutnya."
        exit 0
    }

    if ($resp -and $resp.success -and -not [string]::IsNullOrWhiteSpace($resp.device_token)) {
        $deviceToken = $resp.device_token
        # Simpan token (dan device_id bila ada) ke config.
        if ($cfg.PSObject.Properties.Name -contains 'device_token') {
            $cfg.device_token = $deviceToken
        } else {
            $cfg | Add-Member -NotePropertyName 'device_token' -NotePropertyValue $deviceToken -Force
        }
        if ($resp.PSObject.Properties.Name -contains 'device_id') {
            $cfg | Add-Member -NotePropertyName 'device_id' -NotePropertyValue $resp.device_id -Force
        }
        Save-Config -Cfg $cfg
        Sync-CopotHash -Resp $resp
        Write-Log 'Enroll sukses; device_token disimpan.'
    } else {
        Write-Log 'Enroll menolak (success=false atau token kosong). Coba lagi putaran berikutnya.'
        exit 0
    }
}

# =====================================================================
#  PERSIAPAN: muat state, header, variabel status terbaru dari server
# =====================================================================
$state = Load-State
$maxBatchBytes = 3.5MB

$headers = @{
    'Authorization' = "Bearer $deviceToken"
    'Content-Type'  = 'application/json'
}

# Status terbaru dari server (null jika belum ada respons).
$script:lastStatus   = $null   # "pending" | "aktif"
$script:lastBlokir   = $null
$script:lastAlasan   = ''
$script:unauthorized = $false

# --- Kirim satu batch; kembalikan $true bila sukses (HTTP 2xx), simpan status blokir.
function Send-Batch {
    param([array]$Lines)
    $payload = @{
        device_token = $deviceToken
        host         = $hostName
        account      = $account
        lines        = $Lines
    }
    $json = $payload | ConvertTo-Json -Depth 50 -Compress
    try {
        $resp = Invoke-RestMethod -Uri $cfg.endpoint_ingest -Method Post -Headers $headers `
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
        return $true
    } catch {
        $status = $null
        try { $status = [int]$_.Exception.Response.StatusCode.value__ } catch { }
        if ($status -eq 401) {
            $script:unauthorized = $true
            Write-Log 'Ingest 401 (token ditolak).'
        } else {
            Write-Log "Gagal mengirim batch ($($Lines.Count) baris), status=$status : $($_.Exception.Message)"
        }
        return $false
    }
}

# =====================================================================
#  POLL STATUS (pending/aktif) + blokir + copot_hash  (ingest lines:[])
#  Dilakukan SEBELUM kirim transkrip, agar kunci/buka hosts ditegakkan tiap
#  putaran meski perangkat masih pending.
# =====================================================================
$pollOk = Send-Batch -Lines @()

if ($script:unauthorized) {
    # HTTP 401: token ditolak (mis. perangkat DIHAPUS dari dashboard).
    # (CATATAN: status "pending" datang sebagai HTTP 200, BUKAN 401, jadi
    #  pending tidak akan sampai ke sini dan TIDAK mereset token.)
    # 1) KUNCI akses Claude Code SEKARANG (jangan dibiarkan terbuka).
    # 2) Tandai keadaan pending + notif transisi.
    Set-PendingLock -State $state
    # 3) Reset token agar putaran berikutnya enroll ulang sebagai pending.
    if ($cfg.PSObject.Properties.Name -contains 'device_token') { $cfg.device_token = $null }
    Save-Config -Cfg $cfg
    Save-State -State $state
    Write-Log 'device_token direset ke null (akan enroll ulang sebagai pending).'
    exit 0
}

if (-not $pollOk) {
    # Gagal menghubungi server (bukan 401). Jangan ubah hosts/state; coba lagi nanti.
    Write-Log 'Poll status gagal (jaringan/server). Tidak mengubah apa pun putaran ini.'
    exit 0
}

# =====================================================================
#  TENTUKAN KEADAAN + TEGAKKAN KUNCI/BUKA + NOTIFIKASI TRANSISI
#  Akses Claude Code BOLEH hanya bila status == "aktif" DAN blokir == false.
#  Tiga keadaan: 'terkunci-pending', 'terkunci-blokir', 'terbuka'.
# =====================================================================
$status = $script:lastStatus
$blokir = $script:lastBlokir

if ($status -eq 'aktif') {
    if ($blokir -eq $true) { $keadaan = 'terkunci-blokir' } else { $keadaan = 'terbuka' }
} else {
    # 'pending' atau status tak dikenal -> terkunci, menunggu persetujuan IT.
    $keadaan = 'terkunci-pending'
}

$prevKeadaan = [string]$state.keadaan
if ([string]::IsNullOrWhiteSpace($prevKeadaan)) { $prevKeadaan = 'terbuka' }

# Terapkan hosts: 'terbuka' -> buka kunci; selain itu -> kunci api.anthropic.com.
if ($keadaan -eq 'terbuka') {
    Set-HostsBlock -On $false
} else {
    Set-HostsBlock -On $true
}

# Notifikasi (msg.exe, andal dari SYSTEM -> sesi user; toast modern tidak dipakai
# karena isolasi session-0):
#   - terkunci-pending / terkunci-blokir: SETIAP putaran (ingatkan user terus).
#   - terbuka: HANYA saat transisi dari keadaan terkunci (jangan ganggu saat kerja).
switch ($keadaan) {
    'terkunci-pending' {
        Send-UserNotice -Message 'Claude Code belum diotorisasi Tim IT untuk perangkat ini. Sedang menunggu persetujuan.'
        Write-Log 'Keadaan terkunci-pending; notifikasi dikirim.'
    }
    'terkunci-blokir' {
        $msg = 'Akses Claude Code dinonaktifkan sementara oleh Tim IT.'
        if (-not [string]::IsNullOrWhiteSpace($script:lastAlasan)) {
            $msg = "$msg Alasan: $($script:lastAlasan)"
        }
        Send-UserNotice -Message $msg
        Write-Log 'Keadaan terkunci-blokir; notifikasi dikirim.'
    }
    'terbuka' {
        if ($prevKeadaan -ne 'terbuka') {
            Send-UserNotice -Message 'Akses Claude Code telah dipulihkan oleh Tim IT.'
            Write-Log 'Keadaan -> terbuka (akses dipulihkan); notifikasi dikirim.'
        }
    }
}

$state.keadaan = $keadaan
if ($keadaan -eq 'terkunci-blokir') {
    $state.alasan = [string]$script:lastAlasan
} else {
    $state.alasan = ''
}

# =====================================================================
#  BELUM DISETUJUI (status != aktif): JANGAN kirim transkrip, JANGAN majukan
#  penanda state, JANGAN reset token. Tunggu putaran berikutnya.
# =====================================================================
if ($status -ne 'aktif') {
    Write-Log 'Menunggu persetujuan IT.'
    Save-State -State $state
    exit 0
}

# =====================================================================
#  AKTIF -> KIRIM TRANSKRIP (profil TIAP user)
#  C:\Users\*\.claude\projects\*\*.jsonl
# =====================================================================
$files = @()
try {
    $usersRoot = Join-Path $env:SystemDrive 'Users'
    $files = Get-ChildItem -Path (Join-Path $usersRoot '*\.claude\projects\*\*.jsonl') `
        -File -ErrorAction SilentlyContinue
} catch {
    Write-Log "Gagal menelusuri transkrip: $($_.Exception.Message)"
}

foreach ($file in $files) {
    if ($script:unauthorized) { break }

    $path = $file.FullName

    $already = 0
    if ($state.files.ContainsKey($path)) { $already = [int]$state.files[$path] }

    try {
        $allLines = Get-Content -LiteralPath $path -Encoding UTF8 -ErrorAction Stop
    } catch {
        # Folder/berkas tak terbaca (mis. profil user lain terkunci) -> lewati.
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
        # Jangan majukan penanda: baris dikirim ulang putaran berikutnya.
        Write-Log "Berkas '$path': kirim gagal, penanda tetap di $already dari $total."
    } else {
        $state.files[$path] = $total
    }
}

# --- Token ditolak saat kirim transkrip (mis. perangkat dihapus di tengah jalan):
#     KUNCI akses, tandai pending, lalu reset token agar enroll ulang sebagai pending.
if ($script:unauthorized) {
    Set-PendingLock -State $state
    if ($cfg.PSObject.Properties.Name -contains 'device_token') { $cfg.device_token = $null }
    Save-Config -Cfg $cfg
    Save-State -State $state
    Write-Log 'device_token direset ke null (akan enroll ulang sebagai pending).'
    exit 0
}

Save-State -State $state
Write-Log 'Selesai satu putaran kirim.'
exit 0
