<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/events-core.css') ?>?v=<?= @filemtime(FCPATH . 'css/events-core.css') ?: 1 ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$oldLocs   = old('location_ids', null);
$savedLocs = array_map('intval', $oldLocs !== null ? (array) $oldLocs : $selectedLocationIds);
$editEnd   = old('end_date', $event['start_date']
    ? date('Y-m-d', strtotime($event['start_date'] . ' +' . (max(1, (int) $event['event_days']) - 1) . ' days'))
    : '');
?>
<?= view('events/_form', [
    'isEdit'      => true,
    'action'      => base_url('events/' . $event['id'] . '/edit'),
    'backUrl'     => base_url('events/' . $event['id'] . '/summary'),
    'cancelUrl'   => base_url('events/' . $event['id'] . '/summary'),
    'submitLabel' => 'Simpan Perubahan',
    'v' => [
        'name'       => (string) old('name', $event['name']),
        'tema'       => (string) old('tema', $event['tema'] ?? ''),
        'mall'       => (string) old('mall', $event['mall'] ?? ''),
        'jenis'      => old('jenis', $event['jenis'] ?? 'event'),
        'pos_id'     => old('pos_id', $event['pos_id'] ?? null),
        'start_date' => (string) old('start_date', $event['start_date']),
        'end_date'   => (string) $editEnd,
        'content'    => '',
    ],
    'locations'   => $locations,
    'savedLocs'   => $savedLocs,
    'posPilihan'  => $posPilihan,
]) ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/events-core.js') ?>?v=<?= @filemtime(FCPATH . 'js/events-core.js') ?: 1 ?>"></script>
<?= $this->endSection() ?>
