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
    /** Konteks kapabilitas user untuk ApprovalInbox. */
    private function ctx(): array
    {
        $user = $this->currentUser();
        $uid  = (int) ($user['id'] ?? 0);

        $emp = db_connect()->table('employees')->select('id')->where('user_id', $uid)->get()->getRowArray();

        return ApprovalInbox::contextFor([
            'user_id'                 => $uid,
            'employee_id'             => (int) ($emp['id'] ?? 0),
            'is_admin'                => $this->isAdmin(),
            'can_approve_promo_media' => $this->can('can_approve_promo_media'),
            'can_approve_events'      => $this->can('can_approve_events'),
            'can_approve_pip'         => $this->can('can_approve_pip'),
            'is_hr'                   => $this->canEditMenu('hr_main') || $this->canEditMenu('people_dev'),
            'can_legal'               => $this->canEditMenu('legal'),
        ]);
    }

    public function index()
    {
        $items = ApprovalInbox::collect($this->ctx());

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
