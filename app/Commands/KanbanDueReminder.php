<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Notify;

/**
 * Reminder tenggat kartu Boards/Kanban → notifikasi in-app (lonceng).
 * - due_soon : kartu jatuh tempo BESOK (H-1)
 * - due_over : kartu lewat tenggat & belum ditandai selesai (sekali per hari)
 *
 * Jadwal cron: harian pagi, mis. 0 7 * * * php spark mic:kanban-due
 */
class KanbanDueReminder extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:kanban-due';
    protected $description = 'Kirim notifikasi in-app untuk kartu kanban yang mendekati / melewati tenggat.';

    public function run(array $params)
    {
        $db = db_connect();
        $sent = 0;

        // Kartu aktif ber-due, belum selesai, di board & list aktif
        $cards = $db->table('kanban_cards c')
            ->select('c.id, c.judul, c.due_date, c.board_id, b.nama AS board_nama')
            ->join('kanban_lists l', 'l.id = c.list_id')
            ->join('kanban_boards b', 'b.id = c.board_id')
            ->where('c.is_archived', 0)->where('l.is_archived', 0)->where('b.is_archived', 0)
            ->where('c.due_done', 0)->where('c.due_date IS NOT NULL', null, false)
            ->get()->getResultArray();

        $today    = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        foreach ($cards as $c) {
            $dueDay = substr($c['due_date'], 0, 10);
            $type = null;
            if ($dueDay === $tomorrow)   $type = 'due_soon';
            elseif ($dueDay < $today)    $type = 'due_over';
            if ($type === null) continue;

            // anti-spam: satu notif per kartu per tipe per hari
            $already = $db->table('notifications')
                ->where('link_type', 'kanban_card')->where('link_id', $c['id'])
                ->where('type', $type)->where('created_at >=', $today . ' 00:00:00')
                ->countAllResults();
            if ($already) continue;

            $assignees = array_map('intval', array_column(
                $db->table('kanban_card_members')->where('card_id', $c['id'])->get()->getResultArray(), 'user_id'));
            if (! $assignees) continue;

            $title = $type === 'due_soon'
                ? 'Tenggat besok: "' . $c['judul'] . '"'
                : 'Lewat tenggat: "' . $c['judul'] . '"';
            Notify::send($assignees, 0, 'kanban', $type, $title,
                'Board ' . $c['board_nama'] . ' · tenggat ' . date('j M Y', strtotime($c['due_date'])),
                'kanban_card', (int) $c['id'], 'kanban/' . $c['board_id']);
            $sent += count($assignees);
        }

        CLI::write("Notifikasi due kanban terkirim: {$sent}");
    }
}
