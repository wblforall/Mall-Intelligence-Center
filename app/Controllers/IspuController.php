<?php

namespace App\Controllers;

use App\Libraries\Ispu;

/**
 * Endpoint JSON ISPU Balikpapan untuk widget Dashboard.
 * GET dashboard/ispu            → data (cache 10 menit; salinan terakhir bila sumber gagal)
 * GET dashboard/ispu?segarkan=1 → paksa ambil ulang (hanya admin)
 */
class IspuController extends BaseController
{
    public function index()
    {
        $paksa = (bool) $this->request->getGet('segarkan')
            && (session()->get('role_is_admin') || session()->get('user_role') === 'admin');

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(Ispu::ambil($paksa));
    }
}
