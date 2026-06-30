function pwaContent() {
  return `
<h2><div class="num">1</div> Apa itu PWA?</h2>
<div class="rule"></div>
<p style="text-align:justify">Mall Intelligence Center (MIC) mendukung teknologi <strong>Progressive Web App (PWA)</strong> — standar web modern yang memungkinkan aplikasi browser diinstall langsung ke perangkat seperti aplikasi native, tanpa perlu membuka App Store atau Play Store. Setelah diinstall, MIC berjalan dalam jendela tersendiri tanpa address bar browser, memberikan pengalaman seperti aplikasi desktop atau mobile.</p>

<div class="two-col">
  <div>
    <div class="callout ok"><strong>Keuntungan PWA:</strong>
      <ul style="margin:5px 0 0;padding-left:16px">
        <li>Ikon langsung di taskbar / layar utama</li>
        <li>Jendela tersendiri, tanpa gangguan tab browser</li>
        <li>Tampilan offline saat tidak ada koneksi</li>
        <li>Performa lebih cepat berkat caching aset</li>
        <li>Selalu up-to-date — tidak perlu update manual</li>
      </ul>
    </div>
  </div>
  <div>
    <div class="callout warn"><strong>Syarat Instalasi:</strong>
      <ul style="margin:5px 0 0;padding-left:16px">
        <li>Browser: Chrome atau Edge (desktop/Android)</li>
        <li>iOS/iPadOS: wajib pakai Safari</li>
        <li>Koneksi internet saat pertama install</li>
        <li>Sudah pernah login minimal sekali</li>
      </ul>
    </div>
  </div>
</div>

${ssOnly('pwa_app_desktop', 'MIC berjalan di browser — siap diinstall sebagai PWA')}
<div class="pb"></div>

<h2><div class="num">2</div> Install di Komputer (Windows / Mac)</h2>
<div class="rule"></div>
<p style="text-align:justify">Setelah MIC dibuka di Chrome atau Edge dan pengguna sudah login, browser akan mendeteksi bahwa aplikasi dapat diinstall. Ikon install akan muncul di pojok kanan address bar. Proses install hanya butuh beberapa detik.</p>

<h3>Langkah-langkah (Google Chrome)</h3>
<ul class="steps">
  <li><span class="n">1</span>Buka MIC di Chrome: <strong>mic.wbl-bsb.com</strong> dan login dengan akun Anda.</li>
  <li><span class="n">2</span>Perhatikan ikon <strong>⊕</strong> (Install) di pojok kanan address bar. Klik ikon tersebut.</li>
  <li><span class="n">3</span>Dialog konfirmasi akan muncul. Klik <span class="btn-ref">Install</span>.</li>
  <li><span class="n">4</span>MIC akan terbuka di jendela baru tanpa address bar. Ikon MIC juga muncul di taskbar dan desktop.</li>
</ul>

<div class="callout">Jika ikon install tidak muncul di address bar, coba via menu Chrome: klik <strong>⋮</strong> (tiga titik) → <strong>Simpan dan bagikan</strong> → <strong>Instal Mall Intelligence Center…</strong></div>

<h3>Langkah-langkah (Microsoft Edge)</h3>
<ul class="steps">
  <li><span class="n">1</span>Buka MIC di Edge dan login.</li>
  <li><span class="n">2</span>Klik menu <strong>⋯</strong> (tiga titik) di kanan atas.</li>
  <li><span class="n">3</span>Pilih <strong>Aplikasi</strong> → <strong>Instal situs ini sebagai aplikasi</strong>.</li>
  <li><span class="n">4</span>Konfirmasi nama aplikasi, lalu klik <span class="btn-ref">Instal</span>.</li>
</ul>

${ssOnly('pwa_app_standalone', 'MIC setelah terinstall — berjalan di jendela standalone tanpa address bar')}

<h3>Membuka MIC yang Sudah Terinstall</h3>
<p style="text-align:justify">Setelah install, MIC dapat dibuka melalui:</p>
<ul>
  <li><strong>Windows:</strong> Cari "MIC" atau "Mall Intelligence Center" di Start Menu, atau klik shortcut di desktop.</li>
  <li><strong>Mac:</strong> Cari di Launchpad atau Spotlight (⌘ Space → ketik "MIC").</li>
  <li><strong>Taskbar:</strong> Klik kanan ikon MIC → <strong>Pin to taskbar</strong> untuk akses cepat setiap saat.</li>
</ul>

<div class="callout">MIC yang terinstall dan MIC di browser adalah aplikasi yang <strong>sama</strong> — data, sesi login, dan izin identik. Tidak perlu login ulang jika browser Anda sudah dalam kondisi login.</div>
<div class="pb"></div>

<h2><div class="num">3</div> Install di Android</h2>
<div class="rule"></div>
<p style="text-align:justify">Di perangkat Android, MIC dapat diinstall melalui Chrome. Setelah beberapa kali mengunjungi situs, Chrome akan menampilkan banner install otomatis di bagian bawah layar. Install juga bisa dilakukan manual kapan saja.</p>

<h3>Cara 1 — Banner Otomatis</h3>
<ul class="steps">
  <li><span class="n">1</span>Buka <strong>mic.wbl-bsb.com</strong> di Chrome dan login.</li>
  <li><span class="n">2</span>Tunggu banner <em>"Tambahkan MIC ke layar utama"</em> muncul di bawah layar.</li>
  <li><span class="n">3</span>Ketuk <span class="btn-ref">Instal</span> pada banner tersebut.</li>
  <li><span class="n">4</span>Ikon MIC akan muncul di layar utama (home screen) Android Anda.</li>
</ul>

<h3>Cara 2 — Manual via Menu Chrome</h3>
<ul class="steps">
  <li><span class="n">1</span>Buka MIC di Chrome, login, lalu ketuk menu <strong>⋮</strong> (tiga titik) di kanan atas.</li>
  <li><span class="n">2</span>Pilih <strong>Tambahkan ke layar utama</strong>.</li>
  <li><span class="n">3</span>Konfirmasi nama, lalu ketuk <span class="btn-ref">Tambahkan</span>.</li>
  <li><span class="n">4</span>Ikon MIC tampil di home screen. Ketuk untuk membuka dalam mode fullscreen.</li>
</ul>

${ssPair('pwa_app_desktop', 'mob_pwa_app', 'Tampilan desktop', 'Tampilan mobile (Android)')}

<div class="callout warn">Pastikan menggunakan <strong>Google Chrome</strong> di Android. Browser bawaan Samsung (Samsung Internet) dan Firefox tidak mendukung PWA install sepenuhnya.</div>
<div class="pb"></div>

<h2><div class="num">4</div> Install di iPhone / iPad (iOS)</h2>
<div class="rule"></div>
<p style="text-align:justify">Di perangkat Apple (iPhone dan iPad), install PWA hanya dapat dilakukan melalui <strong>Safari</strong>. Browser lain seperti Chrome di iOS tidak mendukung fitur "Tambah ke Layar Utama" untuk PWA. Setelah diinstall, MIC berjalan sebagai aplikasi standalone di iOS.</p>

<h3>Langkah-langkah (Safari di iOS/iPadOS)</h3>
<ul class="steps">
  <li><span class="n">1</span>Buka <strong>Safari</strong> (bukan Chrome) dan navigasi ke <strong>mic.wbl-bsb.com</strong>. Login dengan akun Anda.</li>
  <li><span class="n">2</span>Ketuk ikon <strong>Share</strong> (kotak dengan panah ke atas) di toolbar bawah Safari.</li>
  <li><span class="n">3</span>Gulir daftar opsi ke bawah dan pilih <strong>Tambah ke Layar Utama</strong> (<em>Add to Home Screen</em>).</li>
  <li><span class="n">4</span>Edit nama jika diinginkan (default: "MIC"), lalu ketuk <strong>Tambahkan</strong> di kanan atas.</li>
  <li><span class="n">5</span>Ikon MIC muncul di home screen. Ketuk untuk membuka dalam mode standalone.</li>
</ul>

<div class="callout warn"><strong>iOS — Wajib Pakai Safari.</strong> Chrome, Firefox, dan browser lain di iOS tidak memiliki opsi "Tambah ke Layar Utama" yang menghasilkan PWA. Jika opsi tersebut tidak tampil di Safari, pastikan iOS Anda sudah versi 16.4 ke atas.</div>

<div class="callout"><strong>iPad:</strong> Tombol Share di iPad Safari ada di toolbar atas (kanan), bukan bawah. Langkah lainnya sama.</div>
<div class="pb"></div>

<h2><div class="num">5</div> Fitur Saat Terinstall</h2>
<div class="rule"></div>
<p style="text-align:justify">Setelah MIC terinstall sebagai PWA, beberapa fitur tambahan tersedia yang tidak ada saat membuka di browser biasa.</p>

<h3>Mode Offline</h3>
<p style="text-align:justify">Saat perangkat kehilangan koneksi internet, MIC menampilkan halaman offline khusus yang memberi tahu pengguna bahwa koneksi terputus. Halaman ini secara otomatis melakukan pengecekan ulang setiap beberapa detik dan langsung melanjutkan sesi begitu koneksi kembali — tanpa perlu reload manual.</p>

${ssOnly('pwa_offline', 'Halaman offline MIC — tampil otomatis saat koneksi terputus')}

<div class="callout warn"><strong>Penting:</strong> MIC adalah aplikasi berbasis data real-time. Mode offline hanya menampilkan halaman tunggu — data tidak dapat diakses atau disimpan secara lokal. Pastikan koneksi internet stabil saat bekerja dengan modul yang membutuhkan input data.</div>

<h3>Update Otomatis</h3>
<p style="text-align:justify">MIC PWA tidak memerlukan update manual dari App Store atau Play Store. Setiap kali ada versi baru, pembaruan akan diunduh di latar belakang saat perangkat terhubung ke internet. Update aktif saat PWA ditutup dan dibuka kembali.</p>

<h3>Sesi Login</h3>
<p style="text-align:justify">Sesi login MIC berlaku <strong>60 menit</strong> sejak terakhir aktif. Jika tidak ada aktivitas selama 60 menit, sistem akan logout otomatis demi keamanan. Login ulang diperlukan setelahnya. Ini berlaku baik di browser maupun mode PWA.</p>

<h3>Hapus / Uninstall PWA</h3>
<table>
  <thead><tr><th>Platform</th><th>Cara Uninstall</th></tr></thead>
  <tbody>
    <tr><td><b>Windows (Chrome)</b></td><td>Buka MIC PWA → klik <strong>⋮</strong> (menu) di kanan atas jendela → <strong>Hapus Mall Intelligence Center…</strong></td></tr>
    <tr><td><b>Windows (Edge)</b></td><td>Buka MIC PWA → klik <strong>⋯</strong> → <strong>Pengaturan Aplikasi</strong> → <strong>Hapus instalan</strong></td></tr>
    <tr><td><b>Mac</b></td><td>Klik kanan ikon MIC di Launchpad → <strong>Hapus</strong>, atau buka MIC PWA → menu → <strong>Hapus…</strong></td></tr>
    <tr><td><b>Android</b></td><td>Tekan dan tahan ikon MIC di home screen → seret ke <strong>Hapus instalasi</strong> / <strong>Uninstall</strong></td></tr>
    <tr><td><b>iOS</b></td><td>Tekan dan tahan ikon MIC di home screen → ketuk <strong>Hapus Aplikasi</strong> → <strong>Hapus dari Layar Utama</strong></td></tr>
  </tbody>
</table>

<div class="callout">Menghapus PWA <strong>tidak</strong> menghapus akun atau data. Data tersimpan di server MIC. Anda dapat install ulang kapan saja melalui langkah yang sama.</div>
  `;
}
