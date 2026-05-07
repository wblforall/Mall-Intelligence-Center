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
