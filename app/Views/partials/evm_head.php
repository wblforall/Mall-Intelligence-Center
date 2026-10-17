<?php
/**
 * Header seragam modul per-event (Loyalty, VM, Creative, Exhibition, Sponsorship).
 *
 * Variabel: $event, $module (kunci completion), $completion (array|null), $canEdit (bool),
 *           $title, $icon (kelas bootstrap-icons), $actions (HTML tombol aksi, opsional)
 * Status "data selesai" + tombol Complete/Batal memakai rute yang sama dengan
 * partials/complete_data_bar (events/{id}/complete|uncomplete/{modul}).
 */
$isDone = ! empty($completion);
$isAdmin = session()->get('role_is_admin') || session()->get('user_role') === 'admin';

$evStart = ! empty($event['start_date']) ? strtotime($event['start_date']) : null;
$evDays  = max(1, (int) ($event['event_days'] ?? 1));
$evRange = '';
if ($evStart) {
    $evEnd   = strtotime('+' . ($evDays - 1) . ' days', $evStart);
    $evRange = $evDays > 1
        ? date('d M', $evStart) . ' – ' . date('d M Y', $evEnd)
        : date('d M Y', $evStart);
}
$evStatusLabel = ['draft' => 'Draft', 'active' => 'Berjalan', 'completed' => 'Selesai'][$event['status'] ?? ''] ?? null;
?>
<div class="ev-head">
    <a href="<?= base_url('events/' . $event['id'] . '/summary') ?>" class="ev-back" title="Kembali ke Summary Event" aria-label="Kembali ke Summary Event"><i class="bi bi-arrow-left"></i></a>
    <div class="ev-head-ico"><i class="bi <?= esc($icon, 'attr') ?>"></i></div>
    <div class="min-w-0">
        <h1 class="ev-title"><?= esc($title) ?></h1>
        <div class="ev-subtitle">
            <a href="<?= base_url('events/' . $event['id'] . '/summary') ?>" title="Buka Summary Event"><?= esc($event['name']) ?></a>
            <?php if (! empty($event['tema'])): ?><span><i class="bi bi-chat-quote"></i><?= esc($event['tema']) ?></span><?php endif; ?>
            <?php if ($evRange): ?><span><i class="bi bi-calendar3"></i><?= $evRange ?></span><?php endif; ?>
            <?php if (! empty($event['mall'])): ?><span><i class="bi bi-buildings"></i><?= esc($event['mall']) ?></span><?php endif; ?>
            <?php if ($evStatusLabel): ?><span><i class="bi bi-flag"></i><?= $evStatusLabel ?></span><?php endif; ?>
        </div>
    </div>
    <div class="ev-head-act">
        <?php if ($isDone): ?>
        <span class="ev-done" title="Data modul ini sudah ditandai selesai (dikunci)">
            <i class="bi bi-check-circle-fill"></i>Selesai
            <span class="ev-done-by d-none d-md-inline">· <?= esc($completion['completed_by_name'] ?? '') ?>, <?= date('d M Y H:i', strtotime($completion['completed_at'])) ?></span>
        </span>
        <?php if ($isAdmin): ?>
        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/uncomplete/' . $module) ?>" class="ev-form-inline">
            <?= csrf_field() ?>
            <button type="submit" class="ev-btn ev-btn-bad" onclick="return confirm('Batalkan tanda selesai?')"><i class="bi bi-x-lg"></i>Batal selesai</button>
        </form>
        <?php endif; ?>
        <?php else: ?>
        <span class="ev-done is-open" title="Data modul ini belum ditandai selesai"><i class="bi bi-hourglass-split"></i>Belum ditandai selesai</span>
        <?php if ($canEdit): ?>
        <form method="POST" action="<?= base_url('events/' . $event['id'] . '/complete/' . $module) ?>" class="ev-form-inline">
            <?= csrf_field() ?>
            <button type="submit" class="ev-btn ev-btn-ok" onclick="return confirm('Tandai data modul ini sudah selesai?')"><i class="bi bi-check-lg"></i>Complete Data</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <?= $actions ?? '' ?>
    </div>
</div>
<?= view('partials/event_tabs', ['event' => $event]) ?>
