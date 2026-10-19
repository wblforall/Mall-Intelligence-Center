<?php
/**
 * Pilihan PIC untuk form Deputy (Tambah / Edit program).
 * Daftar PIC = karyawan aktif di divisi Deputy (semua dept divisi + yang langsung di divisi).
 * Memilih "Assign ke Departemen" menyaring PIC ke karyawan dept itu.
 *
 * Butuh: $employees (id, nama, dept_id, dept_name), $selectedPic (int|null), $formId (string).
 * Select dept di form yang sama harus bernama assigned_to_dept_id.
 */
$selectedPic = (int) ($selectedPic ?? 0);
$formId      = (string) ($formId ?? 'frm');
$picId       = $formId . '-pic';
?>
<div class="mb-2">
    <label class="form-label small fw-semibold" for="<?= esc($picId, 'attr') ?>">PIC</label>
    <select name="pic_employee_id" id="<?= esc($picId, 'attr') ?>" class="form-select form-select-sm" data-wr-pic>
        <option value="">— Belum ada PIC —</option>
        <?php foreach ($employees as $e): ?>
        <option value="<?= (int) $e['id'] ?>" data-dept="<?= (int) ($e['dept_id'] ?? 0) ?>"
                <?= $selectedPic === (int) $e['id'] ? 'selected' : '' ?>>
            <?= esc($e['nama']) ?><?= ! empty($e['dept_name']) ? ' · ' . esc($e['dept_name']) : '' ?>
        </option>
        <?php endforeach; ?>
    </select>
    <div class="form-text" style="font-size:.68rem">Penanggung jawab program. Bila program di-assign ke departemen, pilihan menyempit ke karyawan departemen itu.</div>
</div>
<script>
(function () {
    var pic = document.getElementById(<?= json_encode($picId) ?>);
    if (!pic) return;
    var form = pic.closest('form');
    var dept = form ? form.querySelector('select[name="assigned_to_dept_id"]') : null;
    var awal = true;
    function saring() {
        var d = dept ? parseInt(dept.value || '0', 10) : 0;
        [].forEach.call(pic.options, function (o) {
            if (!o.value) return;
            var ok = !d || parseInt(o.dataset.dept || '0', 10) === d || (awal && o.selected);
            o.hidden = !ok;
            o.disabled = !ok;
        });
        var cur = pic.options[pic.selectedIndex];
        // Nilai awal tersimpan tidak dihapus saat halaman dimuat; hanya pergantian dept yang mengosongkan.
        if (!awal && cur && cur.value && cur.disabled) pic.value = '';
    }
    if (dept) dept.addEventListener('change', saring);
    saring();
    awal = false;
})();
</script>
