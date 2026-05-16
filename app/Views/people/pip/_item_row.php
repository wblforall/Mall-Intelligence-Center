<div class="row g-2">
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Aspek <span class="text-danger">*</span></label>
        <select name="aspek[]" class="form-select form-select-sm aspek-select" required onchange="autoFillItem(this)">
            <option value="">-- Pilih Aspek --</option>
        </select>
        <input type="text" name="aspek_custom[]" class="form-control form-control-sm mt-1 aspek-custom d-none" placeholder="Tuliskan aspek lainnya…">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Target yang Diharapkan</label>
        <input type="text" name="target[]" class="form-control form-control-sm" placeholder="Target otomatis terisi dari master">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Kondisi Saat Ini</label>
        <input type="text" name="masalah[]" class="form-control form-control-sm" placeholder="Kondisi karyawan saat ini…">
    </div>
    <div class="col-md-5">
        <label class="form-label small fw-semibold">Metrik / Cara Ukur</label>
        <input type="text" name="metrik[]" class="form-control form-control-sm" placeholder="Cara mengukur pencapaian">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Deadline</label>
        <input type="date" name="deadline[]" class="form-control form-control-sm">
    </div>
    <div class="col-md-4 d-flex align-items-end justify-content-end">
        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn">
            <i class="bi bi-trash"></i> Hapus
        </button>
    </div>
</div>
