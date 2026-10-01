<#
    WBL AI Monitor - pasang.ps1  (DIKELOLA IT)
    ==========================================
    Pemasang untuk fitur "Pemantauan AI" (PT WBL / MIC) pada laptop kerja milik
    kantor. Dijalankan OLEH TIM IT, WAJIB sebagai Administrator.

    Model baru (IT-managed, tetap TERBUKA):
      - Program disalin ke  C:\Program Files\WBL-AiMonitor\  (hanya admin boleh ubah/hapus).
      - Data/konfig/log di  C:\ProgramData\WBL-AiMonitor\     (user biasa: baca saja).
      - Scheduled Task "WBL AI Monitor" berjalan sebagai SYSTEM (RunLevel Highest),
        tiap 30 menit + saat startup. Task dibuat admin -> user biasa tak bisa unregister.
      - Task TETAP TERLIHAT di Task Scheduler. Pemberitahuan dicetak terbuka.

    Yang TIDAK dilakukan (by design, supaya tak menyerupai malware):
      - TIDAK ada mekanisme self-healing / pasang-ulang-diri bila dihapus.
        Untuk "selalu terpasang", dorong lewat GPO/Intune/MDM (lihat README.txt).
      - TIDAK menyembunyikan diri, TIDAK membaca berkas lain selain transkrip *.jsonl.
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$Endpoint,     # URL enroll, mis: https://<host>/mall-intelligence-center/public/index.php/api/ai-monitor/enroll

    [Parameter(Mandatory = $true)]
    [string]$EnrollKey,    # kunci enrollment rahasia dari menu "Perangkat & Token" di MIC

    [Parameter(Mandatory = $false)]
    [string]$Label         # alias perangkat (opsional); label AWAL saat enroll pertama
)

$ErrorActionPreference = 'Stop'

# =====================================================================
#  0) WAJIB ADMINISTRATOR
# =====================================================================
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltinRole]::Administrator)
if (-not $isAdmin) {
    Write-Host 'GAGAL: pasang.ps1 harus dijalankan sebagai Administrator.' -ForegroundColor Red
    Write-Host 'Buka PowerShell dengan "Run as administrator", lalu ulangi.' -ForegroundColor Yellow
    exit 1
}

# --- Lokasi tetap (IT-managed).
$ProgramDir = Join-Path $env:ProgramFiles 'WBL-AiMonitor'
$DataDir    = Join-Path $env:ProgramData  'WBL-AiMonitor'
$ConfigPath = Join-Path $DataDir 'config.json'
$ScriptDir  = Split-Path -Parent $PSCommandPath
$KirimSrc   = Join-Path $ScriptDir 'kirim.ps1'
$KirimDst   = Join-Path $ProgramDir 'kirim.ps1'
$TaskName   = 'WBL AI Monitor'

# =====================================================================
#  1) PEMBERITAHUAN TERBUKA (dicetak; tidak perlu Enter karena dipasang IT)
# =====================================================================
Write-Host ''
Write-Host '==================================================================' -ForegroundColor Cyan
Write-Host '   PEMBERITAHUAN - PEMANTAUAN AI (PT WBL, dikelola IT)' -ForegroundColor Cyan
Write-Host '==================================================================' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Laptop ini adalah ASET KANTOR yang dikelola tim IT PT WBL. Pemakaian'
Write-Host 'Claude Code di perangkat ini DICATAT dan DIKIRIM ke server MIC untuk'
Write-Host 'keperluan audit dan peningkatan mutu. Pemakaian AI telah diberitahukan'
Write-Host 'ke tim secara tertulis.'
Write-Host ''
Write-Host 'Yang dikirim:'
Write-Host '  - Baris transkrip sesi Claude Code (berkas *.jsonl) dari profil tiap user.'
Write-Host ''
Write-Host 'Yang TIDAK dilakukan:'
Write-Host '  - Tidak membaca/mengirim berkas lain di laptop selain transkrip *.jsonl.'
Write-Host '  - Tidak menghapus atau mengubah berkas transkrip.'
Write-Host '  - Tidak menyembunyikan diri: task TERLIHAT di Task Scheduler.'
Write-Host '  - Tidak ada self-healing: bila dihapus, tidak memasang ulang diri sendiri.'
Write-Host ''
Write-Host 'Privasi: kata sandi/token yang mungkin muncul di transkrip DISAMARKAN'
Write-Host 'oleh server MIC sebelum disimpan (penyamaran dilakukan di server).'
Write-Host ''
Write-Host 'Kendali: akses Claude Code dapat distop oleh IT dari dashboard MIC'
Write-Host '(dengan memblokir api.anthropic.com di perangkat ini; web claude.ai'
Write-Host 'dibiarkan). Agen hanya dapat DICOPOT oleh IT (Administrator) lewat'
Write-Host 'copot.ps1 dan HARUS memasukkan password IT. User biasa tidak dapat'
Write-Host 'mencopot, meng-End Task, atau mengubah berkasnya.'
Write-Host ''

# =====================================================================
#  2) BUAT FOLDER PROGRAM & DATA, SALIN kirim.ps1
# =====================================================================
if (-not (Test-Path -LiteralPath $KirimSrc)) {
    Write-Error "Tidak menemukan kirim.ps1 di '$ScriptDir'. Pastikan berkas satu folder."
    exit 1
}

New-Item -ItemType Directory -Path $ProgramDir -Force | Out-Null
New-Item -ItemType Directory -Path $DataDir    -Force | Out-Null

Copy-Item -LiteralPath $KirimSrc -Destination $KirimDst -Force
Write-Host "Program disalin ke: $KirimDst" -ForegroundColor Green

# =====================================================================
#  3) machine_id (MachineGuid; fallback: nama komputer + serial BIOS)
# =====================================================================
$machineId = $null
try {
    $machineId = (Get-ItemProperty -Path 'HKLM:\SOFTWARE\Microsoft\Cryptography' -Name 'MachineGuid' -ErrorAction Stop).MachineGuid
} catch {
    $machineId = $null
}
if ([string]::IsNullOrWhiteSpace($machineId)) {
    $serial = $null
    try { $serial = (Get-CimInstance -ClassName Win32_BIOS -ErrorAction Stop).SerialNumber } catch { $serial = 'UNKNOWN' }
    $machineId = "$env:COMPUTERNAME-$serial"
}

# =====================================================================
#  4) config.json  (endpoint_ingest diturunkan dari endpoint_enroll)
# =====================================================================
if ($Endpoint -match '/enroll/?$') {
    $endpointIngest = $Endpoint -replace '/enroll/?$', '/ingest'
} else {
    # fallback bila pola /enroll tidak di akhir URL
    $endpointIngest = $Endpoint.Replace('/enroll', '/ingest')
}

# copot_hash TIDAK ditulis di sini. Password copot dikelola terpusat di MIC dan
# dikirim server lewat respons enroll/ingest; kirim.ps1 yang menyimpannya ke config.
$config = [ordered]@{
    endpoint_enroll = $Endpoint
    endpoint_ingest = $endpointIngest
    enroll_key      = $EnrollKey
    machine_id      = $machineId
    device_token    = $null
    copot_hash      = $null
}
# Alias perangkat opsional. Bila tidak diisi, biarkan tak di-set agar kirim.ps1
# memakai default COMPUTERNAME saat enroll.
if (-not [string]::IsNullOrWhiteSpace($Label)) {
    $config['label'] = $Label
}
($config | ConvertTo-Json -Depth 5) | Set-Content -LiteralPath $ConfigPath -Encoding UTF8
Write-Host "Config disimpan di: $ConfigPath" -ForegroundColor Green

# =====================================================================
#  5) ACL folder data: SYSTEM + Administrators = Full; Users = ReadAndExecute
#     (pakai SID well-known supaya tahan beda bahasa Windows)
# =====================================================================
try {
    $sidSystem = New-Object System.Security.Principal.SecurityIdentifier('S-1-5-18')       # NT AUTHORITY\SYSTEM
    $sidAdmins = New-Object System.Security.Principal.SecurityIdentifier('S-1-5-32-544')   # BUILTIN\Administrators
    $sidUsers  = New-Object System.Security.Principal.SecurityIdentifier('S-1-5-32-545')   # BUILTIN\Users

    $inherit     = [System.Security.AccessControl.InheritanceFlags]'ContainerInherit, ObjectInherit'
    $propagation = [System.Security.AccessControl.PropagationFlags]::None
    $allow       = [System.Security.AccessControl.AccessControlType]::Allow

    $acl = Get-Acl -LiteralPath $DataDir
    # Matikan pewarisan dan buang aturan warisan supaya ACL kita yang berlaku.
    $acl.SetAccessRuleProtection($true, $false)
    foreach ($rule in @($acl.Access)) {
        [void]$acl.RemoveAccessRule($rule)
    }

    $ruleSystem = New-Object System.Security.AccessControl.FileSystemAccessRule($sidSystem, 'FullControl',   $inherit, $propagation, $allow)
    $ruleAdmins = New-Object System.Security.AccessControl.FileSystemAccessRule($sidAdmins, 'FullControl',   $inherit, $propagation, $allow)
    $ruleUsers  = New-Object System.Security.AccessControl.FileSystemAccessRule($sidUsers,  'ReadAndExecute',$inherit, $propagation, $allow)

    [void]$acl.AddAccessRule($ruleSystem)
    [void]$acl.AddAccessRule($ruleAdmins)
    [void]$acl.AddAccessRule($ruleUsers)

    Set-Acl -LiteralPath $DataDir -AclObject $acl
    Write-Host 'ACL folder data diterapkan: SYSTEM/Administrators = Full, Users = baca saja.' -ForegroundColor Green
} catch {
    Write-Host "Peringatan: gagal menerapkan ACL data ($($_.Exception.Message))." -ForegroundColor Yellow
    Write-Host 'Lanjut; tetapi periksa izin folder secara manual.' -ForegroundColor Yellow
}

# =====================================================================
#  6) DAFTARKAN SCHEDULED TASK sebagai SYSTEM (RunLevel Highest)
#     Trigger: tiap 30 menit + saat startup. TERLIHAT di Task Scheduler.
# =====================================================================
$psExe  = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
$action = New-ScheduledTaskAction -Execute $psExe `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$KirimDst`""

# Trigger 1: sekali sekarang, lalu berulang tiap 30 menit tanpa batas.
$triggerRepeat  = New-ScheduledTaskTrigger -Once -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 30)
# Trigger 2: saat startup (agar jalan walau belum ada yang login).
$triggerStartup = New-ScheduledTaskTrigger -AtStartup

$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' `
    -LogonType ServiceAccount -RunLevel Highest

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 10)

Register-ScheduledTask -TaskName $TaskName `
    -Action $action -Trigger @($triggerRepeat, $triggerStartup) `
    -Principal $principal -Settings $settings `
    -Description 'Pemantauan AI (PT WBL/MIC): mengirim transkrip Claude Code ke server MIC. Dikelola IT, berjalan sebagai SYSTEM. Hanya Administrator yang dapat mencopot (copot.ps1).' `
    -Force | Out-Null

Write-Host "Scheduled Task '$TaskName' didaftarkan (SYSTEM, tiap 30 menit + startup)." -ForegroundColor Green

# =====================================================================
#  7) PUTARAN PERDANA (enroll + kirim pertama supaya langsung muncul di dashboard)
# =====================================================================
try {
    Start-ScheduledTask -TaskName $TaskName
    Write-Host 'Putaran perdana dijalankan (enroll + kirim pertama).' -ForegroundColor Green
} catch {
    Write-Host "Catatan: gagal menjalankan task perdana ($($_.Exception.Message)). Akan jalan otomatis." -ForegroundColor Yellow
}

# =====================================================================
#  RINGKASAN
# =====================================================================
Write-Host ''
Write-Host '==================================================================' -ForegroundColor Cyan
Write-Host '   RINGKASAN PEMASANGAN' -ForegroundColor Cyan
Write-Host '==================================================================' -ForegroundColor Cyan
Write-Host "  Program : $KirimDst"
Write-Host "  Data    : $DataDir  (config.json, state.json, kirim.log)"
Write-Host "  Task    : $TaskName  (principal SYSTEM, RunLevel Highest)"
Write-Host ''
Write-Host 'Agen hanya dapat DICOPOT oleh Administrator + password IT:' -ForegroundColor Yellow
Write-Host '    powershell -ExecutionPolicy Bypass -File copot.ps1   (Run as administrator)' -ForegroundColor White
Write-Host '    (copot.ps1 akan menanyakan password IT)'
Write-Host ''
Write-Host 'Password copot DIATUR TERPUSAT di MIC (menu Perangkat & Token, khusus admin),'
Write-Host 'bukan di sini. Server mengirimkan hash-nya lewat enroll/ingest, dan laptop'
Write-Host 'akan tersinkron pada laporan berikutnya. Sebelum tersinkron, copot.ps1 akan'
Write-Host 'menolak karena password copot belum ada di perangkat.'
Write-Host ''
Write-Host 'User biasa TIDAK dapat menghapus task, meng-End Task proses SYSTEM, maupun'
Write-Host 'mengubah berkasnya (ACL & principal SYSTEM RunLevel Highest).'
Write-Host 'Catatan jujur: seorang Administrator lokal secara teknis tetap bisa'
Write-Host 'menghentikannya. Untuk pasang-ulang terpusat, gunakan GPO/Intune/MDM.'
Write-Host 'Task tetap terlihat di Task Scheduler Library dengan nama "WBL AI Monitor".'
Write-Host ''
exit 0
