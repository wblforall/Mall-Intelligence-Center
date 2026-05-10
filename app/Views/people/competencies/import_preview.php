<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$newCount   = count(array_filter($data, fn($d) => ! $d['exists']));
$addCount   = count(array_filter($data, fn($d) => $d['exists']));
$totalQs    = array_sum(array_map(fn($d) => count($d['questions']), $data));
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= base_url('people/competencies/import') ?>" class="btn btn-sm btn-light">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="fw-bold mb-0">Preview Import</h5>
        <div class="text-muted small">Periksa sebelum menyimpan</div>
    </div>
</div>

<!-- Summary pills -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <span class="badge bg-success fs-6 fw-normal px-3 py-2">
        <i class="bi bi-plus-circle me-1"></i><?= $newCount ?> kompetensi baru
    </span>
    <?php if ($addCount): ?>
    <span class="badge bg-info text-dark fs-6 fw-normal px-3 py-2">
        <i class="bi bi-arrow-up-circle me-1"></i><?= $addCount ?> kompetensi sudah ada (pertanyaan ditambahkan)
    </span>
    <?php endif; ?>
    <span class="badge bg-secondary fs-6 fw-normal px-3 py-2">
        <i class="bi bi-chat-left-dots me-1"></i><?= $totalQs ?> pertanyaan total
    </span>
</div>

<!-- Preview table -->
<?php foreach (['hard' => 'Hard Skill', 'soft' => 'Soft Skill'] as $cat => $catLabel):
    $items = array_values(array_filter($data, fn($d) => $d['kategori'] === $cat));
    if (empty($items)) continue;
?>
<div class="card mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-<?= $cat === 'hard' ? 'gear-fill text-primary' : 'heart-fill text-danger' ?> me-2"></i>
        <?= $catLabel ?>
        <span class="badge bg-secondary ms-1"><?= count($items) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:200px">Kompetensi</th>
                    <th>Pertanyaan</th>
                    <th style="width:100px" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="align-top py-2">
                    <div class="fw-semibold" style="font-size:.85rem"><?= esc($item['nama']) ?></div>
                    <?php if ($item['deskripsi']): ?>
                    <div class="text-muted" style="font-size:.72rem"><?= esc($item['deskripsi']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="align-top py-2">
                    <ol class="mb-0 ps-3" style="font-size:.8rem">
                        <?php foreach ($item['questions'] as $q): ?>
                        <li class="mb-1"><?= esc($q) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </td>
                <td class="text-center align-top py-2">
                    <?php if ($item['exists']): ?>
                    <span class="badge bg-info text-dark" style="font-size:.68rem">Sudah ada<br>+<?= count($item['questions']) ?> pertanyaan</span>
                    <?php else: ?>
                    <span class="badge bg-success" style="font-size:.68rem">Baru</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<!-- Actions -->
<div class="d-flex gap-2">
    <form method="POST" action="<?= base_url('people/competencies/import/confirm') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary"
                onclick="return confirm('Import <?= $totalQs ?> pertanyaan ke <?= count($data) ?> kompetensi?')">
            <i class="bi bi-check-lg me-1"></i>Konfirmasi Import
        </button>
    </form>
    <a href="<?= base_url('people/competencies/import') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-x me-1"></i>Batal
    </a>
</div>

<?= $this->endSection() ?>
