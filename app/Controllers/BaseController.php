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
    protected $helpers = [];

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
            \CodeIgniter\Config\Services::renderer()->setData([
                '_waitingDataCount'   => $wc,
                '_pendingApprovalCount' => $pc,
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

    protected function canApproveEvents(): bool
    {
        if ($this->isAdmin()) return true;
        $perms = session()->get('role_perms') ?? [];
        return (bool)($perms['can_approve_events'] ?? false);
    }

    protected function isAdmin(): bool
    {
        return session()->get('role_is_admin') || session()->get('user_role') === 'admin';
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
        $menus = session()->get('dept_menus');
        if ($menus === null) return true; // no dept assigned = unrestricted
        return isset($menus[$menuKey]) && $menus[$menuKey]['can_view'];
    }

    // Returns true if user can edit the menu — dept_menus is sole authority for non-admin
    protected function canEditMenu(string $menuKey): bool
    {
        if ($this->isAdmin()) return true;
        $menus = session()->get('dept_menus');
        if ($menus === null) return true;
        return isset($menus[$menuKey]) && $menus[$menuKey]['can_edit'];
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
