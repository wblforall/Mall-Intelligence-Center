================================================================
  WBL AI Monitor - Pemantauan AI (sisi laptop Windows)
  AGEN YANG DIKELOLA IT - Untuk tim IT PT WBL / MIC
================================================================

Fitur ini mengirim catatan pemakaian Claude Code dari laptop kerja (aset
kantor) ke server MIC. Agen DIKELOLA IT: dipasang oleh Administrator, berjalan
sebagai SYSTEM, dan hanya dapat dicopot oleh IT. Tetap TERBUKA: task terlihat di
Task Scheduler dan pemakaian AI diberitahukan ke tim secara tertulis.


----------------------------------------------------------------
1. PRINSIP: ASET KANTOR, DIKELOLA IT, TETAP TERBUKA
----------------------------------------------------------------
- Laptop adalah ASET KANTOR yang dikelola IT. Pemakaian AI (Claude Code) boleh
  dipantau untuk audit dan peningkatan mutu.
- Pemantauan WAJIB diberitahukan ke tim secara tertulis sebelum dipakai.
- Agen tidak menyembunyikan diri: terdaftar sebagai Scheduled Task bernama
  "WBL AI Monitor" yang terlihat di Task Scheduler.
- Agen boleh dibuat "hanya-IT-yang-bisa-mencopot", TETAPI tetap terlihat dan
  diberitahukan. Ini pengelolaan aset kantor, bukan penyusupan diam-diam.
- TIDAK ada mekanisme self-healing / resurrect-diri. Bila dihapus, agen TIDAK
  memasang ulang dirinya sendiri (itu perilaku malware dan sengaja dihindari).
  Untuk "selalu terpasang", dorong lewat GPO/Intune/MDM (lihat bagian 9).

Contoh kalimat edaran tertulis ke tim:

  "Rekan-rekan, laptop kerja adalah aset kantor yang dikelola IT. Mulai pekan
   ini IT mengaktifkan 'Pemantauan AI': transkrip sesi Claude Code (isi
   pekerjaan AI) dikirim ke server MIC untuk audit dan peningkatan mutu. Alat
   ini TIDAK membaca berkas lain di laptop, terlihat di Task Scheduler sebagai
   'WBL AI Monitor', dan password/token yang mungkin muncul akan disamarkan di
   server. Bila perlu, IT dapat menonaktifkan akses Claude Code di perangkat
   dari dashboard. Pertanyaan: hubungi IT."


----------------------------------------------------------------
2. APA YANG DIPANTAU DAN APA YANG TIDAK
----------------------------------------------------------------
DIPANTAU / DIKIRIM:
  - Baris transkrip sesi Claude Code (*.jsonl) dari profil TIAP user:
        C:\Users\<user>\.claude\projects\<slug-proyek>\<uuid-sesi>.jsonl
    Baris dikirim apa adanya (mentah); server MIC yang mengurai & menyimpan.

TIDAK DISENTUH / TIDAK DIKIRIM:
  - Berkas lain apa pun di laptop (dokumen, email, foto, kredensial sistem)
    TIDAK dibaca dan TIDAK dikirim.
  - Berkas transkrip TIDAK PERNAH dihapus atau diubah.

PRIVASI:
  - Kata sandi / token yang mungkin ikut tercatat DISAMARKAN oleh server MIC
    sebelum disimpan (penyamaran di server, bukan di laptop).


----------------------------------------------------------------
3. MODEL KEAMANAN (IT-MANAGED)
----------------------------------------------------------------
- Program:  C:\Program Files\WBL-AiMonitor\   (kirim.ps1). Hanya Administrator
  yang bisa menulis/menghapus (ACL default Program Files).
- Data:     C:\ProgramData\WBL-AiMonitor\     (config.json, state.json, kirim.log).
  ACL diset: SYSTEM + Administrators = FullControl; BUILTIN\Users =
  ReadAndExecute saja. User biasa bisa membaca (transparan), tak bisa ubah/hapus.
- Scheduled Task "WBL AI Monitor": principal SYSTEM, RunLevel Highest, berjalan
  tiap 30 menit + saat startup. Karena dibuat admin & berjalan sebagai SYSTEM:
    * User biasa (non-admin) TIDAK bisa unregister task-nya.
    * User biasa TIDAK bisa End Task proses SYSTEM di Task Manager (akses ditolak).
    * Task diset -RestartCount 3 -RestartInterval 1 menit, AllowStartIfOnBatteries,
      DontStopIfGoingOnBatteries, StartWhenAvailable -> andal dan sulit dimatikan
      oleh user biasa.
- Copot hanya via copot.ps1 SEBAGAI ADMINISTRATOR dan WAJIB password IT (lihat
  bagian 8). Password copot DIKELOLA TERPUSAT di dashboard MIC (khusus admin);
  server mengirim HASH sha256-nya lewat respons enroll/ingest, dan kirim.ps1
  menyimpannya ke config.json. Password mentah TIDAK pernah disimpan di laptop,
  dan pasang.ps1 TIDAK lagi memintanya saat pemasangan.

CATATAN JUJUR (penting):
  Perlindungan ini KUAT terhadap user biasa, tetapi BUKAN "tak bisa dimatikan
  siapa pun". Seorang Administrator lokal secara teknis tetap bisa menghentikan
  atau menghapusnya. Kami TIDAK mengklaim kekebalan penuh. Bila user punya hak
  admin lokal di perangkatnya, pertimbangkan mencabut hak itu dan/atau memakai
  GPO/Intune untuk memasang ulang secara terpusat (bagian 9).


----------------------------------------------------------------
4. PERSETUJUAN IT & PENGUNCIAN AKSES CLAUDE CODE
----------------------------------------------------------------
TANPA kunci enrollment. Perangkat baru yang di-enroll langsung berstatus
"Menunggu persetujuan" di dashboard MIC. Respons enroll & ingest dari server
memuat:
  - "status": "pending" | "aktif"
  - "blokir": true|false (opsional "blokir_alasan")
  - "copot_hash": sha256 password copot (atau null) -> disimpan ke config.json.

ATURAN AKSES (ditegakkan agen tiap putaran, SEBELUM kirim transkrip):
  Akses Claude Code BOLEH hanya bila  status == "aktif" DAN blokir == false.
  Selain itu, akses DIKUNCI dengan menambahkan ke berkas hosts Windows:
        0.0.0.0 api.anthropic.com   # WBL-AiMonitor BLOCK
  Ini mematikan KHUSUS akses Claude Code (api.anthropic.com). Web app claude.ai
  SENGAJA tidak diblok.

TIGA KEADAAN (notifikasi msg.exe hanya saat BERPINDAH keadaan, agar tak spam;
keadaan terakhir disimpan di state.json):
  - terkunci-pending (status pending / belum disetujui / dinonaktifkan dari
    daftar): hosts DIKUNCI. Transkrip TIDAK diupload, penanda state TIDAK maju,
    token TIDAK direset. Notif saat masuk keadaan ini:
        "Akses Claude Code pada perangkat ini terkunci. Perangkat menunggu
         persetujuan Tim IT."
  - terkunci-blokir (status aktif TAPI blokir==true): hosts DIKUNCI, transkrip
    tetap diupload. Notif saat masuk keadaan ini:
        "Akses Claude Code pada perangkat ini dinonaktifkan sementara oleh Tim
         IT. Hubungi IT untuk informasi lebih lanjut."  (+ alasan bila ada)
  - terbuka (status aktif DAN blokir==false): hosts DIBUKA (baris bertanda
    dihapus), transkrip diupload normal. Notif saat pulih dari keadaan terkunci:
        "Akses Claude Code telah dipulihkan oleh Tim IT."

Catatan: pengunciaan hosts tetap ditegakkan walau perangkat masih pending
(status diambil dari poll ingest lines:[] di awal tiap putaran). Semua baris
bertanda "# WBL-AiMonitor BLOCK" otomatis dibersihkan saat agen dicopot.


----------------------------------------------------------------
5. ENDPOINT & MODEL PERSETUJUAN (TANPA KUNCI)
----------------------------------------------------------------
TANPA kunci enrollment. Yang diperlukan hanya Endpoint enroll MIC, contoh:
      https://mic.wbl-bsb.com/api/ai-monitor/enroll
(sudah terisi di pengaturan.txt untuk pemasangan via KLIK-PASANG).

Endpoint ingest diturunkan otomatis dari endpoint enroll (ganti "/enroll" jadi
"/ingest"). Agen melakukan enroll sendiri pada putaran pertama dan menerima
device_token dari server (disimpan di config.json). Tidak perlu menyalin token
atau kunci per laptop.

PENGAMANAN: karena tak ada kunci, perangkat baru TIDAK langsung aktif. Ia muncul
di dashboard MIC (menu Pemantauan AI > Perangkat) sebagai "Menunggu persetujuan",
dan akses Claude Code-nya terkunci sampai IT menekan "Setujui". IT memverifikasi
perangkat (nama, host, akun) sebelum menyetujui.


----------------------------------------------------------------
6. CARA MEMASANG (SEBAGAI ADMINISTRATOR)
----------------------------------------------------------------
Cara termudah: salin folder pemasang (pengaturan.txt, pasang.ps1, kirim.ps1,
pasang-klik.ps1, KLIK-PASANG.bat) ke laptop target, lalu klik kanan
KLIK-PASANG.bat > "Run as administrator". ENDPOINT sudah terisi di
pengaturan.txt; LABEL opsional.

Cara manual (PowerShell "Run as administrator" di folder itu):

  powershell -ExecutionPolicy Bypass -File pasang.ps1 `
    -Endpoint "https://mic.wbl-bsb.com/api/ai-monitor/enroll"

Hanya -Endpoint yang WAJIB. TIDAK ADA -EnrollKey (model tanpa kunci) dan
password copot TIDAK diisi di sini (dikelola terpusat di MIC, lihat bagian 8).

Opsional -Label: alias perangkat, berguna bila nama komputer acak. Contoh:

  powershell -ExecutionPolicy Bypass -File pasang.ps1 `
    -Endpoint "https://mic.wbl-bsb.com/api/ai-monitor/enroll" `
    -Label "Laptop Budi - IT"

Catatan: -Label hanya label AWAL saat enroll pertama. Setelah itu IT dapat
mengganti nama perangkat dari dashboard MIC, dan nama dashboard itulah yang
berlaku (server tidak menimpa label saat re-enroll). Bila -Label tidak diberikan,
label awal memakai COMPUTERNAME.

Pemasang akan:
  1. Memastikan dijalankan sebagai Administrator (kalau tidak, berhenti).
  2. Mencetak pemberitahuan terbuka (tanpa minta Enter, karena dipasang IT).
  3. Membuat C:\Program Files\WBL-AiMonitor dan C:\ProgramData\WBL-AiMonitor,
     menyalin HANYA kirim.ps1 ke Program Files (copot.ps1 TIDAK disalin).
  4. Menulis config.json (endpoint_enroll, endpoint_ingest, machine_id,
     device_token=null, copot_hash=null, label bila -Label diisi). TANPA
     enroll_key. copot_hash akan diisi server lewat enroll/ingest berikutnya.
  5. Menerapkan ACL folder data (SYSTEM/Admin=Full, Users=baca saja).
  6. Mendaftarkan Scheduled Task SYSTEM (RunLevel Highest, tiap 30 menit +
     startup, RestartCount 3) dan menjalankan putaran perdana (enroll).

Setelah pasang, perangkat MENUNGGU PERSETUJUAN di MIC (akses terkunci sampai IT
Setujui). Folder pemasang di laptop boleh dihapus setelah pasang (agen sudah di
C:\Program Files\WBL-AiMonitor).


----------------------------------------------------------------
7. CARA MENGECEK
----------------------------------------------------------------
  Get-ScheduledTask     -TaskName "WBL AI Monitor"
  Get-ScheduledTaskInfo -TaskName "WBL AI Monitor"      (waktu jalan terakhir)
  Start-ScheduledTask   -TaskName "WBL AI Monitor"      (uji manual sekali)

GUI: Task Scheduler -> Task Scheduler Library -> "WBL AI Monitor".

Log & state (Administrator):
  C:\ProgramData\WBL-AiMonitor\kirim.log    (catatan enroll/status/kunci/kirim/galat)
  C:\ProgramData\WBL-AiMonitor\state.json   (penanda baris terkirim + keadaan kunci)
  C:\ProgramData\WBL-AiMonitor\config.json  (endpoint, device_token, copot_hash; tanpa enroll_key)


----------------------------------------------------------------
8. CARA MENCOPOT (ADMINISTRATOR + PASSWORD IT) - PERKAKAS MILIK IT
----------------------------------------------------------------
PENTING: copot.ps1 dan KLIK-COPOT.bat adalah PERKAKAS MILIK IT. Keduanya TIDAK
ikut dibagikan ke laptop anggota tim dan TIDAK ditinggal di mesin. pasang.ps1
hanya menyalin kirim.ps1 ke C:\Program Files\WBL-AiMonitor (copot.ps1 tidak
pernah disalin ke perangkat). Untuk mencopot, IT MEMBAWA/mengunduh berkas copot
saat diperlukan, jalankan di mesin target sebagai Administrator, dan masukkan
password copot (dari MIC). Dengan begitu user tidak bisa mencopot sendiri hanya
karena menemukan berkas di laptopnya.

Buka PowerShell "Run as administrator" di lokasi copot.ps1 (yang dibawa IT),
lalu:

  powershell -ExecutionPolicy Bypass -File copot.ps1

Password copot diatur TERPUSAT di dashboard MIC (menu Perangkat & Token, khusus
admin). Server mengirim hash sha256-nya ke laptop lewat enroll/ingest, lalu
kirim.ps1 menyimpannya ke config.json di perangkat untuk validasi offline.

copot.ps1 akan:
  - Menolak bila bukan Administrator.
  - Membaca copot_hash dari config.json. Bila kosong/null -> ditolak:
      "Password copot belum diatur di MIC atau belum tersinkron ke perangkat ini.
       Atur di dashboard MIC lalu tunggu laptop melapor, baru copot." (exit 1)
  - Menanyakan password IT, menghitung sha256 HEX lowercase dari bytes UTF-8
    (cocok dengan PHP hash('sha256',$pw)), membandingkan dengan copot_hash.
    Bila tidak cocok -> dibatalkan (exit 1).
  - Menghapus Scheduled Task, folder Program Files & ProgramData, dan
    membersihkan baris "# WBL-AiMonitor BLOCK" dari hosts.
  - TIDAK menyentuh berkas transkrip Claude Code milik pengguna.

User biasa tanpa hak admin TIDAK bisa menjalankan ini. Bila password copot belum
diatur/tersinkron, atur dulu di MIC dan tunggu laptop melapor. Bila terpaksa,
pencopotan dapat dilakukan manual oleh admin (hapus task, folder, dan baris
hosts bertanda).


----------------------------------------------------------------
9. DEPLOY MASSAL (GPO / INTUNE / MDM)
----------------------------------------------------------------
Agar agen "selalu terpasang" tanpa membuat mekanisme self-healing di dalam
agen (yang akan menyerupai malware), gunakan kebijakan terpusat yang memasang
ulang sendiri:

  - GPO: startup script / scheduled task via Group Policy yang menjalankan
    pasang.ps1 dengan parameter dari variabel kebijakan.
  - Intune / MDM: paket Win32 (atau PowerShell script policy) yang mem-push
    folder ai-monitor\ dan menjalankan pasang.ps1; set deteksi + remediasi
    sehingga bila agen hilang, MDM memasangnya ulang pada siklus berikutnya.

Dengan cara ini pemasangan-ulang dilakukan oleh sistem manajemen perangkat yang
sah, BUKAN oleh agen yang "menghidupkan diri". Ini sengaja: agar tak menyerupai
malware, dan perilakunya tetap jujur dan dapat diaudit.


----------------------------------------------------------------
10. ISI FOLDER
----------------------------------------------------------------
PAKET PEMASANG (boleh dibagikan ke laptop target, boleh dihapus setelah pasang):
  pengaturan.txt    - ENDPOINT (terisi) + LABEL (opsional); TANPA kunci
  KLIK-PASANG.bat   - klik kanan > Run as administrator untuk memasang
  pasang-klik.ps1   - baca pengaturan.txt, panggil pasang.ps1 (tanpa -EnrollKey)
  pasang.ps1        - pemasang (admin; notifikasi + salin kirim.ps1 + ACL + task SYSTEM)
  kirim.ps1         - agen pengirim (jalan sebagai SYSTEM; enroll + status + kunci + kirim)
  README.txt        - dokumen ini
  Edaran-Pemantauan-AI.docx - contoh edaran tertulis untuk tim

PERKAKAS IT (JANGAN dibagikan ke laptop tim; simpan di tempat IT saja, bawa saat
perlu mencopot):
  copot.ps1         - pencopot (admin + password IT; hapus task/folder + bersihkan hosts)
  KLIK-COPOT.bat    - pembungkus copot.ps1 (Run as administrator)

Catatan: pasang.ps1 hanya menyalin kirim.ps1 ke C:\Program Files\WBL-AiMonitor.
copot.ps1/KLIK-COPOT.bat TIDAK pernah disalin/ditinggal di mesin target.
