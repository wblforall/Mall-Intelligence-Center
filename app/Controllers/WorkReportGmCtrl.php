<?php

namespace App\Controllers;

use App\Libraries\ActivityLog;
use App\Models\WorkInitiativeModel;
use App\Models\WorkInitiativeCommentModel;

class WorkReportGmCtrl extends BaseController
{
    private WorkInitiativeModel        $m;
    private WorkInitiativeCommentModel $mc;

    public function __construct()
    {
        $this->m  = new WorkInitiativeModel();
        $this->mc = new WorkInitiativeCommentModel();
    }

    // ── Halaman GM ────────────────────────────────────────────────────────
    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp = $this->currentEmployee();
        if (! $emp || ! $this->isGm($emp)) {
            return redirect()->to('/work-report')->with('error', 'Halaman ini hanya untuk GM.');
        }

        $items = $this->m->forGm();

        // Kelompokkan per divisi
        $byDivisi = [];
        foreach ($items as $item) {
            $key = $item['divisi_name'] ?? 'Tanpa Divisi';
            $byDivisi[$key][] = $item;
        }
        ksort($byDivisi);

        // Ambil thread GM ↔ Deputy per inisiatif
        $threads = [];
        foreach ($items as $item) {
            $t = $this->mc->gmDeputyThread((int) $item['id']);
            if ($t) $threads[$item['id']] = $t;
        }

        // Badge unread: reply Deputy yang belum GM baca
        $initiativeIds = array_column($items, 'id');
        $gmUnread = [];
        $db = \Config\Database::connect();
        if ($initiativeIds) {
            $uid = (int) session()->get('user_id');
            $reads = $db->table('work_initiative_reads')
                ->whereIn('initiative_id', $initiativeIds)
                ->where('user_id', $uid)
                ->get()->getResultArray();
            $readMap = array_column($reads, 'last_read_deputy_at', 'initiative_id');

            $rows = $db->table('work_initiative_comments')
                ->select('initiative_id, MAX(created_at) AS latest_at, COUNT(*) AS total')
                ->where('visibility', 'gm_deputy')
                ->whereIn('initiative_id', $initiativeIds)
                ->where('author_id !=', (int) $emp['id'])  // hanya pesan dari Deputy
                ->groupBy('initiative_id')
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $lastRead = $readMap[$r['initiative_id']] ?? null;
                if (! $lastRead || $r['latest_at'] > $lastRead) {
                    $gmUnread[$r['initiative_id']] = $lastRead
                        ? $db->table('work_initiative_comments')
                            ->where('initiative_id', $r['initiative_id'])
                            ->where('visibility', 'gm_deputy')
                            ->where('author_id !=', (int) $emp['id'])
                            ->where('created_at >', $lastRead)
                            ->countAllResults()
                        : (int) $r['total'];
                }
            }

            // Mark semua sebagai terbaca (thread tampil inline di halaman ini)
            $now = date('Y-m-d H:i:s');
            foreach ($initiativeIds as $iid) {
                $existing = $db->table('work_initiative_reads')
                    ->where('initiative_id', $iid)->where('user_id', $uid)->get()->getRowArray();
                if ($existing) {
                    $db->table('work_initiative_reads')
                        ->where('initiative_id', $iid)->where('user_id', $uid)
                        ->update(['last_read_deputy_at' => $now]);
                } else {
                    $db->table('work_initiative_reads')
                        ->insert(['initiative_id' => $iid, 'user_id' => $uid, 'last_read_deputy_at' => $now]);
                }
            }
        }

        return view('work_report/gm', [
            'byDivisi' => $byDivisi,
            'threads'  => $threads,
            'empId'    => (int) $emp['id'],
            'gmUnread' => $gmUnread,
        ]);
    }

    // ── GM tambah catatan ke Deputy ───────────────────────────────────────
    public function addNote(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->canViewMenu('work_report')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $emp  = $this->currentEmployee();
        $item = $this->m->find($id);

        if (! $emp || ! $this->isGm($emp) || ! $item) {
            return redirect()->to('/work-report/gm')->with('error', 'Akses ditolak.');
        }

        $body = trim($this->request->getPost('body') ?? '');
        if ($body === '') return redirect()->to('/work-report/gm')->with('error', 'Catatan tidak boleh kosong.');

        $this->mc->insert([
            'initiative_id' => $id,
            'parent_id'     => null,
            'body'          => $body,
            'author_id'     => (int) $emp['id'],
            'visibility'    => 'gm_deputy',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/work-report/gm#initiative-' . $id)->with('success', 'Catatan dikirim ke Deputy.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function currentEmployee(): ?array
    {
        $uid = session()->get('user_id');
        if (! $uid) return null;
        return \Config\Database::connect()
            ->table('employees')
            ->select('employees.*, j.grade, j.nama AS jabatan_nama')
            ->join('jabatans j', 'j.id = employees.jabatan_id', 'left')
            ->where('employees.user_id', $uid)
            ->get()->getRowArray();
    }

    private function isGm(array $emp): bool
    {
        return str_contains(strtolower($emp['jabatan_nama'] ?? ''), 'general manager');
    }
}
