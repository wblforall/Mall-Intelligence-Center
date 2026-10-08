<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\AiSkorPrompt;
$tingkat = [
    ['perlu_dilatih', 'Perlu dilatih', 'di bawah 40', 'Banyak ruang untuk berlatih menyusun instruksi. Mulai dari menyebut tujuan dan berkas yang dimaksud.'],
    ['cukup',         'Cukup',         '40 - 69',      'Arahan dasarnya ada; tambahkan konteks atau kriteria hasil agar AI tidak perlu menebak.'],
    ['baik',          'Baik',          '70 - 84',      'Instruksi jelas dan terarah dengan sedikit koreksi.'],
    ['sangat_baik',   'Sangat baik',   '85 ke atas',   'Instruksi lengkap, fokus, dan hampir tidak perlu diulang.'],
];
helper('ai_skor');
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-award me-2"></i>Cara Penilaian Mutu Prompt</h4>
        <small class="text-muted">Terbuka untuk semua pengguna MIC &mdash; supaya tim tahu persis apa yang dinilai.</small>
    </div>
    <a href="<?= base_url('ai-monitor') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Pemantauan AI</a>
</div>

<div class="alert alert-info small" role="note">
    <i class="bi bi-robot me-1"></i><strong>Penilaian dilakukan oleh AI, bukan oleh aturan atau orang.</strong>
    Hasilnya <strong>perkiraan</strong> untuk membantu pelatihan, <strong>bukan penilaian kinerja resmi</strong>
    dan tidak dipakai untuk menilai pekerjaan seseorang.
</div>

<div class="card mb-3">
    <div class="card-header py-2 fw-semibold small"><i class="bi bi-list-check me-2 text-muted"></i>Lima dimensi (masing-masing 0 - <?= AiSkorPrompt::MAKS_PER_DIMENSI ?>, total 0 - 100)</div>
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Dimensi</th><th>Yang dilihat</th><th>Contoh lemah</th><th>Contoh kuat</th></tr></thead>
        <tbody>
        <?php foreach (AiSkorPrompt::DIMENSI as $d): ?>
        <tr>
            <td class="fw-semibold"><?= esc($d['nama']) ?></td>
            <td class="small"><?= esc($d['arti']) ?></td>
            <td class="small text-muted"><em>&ldquo;<?= esc($d['lemah']) ?>&rdquo;</em></td>
            <td class="small"><em>&ldquo;<?= esc($d['kuat']) ?>&rdquo;</em></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2 fw-semibold small"><i class="bi bi-bar-chart-steps me-2 text-muted"></i>Tingkat</div>
            <ul class="list-group list-group-flush">
            <?php foreach ($tingkat as [$k, $label, $rentang, $arti]): ?>
                <li class="list-group-item d-flex gap-3 align-items-start">
                    <span class="badge <?= skor_kelas($k) ?>" style="min-width:6.5rem"><?= esc($label) ?></span>
                    <div><div class="small fw-semibold"><?= esc($rentang) ?></div><div class="small text-muted"><?= esc($arti) ?></div></div>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2 fw-semibold small"><i class="bi bi-slash-circle me-2 text-muted"></i>Yang tidak dinilai</div>
            <div class="card-body small">
                <ul class="mb-0">
                    <li><strong>Sesi pribadi</strong> tidak pernah dinilai &mdash; tidak masuk rata-rata maupun tren.</li>
                    <li>Sesi dengan <strong>kurang dari <?= AiSkorPrompt::MIN_PROMPT_MANUSIA ?> prompt</strong>, atau yang hanya berisi &ldquo;lanjut&rdquo;/&ldquo;ok&rdquo; (bukan pemberian instruksi).</li>
                    <li>Sesi yang <strong>masih berjalan</strong> (menunggu <?= AiSkorPrompt::JEDA_SESI_AKTIF ?> menit tanpa aktivitas).</li>
                    <li>Sesi yang <strong>belum sempat dinilai AI</strong> (mis. layanan AI sedang penuh) tampil sebagai &ldquo;Belum dinilai&rdquo;, tidak dihitung, lalu dicoba lagi otomatis.</li>
                    <li>Sesi berstatus <em>tak jelas</em> (kantor atau pribadi) tetap dinilai tetapi diberi tanda.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header py-2 fw-semibold small"><i class="bi bi-gear me-2 text-muted"></i>Bagaimana penilaian dibuat</div>
    <div class="card-body small">
        <ol class="mb-2">
            <li>Sesi selesai, lalu diklasifikasi (jenis, tema, kantor/pribadi) seperti biasa.</li>
            <li>Sesi bukan pribadi dan layak dinilai dikirim ke AI: hanya <strong>cuplikan prompt manusia</strong> (maksimal <?= 6 ?> prompt, dipotong), tanpa balasan Claude. Kata sandi dan token sudah disamarkan sebelum disimpan.</li>
            <li>AI memberi nilai 0 - <?= AiSkorPrompt::MAKS_PER_DIMENSI ?> pada tiap dimensi di atas dan satu-dua kalimat saran perbaikan.</li>
            <li>Tidak ada skor pengganti dari aturan atau kata kunci: bila AI tidak bisa menilai, sesi tetap &ldquo;Belum dinilai&rdquo;.</li>
        </ol>
        <p class="mb-1"><strong>Aturan tampilan.</strong> Skor rata-rata seorang karyawan baru muncul bila ada minimal <strong><?= AiSkorPrompt::MIN_SESI_TAMPIL ?> sesi</strong> ternilai pada periode itu; di bawahnya tampil &ldquo;Data belum cukup&rdquo;. Jumlah sesi dasar penilaian selalu ditampilkan. Dashboard <strong>tidak memuat papan peringkat</strong>; tabel karyawan urut abjad dan menampilkan tren dibanding periode sebelumnya.</p>
        <p class="mb-0"><strong>Keterbatasan.</strong> AI bisa keliru atau bias; prompt singkat yang tepat untuk tugas kecil bisa dinilai terlalu rendah. Gunakan sebagai bahan latihan, bukan vonis.</p>
    </div>
</div>
<?= $this->endSection() ?>
