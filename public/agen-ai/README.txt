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
    Isi baris dikirim sebagai BASE64 (field `enc`) lalu server MIC men-decode,
    mengurai, dan menyimpan. Base64 dipakai bukan untuk menyandikan rahasia,
    melainkan agar body OPAQUE sehingga tidak salah-blokir oleh WAF/ModSecurity
    hosting (yang mengira isi prompt berisi kode/SQL/shell berbahaya).

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
- Program:  C:\Program Files\WBL-AiMonitor\   (kirim.ps1 + notice.ps1). Hanya
  Administrator yang bisa menulis/menghapus (ACL default Program Files).
- Data:     C:\ProgramData\WBL-AiMonitor\     (config.json, state.json, kirim.log).
  ACL diset: SYSTEM + Administrators = FullControl; BUILTIN\Users =
  ReadAndExecute saja. User biasa bisa membaca (transparan), tak bisa ubah/hapus.
- DUA Scheduled Task:
    * "WBL AI Monitor" (PENEGAK): principal SYSTEM, RunLevel Highest. kirim.ps1
      memakai LOOP INTERNAL (tiap `interval_detik`, default 30, bisa 15) supaya
      approve/blokir/buka terasa dalam HITUNGAN DETIK. Task bertindak sebagai
      WATCHDOG: trigger startup + cek tiap 1 menit dengan MultipleInstances=
      IgnoreNew dan ExecutionTimeLimit=0 -> bila loop mati, dihidupkan lagi < 1
      menit; bila masih hidup, trigger diabaikan. Dibuat admin & jalan sebagai
      SYSTEM, sehingga user biasa TIDAK bisa unregister maupun End Task prosesnya.
    * "WBL AI Monitor Notice" (NOTIFIER, kosmetik): konteks user (grup
      BUILTIN\Users, RunLevel Limited), trigger AtLogOn, MultipleInstances=IgnoreNew.
      SENGAJA di sesi user agar bisa menampilkan toast. BOLEH dimatikan user;
      enforcement tetap di task SYSTEM. Mematikan notifier TIDAK membuka akses.
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

DUA LAPIS (penegak vs notifier):
  - PENEGAK (task SYSTEM "WBL AI Monitor" / kirim.ps1): mengunci hosts +
    ipconfig /flushdns. Inilah yang benar-benar memutus akses. Tak bisa dimatikan
    user biasa.
  - NOTIFIER (task user "WBL AI Monitor Notice" / notice.ps1): KOSMETIK. Berjalan
    di sesi user, memantau state.json, dan saat user MEMAKAI Claude Code sementara
    akses terkunci (pending/blokir) ia memunculkan notifikasi (toast BurntToast
    bila ada, lalu balloon NotifyIcon, fallback msg.exe) dengan debounce 60 detik.
    Notifier BOLEH dimatikan user; bila dimatikan, PENEGAK SYSTEM tetap jalan dan
    akses tetap terkunci.

NOTIFIKASI DARI PENEGAK: kirim.ps1 (SYSTEM) juga memakai msg.exe (andal dari
SYSTEM ke sesi user; toast modern tidak andal dari SYSTEM karena isolasi
session-0). Saat TERKUNCI, notifikasi diulang dengan DEBOUNCE berbasis waktu
(maksimal sekali tiap ~5 menit, timestamp di state.json) supaya tidak spam
meski loop berjalan tiap ~30 detik. Saat PULIH, notifikasi hanya sekali (saat
transisi) agar tak mengganggu kerja. Keadaan (keadaan + alasan + notif_ts)
disimpan di state.json.

Pesan notifier sesi-user saat user mencoba memakai Claude Code:
  - pending: "Akses Claude Code belum diizinkan Tim IT untuk perangkat ini
    (menunggu persetujuan)."
  - blokir : "Akses Claude Code dinonaktifkan oleh Tim IT." (+ " Alasan: <alasan>"
    bila ada).

TIGA KEADAAN:
  - terkunci-pending (status pending / belum disetujui / token ditolak /
    dinonaktifkan dari daftar): hosts DIKUNCI. Transkrip TIDAK diupload, penanda
    state TIDAK maju, token TIDAK direset. Notif SETIAP putaran:
        "Claude Code belum diotorisasi Tim IT untuk perangkat ini. Sedang
         menunggu persetujuan."
  - terkunci-blokir (status aktif TAPI blokir==true): hosts DIKUNCI, transkrip
    tetap diupload. Notif SETIAP putaran:
        "Akses Claude Code dinonaktifkan sementara oleh Tim IT."  (+ " Alasan:
         <alasan>" bila ada)
  - terbuka (status aktif DAN blokir==false): hosts DIBUKA (baris bertanda
    dihapus), transkrip diupload normal. Notif HANYA saat pulih dari keadaan
    terkunci:
        "Akses Claude Code telah dipulihkan oleh Tim IT."

Catatan: penguncian hosts & notifikasi hanya dijalankan setelah poll status
berhasil (diambil dari poll ingest lines:[] di awal tiap putaran). Bila server
tak terjangkau karena OFFLINE TRANSIEN (bukan penolakan token/HTTP 401), agen
TIDAK mengunci dan TIDAK memberi notifikasi apa pun putaran itu. Semua baris
bertanda "# WBL-AiMonitor BLOCK" otomatis dibersihkan saat agen dicopot.

CACHE DNS & KONEKSI BARU: setiap kali agen BENAR-BENAR mengubah berkas hosts
(mengunci maupun membuka), ia langsung menjalankan "ipconfig /flushdns" agar
perubahan berlaku untuk koneksi BARU. Catatan penting: blokir hanya berlaku
untuk koneksi baru -- aplikasi/VSCode yang SUDAH terbuka dengan koneksi hidup ke
api.anthropic.com mungkin perlu DITUTUP lalu DIBUKA lagi agar blokir terasa.


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
notice.ps1, pasang-klik.ps1, KLIK-PASANG.bat) ke laptop target, lalu klik kanan
KLIK-PASANG.bat > "Run as administrator". ENDPOINT sudah terisi di
pengaturan.txt; LABEL & INTERVAL_DETIK opsional.

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
     menyalin kirim.ps1 + notice.ps1 ke Program Files (copot.ps1 TIDAK disalin).
  4. Menulis config.json (endpoint_enroll, endpoint_ingest, machine_id,
     device_token, copot_hash, interval_detik, label bila ada). TANPA enroll_key.
     Pada re-run, device_token/copot_hash/label LAMA dipertahankan (update).
  5. Menerapkan ACL folder data (SYSTEM/Admin=Full, Users=baca saja).
  6. Mendaftarkan 2 Scheduled Task: "WBL AI Monitor" (SYSTEM, loop internal +
     watchdog 1 menit) dan "WBL AI Monitor Notice" (konteks user), lalu
     menjalankan putaran perdana.

Opsional -IntervalDetik (default 30): cadens loop internal agen dalam detik.
Isi mis. 15 agar approve/blokir/buka terasa lebih cepat (beban server naik).
Lewat KLIK-PASANG, isi INTERVAL_DETIK di pengaturan.txt.

Setelah pasang, perangkat MENUNGGU PERSETUJUAN di MIC (akses terkunci sampai IT
Setujui). Folder pemasang di laptop boleh dihapus setelah pasang (agen sudah di
C:\Program Files\WBL-AiMonitor).

MEMPERBARUI LAPTOP YANG SUDAH TERPASANG: cukup jalankan ulang KLIK-PASANG (atau
pasang.ps1) sebagai Administrator. Pemasang idempoten (-Force): task & berkas
diperbarui, dan enrollment lama (device_token, copot_hash, label) DIPERTAHANKAN
sehingga perangkat TIDAK kembali ke "menunggu persetujuan".

RESPONSIF: agen berjalan sebagai loop internal (default 30 detik). Setelah IT
menekan Setujui / Blokir / Buka di dashboard, perubahan terasa di laptop dalam
hitungan detik (bukan menunggu 30 menit).


----------------------------------------------------------------
7. CARA MENGECEK
----------------------------------------------------------------
  Get-ScheduledTask     -TaskName "WBL AI Monitor"         (penegak SYSTEM)
  Get-ScheduledTask     -TaskName "WBL AI Monitor Notice"  (notifier user)
  Get-ScheduledTaskInfo -TaskName "WBL AI Monitor"         (waktu jalan terakhir)
  Start-ScheduledTask   -TaskName "WBL AI Monitor"         (uji manual sekali)

GUI: Task Scheduler -> Task Scheduler Library -> "WBL AI Monitor" /
"WBL AI Monitor Notice".

Log & state (Administrator):
  C:\ProgramData\WBL-AiMonitor\kirim.log    (transisi keadaan/enroll/error/401/unggah; idle tak dicatat)
  C:\ProgramData\WBL-AiMonitor\state.json   (penanda baris terkirim + keadaan + alasan + notif_ts)
  C:\ProgramData\WBL-AiMonitor\config.json  (endpoint, device_token, copot_hash, interval_detik; tanpa enroll_key)

DIAGNOSA BLOKIR (bila blokir terasa tak berlaku):
  1. Paksa satu putaran agen:
        schtasks /Run /TN "WBL AI Monitor"
  2. Cek baris blokir di hosts:
        findstr "WBL-AiMonitor BLOCK" %SystemRoot%\System32\drivers\etc\hosts
     (harus ada "0.0.0.0 api.anthropic.com   # WBL-AiMonitor BLOCK" saat terkunci)
  3. Cek kirim.log -> cari "Hosts dikunci ... + flushdns" / "Hosts dibuka + flushdns".
  4. Ingat: blokir hanya untuk koneksi BARU. Tutup lalu buka lagi VSCode/terminal
     yang sudah berjalan agar blokir terasa. Agen sudah menjalankan ipconfig
     /flushdns otomatis setiap kali hosts berubah.

DIAGNOSA UNGGAH (bila sesi tak muncul di dashboard):
  - Transkrip dikirim sebagai BASE64 (field `enc`) justru agar TIDAK salah-blokir
    WAF/ModSecurity hosting (dulu body berisi kode/SQL kena HTTP 400/403). Bila
    kirim.log masih menampilkan "Gagal mengirim batch ... status=400/403",
    laporkan ke admin server (aturan WAF pada endpoint ingest).


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
admin). Saat mencopot, password DIVERIFIKASI KE SERVER (selalu ikut dashboard
terbaru) lewat endpoint verify-copot; bila server tak terjangkau atau token
perangkat ditolak, copot.ps1 jatuh ke CADANGAN LOKAL (hash sha256 yang pernah
disinkron server ke config.json).

copot.ps1 akan:
  - Menolak bila bukan Administrator.
  - Membaca config.json (endpoint_ingest, device_token, copot_hash). URL verify
    diturunkan dari endpoint_ingest ('/ingest' -> '/verify-copot').
  - Menanyakan password IT, menghitung sha256 HEX lowercase dari bytes UTF-8
    (cocok dengan PHP hash('sha256',$pw)).
  - VERIFIKASI KE SERVER (POST verify-copot, Bearer device_token, body {hash}):
      * ok:true            -> lanjut copot.
      * ok:false,unset:true -> "Password copot belum diatur di MIC." (exit 1)
      * ok:false           -> "Password copot salah." (exit 1)
      * 401 / jaringan/timeout -> jatuh ke CADANGAN LOKAL.
  - CADANGAN LOKAL (server tak terjangkau / token ditolak / belum ada token):
      * copot_hash ada & cocok -> lanjut copot.
      * copot_hash ada & tidak cocok -> "Password copot salah." (exit 1)
      * copot_hash kosong -> "Tidak bisa memverifikasi: server tak terjangkau dan
        belum ada cadangan di perangkat ini. Sambungkan ke jaringan lalu coba
        lagi." (exit 1)
  - Bila lolos: menghapus KEDUA Scheduled Task ("WBL AI Monitor" +
    "WBL AI Monitor Notice"), folder Program Files (kirim.ps1 + notice.ps1) &
    ProgramData, dan membersihkan baris "# WBL-AiMonitor BLOCK" dari hosts.
  - TIDAK menyentuh berkas transkrip Claude Code milik pengguna.

Keuntungan verifikasi ke server: bila IT mengganti/mereset password copot di
dashboard, perubahan langsung berlaku tanpa menunggu laptop melapor. Cadangan
lokal hanya dipakai saat benar-benar offline.

User biasa tanpa hak admin TIDAK bisa menjalankan ini. Bila terpaksa (mis. lupa
password & tak ada akses MIC), pencopotan dapat dilakukan manual oleh admin
(hapus task, folder, dan baris hosts bertanda).


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
  pasang.ps1        - pemasang (admin; salin kirim.ps1 + notice.ps1 + ACL + 2 task)
  kirim.ps1         - PENEGAK (jalan sebagai SYSTEM; enroll + status + kunci + kirim)
  notice.ps1        - NOTIFIER sesi user (kosmetik; toast saat akses terkunci)
  README.txt        - dokumen ini
  Edaran-Pemantauan-AI.docx - contoh edaran tertulis untuk tim

PERKAKAS IT (JANGAN dibagikan ke laptop tim; simpan di tempat IT saja, bawa saat
perlu mencopot):
  copot.ps1         - pencopot (admin + password IT; hapus 2 task/folder + bersihkan hosts)
  KLIK-COPOT.bat    - pembungkus copot.ps1 (Run as administrator)

Catatan: pasang.ps1 hanya menyalin kirim.ps1 + notice.ps1 ke
C:\Program Files\WBL-AiMonitor. copot.ps1/KLIK-COPOT.bat TIDAK pernah
disalin/ditinggal di mesin target.
