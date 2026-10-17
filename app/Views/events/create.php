<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-core.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-core.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('events/_form', [
    'isEdit'      => false,
    'action'      => base_url('events/create'),
    'backUrl'     => base_url('events'),
    'cancelUrl'   => base_url('events'),
    'submitLabel' => 'Buat Event',
    'v' => [
        'name'       => (string) old('name'),
        'tema'       => (string) old('tema'),
        'mall'       => (string) old('mall', 'keduanya'),
        'jenis'      => old('jenis', 'event'),
        'pos_id'     => old('pos_id'),
        'start_date' => (string) old('start_date'),
        'end_date'   => (string) old('end_date'),
        'content'    => (string) old('content'),
    ],
    'locations'   => $locations,
    'savedLocs'   => array_map('intval', (array) old('location_ids', [])),
    'posPilihan'  => $posPilihan,
]) ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/events-core.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-core.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
