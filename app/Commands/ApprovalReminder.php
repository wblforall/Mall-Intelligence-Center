<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\ApprovalInbox;
use App\Libraries\Notify;
use App\Libraries\OrgRecipients;

/**
 * Pengingat persetujuan yang menggantung.
 *
 * Notifikasi saat pengajuan masuk hanya menyala SEKALI — bila terlewat, item
 * bisa menggantung berminggu-minggu tanpa ada yang tahu. Command ini menutup
 * celah itu: tiap pagi mengirim SATU rangkuman per approver.
 *
 * - Ambang ingat  : item menunggu >= UMUR_INGAT hari (default 3)
 * - Eskalasi      : bila ada item >= UMUR_ESKALASI hari (default 14), atasan
 *                   langsung approver ikut diberi tahu
 * - Anti-spam     : maksimal satu notifikasi per approver per hari per jenis
 *
 * Jadwal cron: 0 7 * * *  php spark mic:approval-reminder
 */
class ApprovalReminder extends BaseCommand
{
    protected $group       = 'MIC';
    protected $name        = 'mic:approval-reminder';
    protected $description = 'Ingatkan approver tentang persetujuan yang masih menggantung (dan eskalasi ke atasan bila terlalu lama).';
    protected $usage       = 'mic:approval-reminder [--ingat 3] [--eskalasi 14] [--dry-run]';

    private const UMUR_INGAT    = 3;
    private const UMUR_ESKALASI = 14;

    public function run(array $params)
    {
        $umurIngat    = (int) (CLI::getOption('ingat')    ?: self::UMUR_INGAT);
        $umurEskalasi = (int) (CLI::getOption('eskalasi') ?: self::UMUR_ESKALASI);
        $dryRun       = (bool) CLI::getOption('dry-run');

        $db        = db_connect();
        $hariIni   = date('Y-m-d') . ' 00:00:00';
        $kandidat  = ApprovalInbox::candidateApprovers();
        $diingat   = 0;
        $dieskalasi = 0;

        CLI::write('Kandidat approver: ' . count($kandidat), 'cyan');

        foreach ($kandidat as $uid) {
            $ctx = ApprovalInbox::contextForUser($uid);
            if (! $ctx) continue; // user nonaktif/terhapus

            // Hanya item yang sudah menggantung >= ambang
            $tunggak = array_values(array_filter(
                ApprovalInbox::collect($ctx),
                static fn ($i) => ApprovalInbox::ageDays($i['since']) >= $umurIngat
            ));
            if (! $tunggak) continue;

            usort($tunggak, static fn ($a, $b) => strcmp((string) $a['since'], (string) $b['since']));
            $tertua    = $tunggak[0];
            $umurTertua = ApprovalInbox::ageDays($tertua['since']);

            // Anti-spam: sudah dikirim hari ini?
            $sudah = $db->table('notifications')
                ->where('user_id', $uid)->where('module', 'persetujuan')->where('type', 'reminder')
                ->where('created_at >=', $hariIni)->countAllResults();

            if (! $sudah) {
                $judul = count($tunggak) . ' persetujuan menunggu keputusan Anda';
                $isi   = 'Tertua: "' . mb_substr($tertua['title'], 0, 80) . '" — sudah ' . $umurTertua . ' hari menunggu.';
                if (! $dryRun) {
                    Notify::send([$uid], 0, 'persetujuan', 'reminder', $judul, $isi, null, null, 'persetujuan');
                }
                $diingat++;
                CLI::write(sprintf('  user #%-4d %2d item, tertua %2d hari%s', $uid, count($tunggak), $umurTertua, $dryRun ? ' [dry-run]' : ''));
            }

            // ── Eskalasi ke atasan approver ──
            if ($umurTertua < $umurEskalasi) continue;

            // Atasan langsung (aktif) → Deputy divisi → GM, supaya kemandekan
            // tetap terlihat walau atasannya sudah resign / tak tercatat.
            $atasan = OrgRecipients::escalationTargets($uid);
            if (! $atasan) continue;

            // Dedupe DIIKAT KE APPROVER (link_id), bukan sekadar "atasan ini
            // sudah dieskalasi hari ini" — kalau tidak, atasan dengan beberapa
            // bawahan mandek hanya menerima eskalasi untuk satu orang saja,
            // dan karena urutan kandidat stabil, yang lain tak pernah terkirim.
            $sudahEsk = $db->table('notifications')
                ->whereIn('user_id', $atasan)->where('module', 'persetujuan')->where('type', 'escalation')
                ->where('link_type', 'approver')->where('link_id', $uid)
                ->where('created_at >=', $hariIni)->countAllResults();
            if ($sudahEsk) continue;

            $nama = $db->table('users')->select('name')->where('id', $uid)->get()->getRowArray()['name'] ?? ('User #' . $uid);
            if (! $dryRun) {
                Notify::send(
                    $atasan, 0, 'persetujuan', 'escalation',
                    'Persetujuan mandek: ' . $nama,
                    count($tunggak) . ' item belum diputuskan, tertua ' . $umurTertua . ' hari.',
                    'approver', $uid, 'persetujuan'
                );
            }
            $dieskalasi++;
            CLI::write('    ↑ eskalasi ke atasan ' . $nama, 'yellow');
        }

        CLI::write("Selesai. Pengingat: {$diingat} · Eskalasi: {$dieskalasi}" . ($dryRun ? ' (dry-run, tidak ada yang dikirim)' : ''), 'green');
    }
}
