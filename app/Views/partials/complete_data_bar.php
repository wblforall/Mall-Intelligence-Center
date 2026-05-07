<?php
/**
 * Vars expected: $event, $module (string key), $completion (array|null), $canEdit (bool)
 */
$isDone = ! empty($completion);
?>
<div class="alert <?= $isDone ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center justify-content-between py-2 px-3 mb-4">
    <div class="small">
        <?php if ($isDone): ?>
        <i class="bi bi-check-circle-fill me-1"></i>
        Data ditandai selesai oleh <strong><?= esc($completion['completed_by_name']) ?></strong>
        pada <?= date('d M Y H:i', strtotime($completion['completed_at'])) ?>
        <?php else: ?>
        <i class="bi bi-hourglass-split me-1"></i>
        Data belum ditandai selesai untuk modul ini.
        <?php endif; ?>
    </div>
    <?php if ($canEdit && ! $isDone): ?>
    <form method="POST" action="<?= base_url('events/'.$event['id'].'/complete/'.$module) ?>" class="ms-3 flex-shrink-0">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Tandai data modul ini sudah selesai?')">
            <i class="bi bi-check-lg me-1"></i>Complete Data
        </button>
    </form>
    <?php elseif ((session()->get('role_is_admin') || session()->get('user_role') === 'admin') && $isDone): ?>
    <form method="POST" action="<?= base_url('events/'.$event['id'].'/uncomplete/'.$module) ?>" class="ms-3 flex-shrink-0">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-danger btn-sm" onclick="return confirm('Batalkan tanda selesai?')">
            <i class="bi bi-x me-1"></i>Batal
        </button>
    </form>
    <?php endif; ?>
</div>
