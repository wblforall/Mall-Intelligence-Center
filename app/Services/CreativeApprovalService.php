<?php

namespace App\Services;

use App\Libraries\ActivityLog;
use App\Libraries\Notify;
use App\Libraries\OrgRecipients;
use App\Models\CreativeFileModel;
use App\Models\CreativeItemModel;
use App\Models\CreativeReviewModel;
use App\Models\EventCreativeFileModel;
use App\Models\EventCreativeItemModel;

/**
 * Alur persetujuan materi creative — satu sumber kebenaran untuk creative
 * standalone maupun creative per-event, yang alurnya identik.
 *
 * Sebelumnya keputusan diambil lewat satu endpoint updateStatus yang
 * menerima status apa pun dari dropdown bebas: tidak ada aksi eksplisit
 * "setujui"/"minta revisi", alasan revisi tidak wajib, tidak ada jejak
 * keputusan, dan materi yang sudah disetujui masih bisa diganti filenya
 * diam-diam tanpa status ikut berubah.
 *
 * Aturan yang ditegakkan di sini:
 *
 *  draft ──ajukan──> review ──setujui──> approved (terkunci)
 *                      │                    │
 *                      └──revisi──> revision └──buka──> draft
 *
 * - Setiap perpindahan mencatat satu baris di `creative_reviews`.
 * - `versi` naik saat revisi diminta dan saat materi dibuka kembali,
 *   sehingga file yang diunggah sesudahnya otomatis milik putaran baru.
 * - Peninjau wajib memilih opsi mana yang disetujui bila kandidatnya
 *   lebih dari satu; bila hanya satu opsi, opsi itu dipilih otomatis.
 * - Alasan revisi wajib diisi dan disimpan sebagai catatan keputusan,
 *   bukan menimpa kolom `catatan` milik item.
 */
class CreativeApprovalService
{
    public const SCOPE_STANDALONE = 'standalone';
    public const SCOPE_EVENT      = 'event';

    /** Panjang minimum alasan revisi — cukup untuk memaksa kalimat, bukan "ok". */
    public const MIN_ALASAN = 10;

    private string $scope;
    private ?int $eventId;

    public function __construct(string $scope, ?int $eventId = null)
    {
        if (! in_array($scope, [self::SCOPE_STANDALONE, self::SCOPE_EVENT], true)) {
            throw new \InvalidArgumentException('Scope creative tidak dikenal: ' . $scope);
        }
        $this->scope   = $scope;
        $this->eventId = $eventId;
    }

    public static function standalone(): self
    {
        return new self(self::SCOPE_STANDALONE);
    }

    public static function forEvent(int $eventId): self
    {
        return new self(self::SCOPE_EVENT, $eventId);
    }

    private function isEvent(): bool
    {
        return $this->scope === self::SCOPE_EVENT;
    }

    private function itemModel(): CreativeItemModel|EventCreativeItemModel
    {
        return $this->isEvent() ? new EventCreativeItemModel() : new CreativeItemModel();
    }

    /**
     * Ambil item sekaligus pastikan ia memang milik event yang sedang
     * dibuka — tanpa ini, id item dari event lain bisa diselipkan lewat
     * URL dan riwayat keputusannya tercatat di event yang keliru.
     */
    private function temukan(int $itemId): ?array
    {
        $item = $this->itemModel()->find($itemId);
        if (! $item) return null;

        if ($this->isEvent() && (int) ($item['event_id'] ?? 0) !== (int) $this->eventId) {
            return null;
        }
        return $item;
    }

    private function fileModel(): CreativeFileModel|EventCreativeFileModel
    {
        return $this->isEvent() ? new EventCreativeFileModel() : new CreativeFileModel();
    }

    private function logModule(): string
    {
        return $this->isEvent() ? 'creative' : 'creative_standalone';
    }

    private function menuKey(): string
    {
        return $this->isEvent() ? 'creative' : 'creative_main';
    }

    private function linkType(): string
    {
        return $this->isEvent() ? 'event_creative_item' : 'creative_item';
    }

    public function itemUrl(int $itemId): string
    {
        return $this->isEvent()
            ? "events/{$this->eventId}/creative#item-{$itemId}"
            : 'creative#item-' . $itemId . '-s';
    }

    // ─────────────────────────────────────────────────────────────
    // Status & penguncian
    // ─────────────────────────────────────────────────────────────

    /**
     * Materi yang sudah disetujui terkunci: nama, budget, file, dan
     * penghapusan ditolak sampai seseorang membukanya kembali secara
     * eksplisit. Ini yang menutup celah "sudah approved lalu filenya
     * diganti diam-diam tapi badge tetap hijau".
     */
    public static function isLocked(?array $item): bool
    {
        return ($item['status'] ?? 'draft') === 'approved';
    }

    public const PESAN_TERKUNCI = 'Materi sudah disetujui dan terkunci. Klik "Buka & Ajukan Ulang" bila memang perlu diubah.';

    /**
     * Opsi tidak boleh dihapus selama menunggu keputusan maupun setelah
     * disetujui.
     *
     * Saat 'review': menghapus opsi terakhir akan membuat item tersangkut —
     * tak ada lagi yang bisa disetujui, tapi statusnya tetap menunggu dan
     * terus muncul di Kotak Persetujuan. Menambah opsi baru tetap boleh,
     * karena itu hanya memperkaya pilihan peninjau.
     * Saat 'approved': menghapus opsi berarti menghapus jejak keputusan.
     */
    public static function opsiTerkunci(?array $item): bool
    {
        return in_array($item['status'] ?? 'draft', ['review', 'approved'], true);
    }

    /**
     * Apakah SATU file boleh dihapus.
     *
     * File pendukung (brief, referensi) bukan bahan keputusan, jadi ia tetap
     * bisa dirapikan selagi materi menunggu peninjauan — tanpa ini, referensi
     * yang salah unggah terjebak sampai materi disetujui, dan satu-satunya
     * jalan keluar justru membatalkan persetujuan.
     */
    public static function fileTerkunci(?array $item, ?array $file): bool
    {
        $status = $item['status'] ?? 'draft';

        if ($status === 'approved')       return true;
        if ($status !== 'review')         return false;

        // Selama review, hanya opsi yang dibekukan.
        return (int) ($file['is_opsi'] ?? 1) === 1;
    }

    public static function pesanOpsiTerkunci(?array $item): string
    {
        return ($item['status'] ?? '') === 'review'
            ? 'Opsi tidak bisa dihapus selama materi menunggu peninjauan. Tunggu keputusan peninjau, atau tambahkan opsi baru.'
            : self::PESAN_TERKUNCI;
    }

    // ─────────────────────────────────────────────────────────────
    // Opsi desain
    // ─────────────────────────────────────────────────────────────

    /** Opsi desain milik satu item (file pendukung tidak ikut). */
    public function opsiOf(int $itemId): array
    {
        return $this->fileModel()
            ->where('creative_item_id', $itemId)
            ->where('is_opsi', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Metadata untuk file yang baru diunggah: label opsi berikutnya dan
     * versi putaran yang sedang berjalan.
     *
     * Label berlanjut sepanjang umur item (A, B, C, ... lintas versi),
     * tidak diulang per versi, supaya riwayat "lanjutkan opsi B" tetap
     * menunjuk ke satu file yang jelas.
     */
    public function uploadMeta(int $itemId, bool $isOpsi): array
    {
        $item  = $this->temukan($itemId);
        $versi = (int) ($item['versi'] ?? 1) ?: 1;

        if (! $isOpsi) {
            return ['is_opsi' => 0, 'opsi_label' => null, 'versi' => $versi];
        }

        // Penghitung di item, bukan label tertinggi yang masih ada: label
        // sekali terbit tidak boleh terbit lagi, walau filenya sudah dihapus.
        // Kalau tidak, riwayat "Disetujui — opsi C" bisa menunjuk gambar baru
        // yang belum pernah ditinjau siapa pun.
        $urutan = max(
            (int) ($item['opsi_terakhir'] ?? 0),
            self::urutanTertinggi(array_column($this->opsiOf($itemId), 'opsi_label'))
        ) + 1;

        $this->itemModel()->update($itemId, ['opsi_terakhir' => $urutan]);

        return [
            'is_opsi'    => 1,
            'opsi_label' => self::angkaKeLabel($urutan),
            'versi'      => $versi,
        ];
    }

    /** Urutan tertinggi dari sekumpulan label ('A','C' => 3). */
    private static function urutanTertinggi(array $labelTerpakai): int
    {
        $tertinggi = 0;
        foreach ($labelTerpakai as $label) {
            $n = self::labelKeAngka((string) $label);
            if ($n > $tertinggi) $tertinggi = $n;
        }
        return $tertinggi;
    }

    /** 1 => A, 2 => B, ... 27 => AA */
    public static function angkaKeLabel(int $n): string
    {
        if ($n < 1) $n = 1;
        $out = '';
        while ($n > 0) {
            $n--;
            $out = chr(65 + ($n % 26)) . $out;
            $n = intdiv($n, 26);
        }
        return $out;
    }

    /** A => 1, B => 2, ... AA => 27. Label tak dikenal dianggap 0. */
    private static function labelKeAngka(string $label): int
    {
        $label = strtoupper(trim($label));
        if ($label === '' || ! preg_match('/^[A-Z]+$/', $label)) return 0;

        $n = 0;
        foreach (str_split($label) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }
        return $n;
    }

    // ─────────────────────────────────────────────────────────────
    // Aksi
    // ─────────────────────────────────────────────────────────────

    /**
     * Desainer mengirim materi untuk ditinjau (draft/revision → review).
     */
    public function ajukan(int $itemId, array $user): array
    {
        $item = $this->temukan($itemId);
        if (! $item) return $this->gagal('Item tidak ditemukan.');

        $status = $item['status'] ?? 'draft';
        if (! in_array($status, ['draft', 'revision'], true)) {
            return $this->gagal($status === 'review'
                ? 'Materi ini sudah menunggu peninjauan.'
                : self::PESAN_TERKUNCI);
        }

        $opsi  = $this->opsiOf($itemId);
        $versi = (int) ($item['versi'] ?? 1) ?: 1;

        if (empty($opsi)) {
            return $this->gagal('Unggah minimal satu opsi desain sebelum mengajukan review.');
        }

        // Setelah diminta revisi, harus ada materi baru. Tanpa syarat ini
        // permintaan revisi bisa dipantulkan balik apa adanya — peninjau
        // ditagih ulang untuk gambar yang sudah ia tolak.
        if ($status === 'revision') {
            $baru = array_filter($opsi, fn($o) => (int) ($o['versi'] ?? 1) === $versi);
            if (empty($baru)) {
                return $this->gagal('Unggah dulu opsi hasil perbaikan sebelum mengajukan ulang — peninjau sudah menolak opsi yang ada.');
            }
        }

        $this->itemModel()->update($itemId, [
            'status'              => 'review',
            'review_submitted_at' => date('Y-m-d H:i:s'),
        ]);

        $this->catat($itemId, $versi, 'ajukan', null, null, null, count($opsi), $user);
        ActivityLog::write('submit', $this->logModule(), (string) $itemId, $item['nama'] ?? '', [
            'versi' => $versi, 'jumlah_opsi' => count($opsi),
        ]);

        $labelItem = $item['nama'] ?? 'Materi creative';
        $penerima  = OrgRecipients::orAdmins(OrgRecipients::merge(
            OrgRecipients::menuEditors($this->menuKey()),
            OrgRecipients::menuApprovers($this->menuKey())
        ));
        Notify::send(
            $penerima,
            (int) $user['id'],
            'creative',
            'approval',
            ($this->isEvent() ? 'Materi creative event' : 'Materi creative') . ' menunggu peninjauan: ' . $labelItem,
            ($user['name'] ?? '') . ' mengajukan ' . count($opsi) . ' opsi desain untuk ditinjau (v' . $versi . ').',
            $this->linkType(),
            $itemId,
            $this->itemUrl($itemId)
        );

        return $this->sukses('Materi diajukan untuk peninjauan (' . count($opsi) . ' opsi).');
    }

    /**
     * Peninjau menyetujui, sekaligus menetapkan opsi mana yang dipakai.
     *
     * @param int[] $fileIds Opsi yang dipilih. Boleh kosong bila kandidatnya
     *                       cuma satu — opsi tunggal itu dipilih otomatis.
     */
    public function setujui(int $itemId, array $fileIds, array $user, ?string $catatan = null): array
    {
        $item = $this->temukan($itemId);
        if (! $item) return $this->gagal('Item tidak ditemukan.');

        if (($item['status'] ?? '') !== 'review') {
            return $this->gagal('Hanya materi yang sedang menunggu peninjauan yang bisa disetujui.');
        }

        $opsi = $this->opsiOf($itemId);
        if (empty($opsi)) {
            return $this->gagal('Tidak ada opsi desain yang bisa disetujui.');
        }

        $idValid  = array_map('intval', array_column($opsi, 'id'));
        $terpilih = array_values(array_intersect($idValid, array_map('intval', $fileIds)));

        if (empty($terpilih)) {
            if (count($opsi) > 1) {
                return $this->gagal('Pilih dulu opsi desain mana yang disetujui.');
            }
            $terpilih = [$idValid[0]];
        }

        $labels = [];
        foreach ($opsi as $o) {
            if (in_array((int) $o['id'], $terpilih, true)) $labels[] = $o['opsi_label'];
        }
        $labelOpsi = implode(', ', array_filter($labels));

        $versi     = (int) ($item['versi'] ?? 1) ?: 1;
        $fileModel = $this->fileModel();
        $db        = db_connect();

        $db->transStart();
        // Tanda terpilih selalu ditulis ulang dari nol supaya tidak ada
        // sisa penanda dari putaran sebelumnya yang ikut terbawa.
        $fileModel->where('creative_item_id', $itemId)->set(['is_terpilih' => 0])->update();
        $fileModel->whereIn('id', $terpilih)->set(['is_terpilih' => 1])->update();
        $this->itemModel()->update($itemId, [
            'status'      => 'approved',
            'approved_by' => (int) $user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->gagal('Gagal menyimpan persetujuan. Silakan coba lagi.');
        }

        $this->catat($itemId, $versi, 'setujui', $catatan, null, $labelOpsi, count($opsi), $user);
        ActivityLog::write('approve', $this->logModule(), (string) $itemId, $item['nama'] ?? '', [
            'versi' => $versi, 'opsi_terpilih' => $labelOpsi,
        ]);

        $this->beriTahuPengaju(
            $item,
            $user,
            ($this->isEvent() ? 'Materi creative event' : 'Materi creative') . ' disetujui: ' . ($item['nama'] ?? '')
                . ($labelOpsi !== '' ? ' — opsi ' . $labelOpsi : ''),
            $catatan ?: null,
            $itemId
        );

        return $this->sukses('Materi disetujui' . ($labelOpsi !== '' ? ' — opsi ' . $labelOpsi : '') . '.');
    }

    /**
     * Peninjau meminta revisi. Alasan wajib; boleh menunjuk opsi mana
     * yang dijadikan basis ("lanjutkan opsi B, tapi warnanya terlalu
     * gelap") — feedback yang selama ini hilang total.
     */
    public function revisi(int $itemId, string $catatan, ?int $basisFileId, array $user): array
    {
        $item = $this->temukan($itemId);
        if (! $item) return $this->gagal('Item tidak ditemukan.');

        if (($item['status'] ?? '') !== 'review') {
            return $this->gagal('Hanya materi yang sedang menunggu peninjauan yang bisa diminta revisi.');
        }

        $catatan = trim($catatan);
        if (mb_strlen($catatan) < self::MIN_ALASAN) {
            return $this->gagal('Tuliskan alasan revisi minimal ' . self::MIN_ALASAN . ' karakter agar desainer tahu apa yang harus diperbaiki.');
        }

        $opsi      = $this->opsiOf($itemId);
        $idValid   = array_map('intval', array_column($opsi, 'id'));
        $basisFileId = ($basisFileId && in_array((int) $basisFileId, $idValid, true)) ? (int) $basisFileId : null;

        $basisLabel = null;
        if ($basisFileId) {
            foreach ($opsi as $o) {
                if ((int) $o['id'] === $basisFileId) { $basisLabel = $o['opsi_label']; break; }
            }
        }

        $versi = (int) ($item['versi'] ?? 1) ?: 1;

        // Versi naik saat revisi diminta, bukan saat diajukan ulang, supaya
        // file yang diunggah untuk perbaikan otomatis masuk putaran baru.
        $this->itemModel()->update($itemId, [
            'status'              => 'revision',
            'versi'               => $versi + 1,
            'review_submitted_at' => null,
        ]);

        $this->catat($itemId, $versi, 'revisi', $catatan, $basisFileId, $basisLabel, count($opsi), $user);
        ActivityLog::write('reject', $this->logModule(), (string) $itemId, $item['nama'] ?? '', [
            'versi' => $versi, 'basis_opsi' => $basisLabel,
        ]);

        $this->beriTahuPengaju(
            $item,
            $user,
            ($this->isEvent() ? 'Materi creative event' : 'Materi creative') . ' perlu revisi: ' . ($item['nama'] ?? ''),
            ($basisLabel ? 'Lanjutkan dari opsi ' . $basisLabel . '. ' : '') . $catatan,
            $itemId
        );

        return $this->sukses('Permintaan revisi terkirim ke pembuat materi.');
    }

    /**
     * Membuka kembali materi yang sudah disetujui agar bisa diubah.
     * Persetujuan lama dinyatakan batal: tanda opsi terpilih dilepas dan
     * versi naik, sehingga tidak ada materi "final" yang menggantung
     * sementara isinya sedang diubah.
     */
    public function buka(int $itemId, string $catatan, array $user): array
    {
        $item = $this->temukan($itemId);
        if (! $item) return $this->gagal('Item tidak ditemukan.');

        if (($item['status'] ?? '') !== 'approved') {
            return $this->gagal('Hanya materi yang sudah disetujui yang perlu dibuka kembali.');
        }

        $catatan = trim($catatan);
        if (mb_strlen($catatan) < self::MIN_ALASAN) {
            return $this->gagal('Tuliskan alasan membuka kembali minimal ' . self::MIN_ALASAN . ' karakter.');
        }

        $versi = (int) ($item['versi'] ?? 1) ?: 1;
        $db    = db_connect();

        $db->transStart();
        $this->fileModel()->where('creative_item_id', $itemId)->set(['is_terpilih' => 0])->update();
        $this->itemModel()->update($itemId, [
            'status'              => 'draft',
            'versi'               => $versi + 1,
            'approved_by'         => null,
            'approved_at'         => null,
            'review_submitted_at' => null,
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->gagal('Gagal membuka kembali materi. Silakan coba lagi.');
        }

        $this->catat($itemId, $versi, 'buka', $catatan, null, null, 0, $user);
        ActivityLog::write('reopen', $this->logModule(), (string) $itemId, $item['nama'] ?? '', [
            'versi' => $versi,
        ]);

        // Pembuat materi DAN penyetuju sebelumnya sama-sama perlu tahu
        // bahwa keputusan yang sudah diambil dibatalkan.
        $penerima = array_filter([
            (int) ($item['created_by'] ?? 0),
            (int) ($item['approved_by'] ?? 0),
        ]);
        if (! empty($penerima)) {
            Notify::send(
                array_values(array_unique($penerima)),
                (int) $user['id'],
                'creative',
                'result',
                'Persetujuan materi creative dibuka kembali: ' . ($item['nama'] ?? ''),
                ($user['name'] ?? '') . ': ' . $catatan,
                $this->linkType(),
                $itemId,
                $this->itemUrl($itemId)
            );
        }

        return $this->sukses('Materi dibuka kembali sebagai draft (v' . ($versi + 1) . '). Persetujuan sebelumnya dibatalkan.');
    }

    // ─────────────────────────────────────────────────────────────
    // Internal
    // ─────────────────────────────────────────────────────────────

    private function catat(
        int $itemId,
        int $versi,
        string $aksi,
        ?string $catatan,
        ?int $basisFileId,
        ?string $opsiLabel,
        int $jumlahOpsi,
        array $user
    ): void {
        (new CreativeReviewModel())->insert([
            'scope'         => $this->scope,
            'item_id'       => $itemId,
            'event_id'      => $this->isEvent() ? $this->eventId : null,
            'versi'         => $versi,
            'aksi'          => $aksi,
            'catatan'       => $catatan !== null && $catatan !== '' ? $catatan : null,
            'basis_file_id' => $basisFileId,
            'opsi_label'    => $opsiLabel !== null && $opsiLabel !== '' ? $opsiLabel : null,
            'jumlah_opsi'   => $jumlahOpsi,
            'oleh'          => (int) $user['id'],
        ]);
    }

    private function beriTahuPengaju(array $item, array $user, string $judul, ?string $isi, int $itemId): void
    {
        if (empty($item['created_by'])) return;

        Notify::send(
            [(int) $item['created_by']],
            (int) $user['id'],
            'creative',
            'result',
            $judul,
            $isi,
            $this->linkType(),
            $itemId,
            $this->itemUrl($itemId)
        );
    }

    private function sukses(string $pesan): array
    {
        return ['ok' => true, 'message' => $pesan];
    }

    private function gagal(string $pesan): array
    {
        return ['ok' => false, 'message' => $pesan];
    }
}
