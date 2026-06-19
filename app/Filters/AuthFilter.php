<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Force-logout: jika akun dinonaktifkan ATAU akses diubah admin setelah user login,
        // sesi lama diputus & user diminta login ulang (agar akses baru langsung berlaku).
        $uid = (int) session()->get('user_id');
        if ($uid) {
            $loginTime = (int) (session()->get('login_time') ?: 0);
            if ($loginTime === 0) { session()->set('login_time', time()); }
            else {
                $u = db_connect()->table('users')->select('is_active, perms_changed_at')->where('id', $uid)->get()->getRowArray();
                $changed = (! empty($u['perms_changed_at']) && strtotime($u['perms_changed_at']) > $loginTime);
                if (! $u || (int) $u['is_active'] === 0 || $changed) {
                    session()->destroy();
                    return redirect()->to('/login')->with('error', $u && (int) $u['is_active'] === 0
                        ? 'Akun Anda dinonaktifkan. Hubungi admin.'
                        : 'Akses Anda diperbarui. Silakan login ulang.');
                }
            }
        }

        // Wajib ganti password: kunci akses ke semua halaman kecuali ganti-password & logout.
        if (session()->get('must_change_password') && ! in_array(uri_string(), ['change-password', 'logout'], true)) {
            return redirect()->to('/change-password')->with('error', 'Demi keamanan, Anda wajib mengganti password terlebih dahulu.');
        }

        // Role check: auth:admin — role_is_admin flag OR legacy user_role='admin'
        if ($arguments && in_array('admin', $arguments)) {
            $isAdmin = session()->get('role_is_admin') || session()->get('user_role') === 'admin';
            if (! $isAdmin) {
                return redirect()->to('/')->with('error', 'Akses ditolak. Hanya admin yang diizinkan.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
