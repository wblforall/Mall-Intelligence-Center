<?php

namespace App\Controllers;

use App\Libraries\ApprovalInbox;

/**
 * Kotak Persetujuan terpadu (/persetujuan) — satu tempat melihat seluruh item
 * yang menunggu keputusan user, lintas modul. Otorisasi tetap memakai
 * canEditMenu()/can() dari BaseController; halaman ini hanya mengagregasi.
 */
class Persetujuan extends BaseController
{
    public function index()
    {
        // Konteks kapabilitas dari BaseController::approvalContext() — satu
        // implementasi yang sama dengan badge sidebar & cron pengingat.
        $items = ApprovalInbox::collect($this->approvalContext());

        // Segarkan cache badge sidebar dengan angka terbaru (bukan menunggu TTL),
        // supaya badge langsung akurat setelah user menindaklanjuti item.
        session()->set('appr_badge', ['n' => count($items), 't' => time()]);

        // Rekap per modul untuk filter chip
        $perModul = [];
        foreach ($items as $it) {
            $perModul[$it['module']] = ($perModul[$it['module']] ?? 0) + 1;
        }

        return view('persetujuan/index', [
            'user'     => $this->currentUser(),
            'items'    => $items,
            'perModul' => $perModul,
            'modules'  => ApprovalInbox::MODULES,
            'urgent'   => count(array_filter($items, fn ($i) => $i['urgent'])),
        ]);
    }
}
