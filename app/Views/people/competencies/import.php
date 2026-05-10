<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('people/competencies') ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="fw-bold mb-0">Import Competency Framework</h5>
        <div class="text-muted small">Upload CSV hasil generate AI</div>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger py-2"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Upload form -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-upload me-2"></i>Upload File CSV</div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('people/competencies/import/parse') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">File CSV <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">Format UTF-8, max 2MB. Bisa export dari Google Sheets atau Excel.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-1"></i>Preview Import
                        </button>
                        <a href="<?= base_url('people/competencies/import/template') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-download me-1"></i>Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Format guide -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2"></i>Format CSV</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Lima kolom (header baris pertama):</p>
                <table class="table table-sm table-bordered small mb-3">
                    <thead class="table-light">
                        <tr><th>Kolom</th><th>Nilai</th><th>Wajib</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>cluster</code></td><td>Nama cluster (dibuat otomatis jika belum ada)</td><td class="text-center text-muted">–</td></tr>
                        <tr><td><code>kategori</code></td><td><code>hard</code> atau <code>soft</code></td><td class="text-center text-danger">✓</td></tr>
                        <tr><td><code>nama</code></td><td>Nama kompetensi</td><td class="text-center text-danger">✓</td></tr>
                        <tr><td><code>deskripsi</code></td><td>Deskripsi singkat</td><td class="text-center text-muted">–</td></tr>
                        <tr><td><code>pertanyaan</code></td><td>Satu pertanyaan per baris</td><td class="text-center text-danger">✓</td></tr>
                    </tbody>
                </table>
                <p class="small text-muted mb-1"><strong>Contoh:</strong></p>
                <pre class="small bg-light rounded p-2 mb-2" style="font-size:.72rem;overflow-x:auto">cluster,kategori,nama,deskripsi,pertanyaan
Technical & Digital,hard,AI Prompting,Kemampuan AI,Mampu menyusun prompt efektif?
Technical & Digital,hard,AI Prompting,Kemampuan AI,Menggunakan AI untuk produktivitas?
Communication,soft,Komunikasi,,Menyampaikan info dengan jelas?</pre>
                <p class="small text-muted mb-0">
                    <i class="bi bi-lightbulb me-1 text-warning"></i>
                    Cluster yang belum ada akan dibuat otomatis. Kompetensi yang sudah ada hanya ditambah pertanyaannya.
                </p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header fw-semibold"><i class="bi bi-robot me-2"></i>Generate Prompt AI</div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Jabatan <span class="text-danger">*</span></label>
                    <input type="text" id="pJabatan" class="form-control form-control-sm"
                           placeholder="cth: Marketing Manager, Staff IT, HRD Supervisor">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Departemen / Divisi</label>
                    <input type="text" id="pDept" class="form-control form-control-sm"
                           placeholder="cth: Marketing, IT, Human Resources">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Industri / Konteks Perusahaan</label>
                    <input type="text" id="pKonteks" class="form-control form-control-sm"
                           placeholder="cth: properti retail mall, perbankan, manufaktur">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Jumlah kompetensi</label>
                        <input type="number" id="pJmlComp" class="form-control form-control-sm" value="8" min="3" max="20">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Pertanyaan per kompetensi</label>
                        <input type="number" id="pJmlQ" class="form-control form-control-sm" value="4" min="2" max="10">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Prompt yang dihasilkan</label>
                    <textarea id="promptOutput" class="form-control form-control-sm" rows="7" readonly
                              style="font-size:.72rem;resize:none;background:var(--bs-secondary-bg)"></textarea>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="copyPromptBtn">
                    <i class="bi bi-clipboard me-1"></i>Copy Prompt
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function buildPrompt() {
    const jabatan  = document.getElementById('pJabatan').value.trim();
    const dept     = document.getElementById('pDept').value.trim();
    const konteks  = document.getElementById('pKonteks').value.trim();
    const jmlComp  = document.getElementById('pJmlComp').value || '8';
    const jmlQ     = document.getElementById('pJmlQ').value || '4';

    if (! jabatan) {
        document.getElementById('promptOutput').value = 'Isi jabatan terlebih dahulu...';
        return;
    }

    const deptLine    = dept    ? ` di departemen ${dept}` : '';
    const konteksLine = konteks ? ` Perusahaan bergerak di bidang ${konteks}.` : '';

    const prompt = `Buatkan competency framework untuk posisi ${jabatan}${deptLine}.${konteksLine}

PENTING: Berikan output HANYA berupa file CSV murni. Tidak boleh ada teks penjelasan, tidak boleh ada markdown, tidak boleh ada kode blok (\`\`\`), tidak boleh ada kalimat pembuka atau penutup. Langsung mulai dari baris header.

Format CSV (satu baris per pertanyaan):
cluster,kategori,nama,deskripsi,pertanyaan

Aturan:
- "cluster" adalah kelompok kompetensi (contoh: Leadership & Management, Technical & Digital, Communication & Interpersonal, Administrative & Operational, Customer Service)
- "kategori" hanya boleh "hard" atau "soft"
- "nama" adalah nama kompetensi
- "deskripsi" adalah penjelasan singkat kompetensi tersebut
- "pertanyaan" adalah pertanyaan penilaian yang spesifik dan dapat diamati, SATU pertanyaan per baris
- Buat ${jmlComp} kompetensi yang relevan untuk posisi ${jabatan}
- Buat ${jmlQ} pertanyaan per kompetensi
- Skala penilaian: 1=Tidak pernah, 2=Jarang, 3=Kadang-kadang, 4=Sering, 5=Selalu
- Gunakan bahasa Indonesia yang formal
- Jika nilai mengandung koma, bungkus dengan tanda kutip ganda

Output pertama dan terakhir harus baris CSV, tidak ada yang lain.`;

    document.getElementById('promptOutput').value = prompt;
}

['pJabatan','pDept','pKonteks','pJmlComp','pJmlQ'].forEach(id => {
    document.getElementById(id).addEventListener('input', buildPrompt);
});

document.getElementById('copyPromptBtn').addEventListener('click', function() {
    const ta = document.getElementById('promptOutput');
    if (! ta.value || ta.value === 'Isi jabatan terlebih dahulu...') return;
    navigator.clipboard.writeText(ta.value).then(() => {
        this.innerHTML = '<i class="bi bi-check-lg me-1"></i>Tersalin!';
        this.classList.replace('btn-outline-primary', 'btn-success');
        setTimeout(() => {
            this.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy Prompt';
            this.classList.replace('btn-success', 'btn-outline-primary');
        }, 2000);
    });
});

// Init on load
buildPrompt();
</script>
<?= $this->endSection() ?>
