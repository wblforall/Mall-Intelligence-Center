<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DepartmentMenuModel;
use App\Models\RoleModel;
use App\Models\LoginLogModel;
use App\Models\PasswordResetModel;
use App\Libraries\ActivityLog;
use CodeIgniter\Controller;

class Auth extends Controller
{
    public function index()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/');
        }
        return view('auth/login');
    }

    public function login()
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $model = new UserModel();
        $user  = $model->findByEmail($email);

        if (! $user) {
            ActivityLog::write('login_failed', 'auth', null, 'Login gagal — email tidak ditemukan', ['email' => $email]);
            return redirect()->back()->with('error', 'Email atau password salah.')->withInput();
        }

        if (! $user['is_active']) {
            return redirect()->back()->with('error', 'Akun Anda dinonaktifkan.')->withInput();
        }

        // Cek apakah akun terkunci
        if (! empty($user['locked_until'])) {
            $lockedUntil = strtotime($user['locked_until']);
            if ($lockedUntil > time()) {
                $menitLagi = (int)ceil(($lockedUntil - time()) / 60);
                return redirect()->back()
                    ->with('error', "Akun terkunci karena terlalu banyak percobaan login. Coba lagi dalam {$menitLagi} menit.")
                    ->withInput();
            }
            // Lock sudah kadaluarsa — reset otomatis
            $model->update($user['id'], ['failed_login_attempts' => 0, 'locked_until' => null]);
            $user['failed_login_attempts'] = 0;
        }

        if (! password_verify($password, $user['password'])) {
            $attempts = (int)($user['failed_login_attempts'] ?? 0) + 1;
            $maxAttempts = 5;

            if ($attempts >= $maxAttempts) {
                $model->update($user['id'], [
                    'failed_login_attempts' => $attempts,
                    'locked_until'          => date('Y-m-d H:i:s', strtotime('+15 minutes')),
                ]);
                ActivityLog::write('login_failed', 'auth', (string)$user['id'], 'Akun dikunci setelah ' . $attempts . ' percobaan gagal');
                return redirect()->back()
                    ->with('error', 'Akun dikunci selama 15 menit karena terlalu banyak percobaan login yang gagal.')
                    ->withInput();
            }

            $model->update($user['id'], ['failed_login_attempts' => $attempts]);
            $sisa = $maxAttempts - $attempts;
            ActivityLog::write('login_failed', 'auth', (string)$user['id'], "Login gagal (percobaan ke-{$attempts})");
            return redirect()->back()
                ->with('error', "Email atau password salah. {$sisa} percobaan lagi sebelum akun dikunci.")
                ->withInput();
        }

        // Login berhasil — reset counter
        $model->update($user['id'], ['failed_login_attempts' => 0, 'locked_until' => null]);

        // Load role permissions
        $rolePerms = ['is_admin' => false, 'can_create_event' => false, 'can_delete_event' => false, 'can_manage_users' => false];
        if (! empty($user['role_id'])) {
            $role = (new RoleModel())->find((int)$user['role_id']);
            if ($role) {
                $rolePerms = RoleModel::buildPerms($role);
            }
        } elseif ($user['role'] === 'admin') {
            // Legacy fallback for users without role_id yet
            $rolePerms = ['is_admin' => true, 'can_create_event' => true, 'can_delete_event' => true, 'can_manage_users' => true];
        }

        // Load department menu access map for non-admin users
        $deptMenus = null;
        if (! $rolePerms['is_admin'] && ! empty($user['department_id'])) {
            $deptMenus = (new DepartmentMenuModel())->getMenuMap((int)$user['department_id']);
        }

        session()->set([
            'logged_in'      => true,
            'user_id'        => $user['id'],
            'user_name'      => $user['name'],
            'user_email'     => $user['email'],
            'user_role'      => $user['role'],
            'role_is_admin'  => $rolePerms['is_admin'],
            'role_perms'     => $rolePerms,
            'dept_id'        => $user['department_id'],
            'dept_menus'     => $deptMenus,
            'user_theme'     => $user['theme'] ?? 'dark',
        ]);

        (new LoginLogModel())->record((int)$user['id']);
        ActivityLog::write('login', 'auth', (string)$user['id'], $user['name']);

        if (! empty($user['must_change_password'])) {
            return redirect()->to('/change-password');
        }

        return redirect()->to('/')->with('show_greeting', true);
    }

    public function changePassword()
    {
        if (! session()->get('logged_in')) return redirect()->to('/login');
        return view('auth/change_password');
    }

    public function savePassword()
    {
        if (! session()->get('logged_in')) return redirect()->to('/login');

        $password    = $this->request->getPost('password');
        $confirm     = $this->request->getPost('password_confirm');

        if ($password !== $confirm) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak cocok.');
        }

        $errors = [];
        if (strlen($password) < 8)                          $errors[] = 'Minimal 8 karakter.';
        if (! preg_match('/[A-Z]/', $password))             $errors[] = 'Minimal 1 huruf kapital.';
        if (! preg_match('/[a-z]/', $password))             $errors[] = 'Minimal 1 huruf kecil.';
        if (! preg_match('/[0-9]/', $password))             $errors[] = 'Minimal 1 angka.';
        if (! preg_match('/[\W_]/', $password))             $errors[] = 'Minimal 1 karakter simbol (!@#$% dll).';

        if ($errors) {
            return redirect()->back()->with('pw_errors', $errors);
        }

        $userId = session()->get('user_id');
        (new UserModel())->update($userId, [
            'password'             => password_hash($password, PASSWORD_BCRYPT),
            'must_change_password' => 0,
        ]);
        ActivityLog::write('update', 'user', (string)$userId, 'change_password_on_first_login');

        return redirect()->to('/')->with('show_greeting', true);
    }

    public function forgotPassword()
    {
        if (session()->get('logged_in')) return redirect()->to('/');
        return view('auth/forgot_password');
    }

    public function sendResetLink()
    {
        $email = trim($this->request->getPost('email'));
        $user  = (new UserModel())->findByEmail($email);

        if ($user && $user['is_active']) {
            $token    = (new PasswordResetModel())->createToken($email);
            $resetUrl = base_url('reset-password/' . $token);

            $emailer = \Config\Services::email();
            $emailer->setTo($email);
            $emailer->setSubject('Reset Password — Mall Intelligence Center');
            $emailer->setMessage($this->buildResetEmail($user['name'], $resetUrl));
            $emailer->setMailType('html');
            $emailer->send();
        }

        // Selalu tampilkan pesan sukses untuk keamanan (tidak bocorkan apakah email terdaftar)
        return redirect()->to('/forgot-password')
            ->with('success', 'Jika email terdaftar, link reset password telah dikirim. Cek inbox (dan folder spam) Anda.');
    }

    public function resetPassword(string $token)
    {
        $record = (new PasswordResetModel())->findValid($token);
        if (! $record) {
            return view('auth/reset_invalid');
        }
        return view('auth/reset_password', ['token' => $token]);
    }

    public function processReset(string $token)
    {
        $model  = new PasswordResetModel();
        $record = $model->findValid($token);
        if (! $record) {
            return view('auth/reset_invalid');
        }

        $password = $this->request->getPost('password');
        $confirm  = $this->request->getPost('password_confirm');

        if ($password !== $confirm) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak cocok.');
        }

        $errors = [];
        if (strlen($password) < 8)                $errors[] = 'Minimal 8 karakter.';
        if (! preg_match('/[A-Z]/', $password))   $errors[] = 'Minimal 1 huruf kapital.';
        if (! preg_match('/[a-z]/', $password))   $errors[] = 'Minimal 1 huruf kecil.';
        if (! preg_match('/[0-9]/', $password))   $errors[] = 'Minimal 1 angka.';
        if (! preg_match('/[\W_]/', $password))   $errors[] = 'Minimal 1 karakter simbol.';

        if ($errors) {
            return redirect()->back()->with('pw_errors', $errors);
        }

        $user = (new UserModel())->findByEmail($record['email']);
        if ($user) {
            (new UserModel())->update($user['id'], [
                'password'             => password_hash($password, PASSWORD_BCRYPT),
                'must_change_password' => 0,
            ]);
            ActivityLog::write('update', 'user', (string)$user['id'], 'reset_password');
        }

        $model->consume($token);
        return redirect()->to('/login')->with('success', 'Password berhasil direset. Silakan login.');
    }

    private function buildResetEmail(string $name, string $url): string
    {
        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px">
            <div style="font-size:11pt;color:#6b7280;margin-bottom:4px">Mall Intelligence Center</div>
            <h2 style="margin:0 0 16px;font-size:16pt;color:#111827">Reset Password</h2>
            <p style="color:#374151">Halo <strong>{$name}</strong>,</p>
            <p style="color:#374151">Kami menerima permintaan reset password untuk akun Anda. Klik tombol di bawah untuk membuat password baru:</p>
            <div style="text-align:center;margin:28px 0">
                <a href="{$url}" style="background:#2563eb;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:11pt">Reset Password</a>
            </div>
            <p style="color:#6b7280;font-size:9.5pt">Link ini berlaku selama <strong>1 jam</strong>. Jika Anda tidak meminta reset password, abaikan email ini.</p>
            <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0">
            <p style="color:#9ca3af;font-size:8.5pt;margin:0">PT. Wulandari Bangun Laksana Tbk.</p>
        </div>
        HTML;
    }

    public function logout()
    {
        ActivityLog::write('logout', 'auth', (string)session()->get('user_id'), session()->get('user_name'));
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Anda berhasil logout.');
    }
}
