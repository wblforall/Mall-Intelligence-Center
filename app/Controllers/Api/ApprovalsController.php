<?php

namespace App\Controllers\Api;

use App\Libraries\ApprovalInbox;

/**
 * Kotak Persetujuan untuk app mobile — cermin API dari halaman web
 * `/persetujuan` (Fase 0 §5 MOBILE_APP_PLAN.md).
 *
 * Otorisasi TIDAK ditulis ulang di sini. Konteks kapabilitas dibangun oleh
 * `ApprovalInbox::contextForUser()` yang membaca aturan dari DB lewat
 * `MenuAccess` — sumber yang sama dengan web dan cron pengingat. Kalau di web
 * seseorang tak boleh memutuskan sesuatu, di app pun tidak muncul.
 */
class ApprovalsController extends BaseApiController
{
    public function index()
    {
        if (! $this->requireAuth()) return $this->response;

        $ctx = ApprovalInbox::contextForUser((int) $this->apiUser['id']);

        // contextForUser() mengembalikan [] bila akun tak aktif. requireAuth()
        // sudah menyaring itu, tapi jangan mengandalkan urutan pemeriksaan di
        // tempat lain — kotak kosong lebih aman daripada notice PHP.
        $items = $ctx ? ApprovalInbox::collect($ctx) : [];

        // Filter per modul — app memakai ini untuk chip filter tanpa
        // mengunduh ulang seluruh daftar.
        // getGet() mengembalikan array bila klien mengirim `?module[]=x`;
        // memaksanya jadi string akan memicu warning "Array to string".
        $modulRaw = $this->request->getGet('module');
        $modul    = is_string($modulRaw) ? trim($modulRaw) : '';
        if ($modul !== '') {
            if (! isset(ApprovalInbox::MODULES[$modul])) {
                return $this->error('Modul tidak dikenal: ' . $modul, 422);
            }
            $items = array_values(array_filter($items, static fn ($i) => $i['module'] === $modul));
        }

        // Rekap dihitung SEBELUM pemotongan limit, supaya angka badge tetap
        // benar walau app hanya menarik halaman pertama.
        $total    = count($items);
        $mendesak = count(array_filter($items, static fn ($i) => $i['urgent']));

        // Daftar sudah terurut terlama dulu oleh collect(), jadi item pertama
        // adalah yang paling lama menunggu.
        $tertua = $items[0]['since'] ?? null;

        $perModul = [];
        foreach ($items as $it) {
            $perModul[$it['module']] = ($perModul[$it['module']] ?? 0) + 1;
        }

        $limitRaw = $this->request->getGet('limit');
        $limit    = is_scalar($limitRaw) ? (int) $limitRaw : 0;
        if ($limit > 0) $items = array_slice($items, 0, $limit);

        return $this->success([
            'total'       => $total,
            'urgent'      => $mendesak,
            'oldest_days' => ApprovalInbox::ageDays($tertua),
            'modules'     => $this->rekapModul($perModul),
            'items'       => array_map([$this, 'sajikan'], $items),
        ]);
    }

    /** Metadata modul + jumlah item, hanya untuk modul yang memang ada isinya. */
    private function rekapModul(array $perModul): array
    {
        $out = [];
        foreach (ApprovalInbox::MODULES as $key => [$label, $icon, $warna]) {
            if (empty($perModul[$key])) continue;
            $out[] = [
                'key'   => $key,
                'label' => $label,
                'icon'  => $icon,
                'color' => $warna,
                'count' => $perModul[$key],
            ];
        }
        return $out;
    }

    /**
     * Bentuk satu item untuk app. `url` tetap relatif (dipakai app untuk
     * deep-link internal), `web_url` absolut sebagai jalan keluar: banyak modul
     * belum punya layar di app dan harus dibuka lewat webview.
     */
    private function sajikan(array $it): array
    {
        [$label, $icon, $warna] = ApprovalInbox::MODULES[$it['module']] ?? [$it['module'], 'bi-dot', 'gray'];

        return [
            'module'       => $it['module'],
            'module_label' => $label,
            'icon'         => $icon,
            'color'        => $warna,
            'source'       => $it['source'],
            'id'           => $it['id'],
            'title'        => $it['title'],
            'subtitle'     => $it['subtitle'],
            'since'        => $it['since'],
            'age_days'     => ApprovalInbox::ageDays($it['since']),
            'urgent'       => $it['urgent'],
            'url'          => $it['url'],
            'web_url'      => base_url($it['url']),
        ];
    }
}
