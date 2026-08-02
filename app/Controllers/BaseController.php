<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = ['tanggal'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (session()->has('user_id')) {
            $db = db_connect();
            $wc = $db->table('events')->where('approval_status', 'approved')->where('status', 'waiting_data')->countAllResults();
            $pc = 0;
            if ($this->canApproveEvents()) {
                $pc = $db->table('events')->where('approval_status', 'pending')->countAllResults();
            }
            // Inbox Appraisal: form yang menunggu aksi user ini (penilai/reviewer)
            $apprInbox = 0; $apprShowMenu = false;
            if ($db->tableExists('appraisal_forms')) {
                $uid = session()->get('user_id');
                $apprInbox = $db->table('appraisal_forms')
                    ->where('current_user_id', $uid)
                    ->whereIn('status', ['input', 'in_review'])
                    ->countAllResults();
                $myEmp = $db->table('employees')->select('id')->where('user_id', $uid)->get()->getRowArray();
                $isAtasan = $myEmp ? $db->table('employees')->where('atasan_id', $myEmp['id'])->countAllResults() : 0;
                // dept head / deputy yang ditunjuk menyusun template
                $isAuthor = ($db->tableExists('appraisal_template_authors')
                        && $db->table('appraisal_template_authors')->where('user_id', $uid)->countAllResults())
                    || ($db->tableExists('appraisal_division_deputies')
                        && $db->table('appraisal_division_deputies')->where('user_id', $uid)->countAllResults());
                $apprShowMenu = $apprInbox > 0 || $isAtasan > 0;
            }
            // Inbox HR: pengajuan perubahan data yang menunggu persetujuan
            $changeReqCount = 0;
            if ($this->isAdmin() || $this->canEditMenu('people_dev') || $this->canEditMenu('hr_main')) {
                if ($db->tableExists('employee_change_requests')) {
                    $changeReqCount += $db->table('employee_change_requests')->where('status', 'pending')->countAllResults();
                }
                if ($db->tableExists('employee_documents')) {
                    $changeReqCount += $db->table('employee_documents')->where('status', 'pending')->countAllResults();
                }
            }

            \CodeIgniter\Config\Services::renderer()->setData([
                '_waitingDataCount'   => $wc,
                '_pendingApprovalCount' => $pc,
                '_apprInboxCount'     => $apprInbox,
                '_apprShowMenu'       => $apprShowMenu,
                '_apprIsAuthor'       => $isAuthor ?? false,
                '_changeReqCount'     => $changeReqCount,
            ], 'raw');
        }
    }

    protected function currentUser(): array
    {
        return [
            'id'    => session()->get('user_id'),
            'name'  => session()->get('user_name'),
            'email' => session()->get('user_email'),
            'role'  => session()->get('user_role'),
        ];
    }

    /* Hak menyetujui = izin role (lama, berlaku ke semua pemegang role)
       ATAU grant "Setujui" pada menu terkait (baru, bisa per orang). */
    protected function canApproveEvents(): bool
    {
        if ($this->isAdmin()) return true;
        $perms = session()->get('role_perms') ?? [];
        return (bool)($perms['can_approve_events'] ?? false) || $this->canApproveMenu('events');
    }

    protected function canApprovePromoMedia(): bool
    {
        if ($this->isAdmin()) return true;
        $perms = session()->get('role_perms') ?? [];
        return (bool)($perms['can_approve_promo_media'] ?? false) || $this->canApproveMenu('creative_main');
    }

    protected function canApproveLegal(): bool
    {
        if ($this->isAdmin()) return true;
        $perms = session()->get('role_perms') ?? [];
        return (bool)($perms['can_approve_legal'] ?? false) || $this->canApproveMenu('legal');
    }

    protected function canApprovePip(): bool
    {
        if ($this->isAdmin()) return true;
        $perms = session()->get('role_perms') ?? [];
        return (bool)($perms['can_approve_pip'] ?? false) || $this->canApproveMenu('people_dev');
    }

    /**
     * Boleh memutuskan materi creative (approve / minta revisi).
     * Gerbang lama memakai kolom users.role ('admin'/'manager') yang tak bisa
     * diatur dari UI dan berlaku ke semua manager lintas dept; kini grant
     * "Setujui" pada menu creative juga berlaku, sehingga hak ini bisa
     * diarahkan ke orang yang tepat. Gerbang lama dipertahankan dulu agar
     * tak ada yang mendadak kehilangan akses.
     */
    protected function canApproveCreative(bool $perEvent = false): bool
    {
        if ($this->isAdmin()) return true;
        if (in_array(session()->get('user_role') ?? '', ['admin', 'manager'], true)) return true;
        return $this->canApproveMenu($perEvent ? 'creative' : 'creative_main');
    }

    protected function isAdmin(): bool
    {
        return session()->get('role_is_admin') || session()->get('user_role') === 'admin';
    }

    /**
     * Konteks kapabilitas Kotak Persetujuan untuk user yang login.
     *
     * Sengaja mendelegasikan ke ApprovalInbox::contextForUser() (berbasis DB)
     * alih-alih merakit ulang dari session: aturan akses jadi punya SATU
     * implementasi yang dipakai halaman, badge, cron, dan kelak API mobile.
     * Perakitan terpisah sebelumnya membuat badge kehilangan employee_id
     * sehingga item PIP/IDP tak ikut terhitung.
     */
    protected function approvalContext(): array
    {
        return \App\Libraries\ApprovalInbox::contextForUser((int) session()->get('user_id'));
    }

    protected function can(string $perm): bool
    {
        if ($this->isAdmin()) return true;
        $perms = session()->get('role_perms') ?? [];
        return (bool)($perms[$perm] ?? false);
    }

    // Returns true if user can view the menu — dept_menus is sole authority for non-admin
    protected function canViewMenu(string $menuKey): bool
    {
        if ($this->isAdmin()) return true;
        // Grant per-user (override) — additive di atas akses dept.
        $um = session()->get('user_menus');
        if (isset($um[$menuKey]) && $um[$menuKey]['can_view']) return true;
        $menus = session()->get('dept_menus');
        // Non-admin tanpa departemen = TIDAK punya akses (hanya admin/superadmin yang full).
        if ($menus === null) return false;
        return isset($menus[$menuKey]) && $menus[$menuKey]['can_view'];
    }

    // Returns true if user can edit the menu — dept_menus + grant per-user (override)
    protected function canEditMenu(string $menuKey): bool
    {
        if ($this->isAdmin()) return true;
        $um = session()->get('user_menus');
        if (isset($um[$menuKey]) && $um[$menuKey]['can_edit']) return true;
        $menus = session()->get('dept_menus');
        if ($menus === null) return false; // non-admin tanpa dept = tidak boleh edit
        return isset($menus[$menuKey]) && $menus[$menuKey]['can_edit'];
    }

    /**
     * Boleh MENYETUJUI pada sebuah menu — tingkat akses ketiga setelah
     * lihat & ubah. Aturannya sama: admin → grant per-user → grant dept.
     *
     * Bersifat ADITIF terhadap izin lama di tabel `roles` (can_approve_*):
     * pemanggil menggabungkan keduanya, sehingga hak yang sudah berjalan
     * lewat role tidak berubah, sementara hak setuju kini juga bisa
     * diberikan ke SATU orang tanpa harus mengubah role bersama.
     */
    protected function canApproveMenu(string $menuKey): bool
    {
        if ($this->isAdmin()) return true;
        $um = session()->get('user_menus');
        if (isset($um[$menuKey]) && ! empty($um[$menuKey]['can_approve'])) return true;
        $menus = session()->get('dept_menus');
        if ($menus === null) return false;
        return isset($menus[$menuKey]) && ! empty($menus[$menuKey]['can_approve']);
    }

    // Returns the section_type for the user's dept + menu ('all' for admin or no dept)
    protected function getSectionType(string $menuKey): string
    {
        if ($this->isAdmin()) return 'all';
        $menus = session()->get('dept_menus');
        if ($menus === null) return 'all';
        return $menus[$menuKey]['section_type'] ?? 'all';
    }

    protected function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    protected function pct(float $value): string
    {
        return number_format($value * 100, 1) . '%';
    }

    const MIME_IMAGE = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    const MIME_DOC   = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    const MIME_CSV   = ['text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values'];

    protected function validateUpload(\CodeIgniter\HTTP\Files\UploadedFile $file, array $allowedMimes, int $maxMB = 10): ?string
    {
        if (! $file->isValid() || $file->hasMoved()) {
            return 'File tidak valid.';
        }
        if ($file->getSizeByUnit('mb') > $maxMB) {
            return "Ukuran file maksimal {$maxMB}MB.";
        }
        if (! in_array($file->getMimeType(), $allowedMimes)) {
            return 'Tipe file tidak diizinkan: ' . $file->getMimeType() . '.';
        }
        return null;
    }

    protected function safeExt(\CodeIgniter\HTTP\Files\UploadedFile $file): string
    {
        $mimeMap = [
            'image/jpeg'       => 'jpg',
            'image/png'        => 'png',
            'image/webp'       => 'webp',
            'image/gif'        => 'gif',
            'application/pdf'  => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/csv'         => 'csv',
            'video/mp4'        => 'mp4',
            'video/quicktime'  => 'mov',
        ];
        return $mimeMap[$file->getMimeType()] ?? 'bin';
    }
}
