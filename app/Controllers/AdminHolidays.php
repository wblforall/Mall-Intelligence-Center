<?php

namespace App\Controllers;

use App\Models\PublicHolidayModel;
use App\Libraries\ActivityLog;

class AdminHolidays extends BaseController
{
    private PublicHolidayModel $model;

    public function __construct()
    {
        $this->model = new PublicHolidayModel();
    }

    public function index()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $year     = (int)($this->request->getGet('year') ?? date('Y'));
        $holidays = $this->model->getByYear($year);

        return view('admin/holidays/index', [
            'user'     => $this->currentUser(),
            'year'     => $year,
            'holidays' => $holidays,
        ]);
    }

    public function store()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $tanggal = $this->request->getPost('tanggal');
        $nama    = trim($this->request->getPost('nama'));
        $jenis   = $this->request->getPost('jenis') ?? 'nasional';

        if (! $tanggal || ! $nama) {
            return redirect()->back()->with('error', 'Tanggal dan nama wajib diisi.');
        }

        $this->model->insert(['tanggal' => $tanggal, 'nama' => $nama, 'jenis' => $jenis]);
        ActivityLog::write('create', 'public_holiday', null, $nama . ' (' . $tanggal . ')');

        $year = substr($tanggal, 0, 4);
        return redirect()->to("admin/holidays?year={$year}")->with('success', 'Hari libur ditambahkan.');
    }

    public function delete(int $id)
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $record = $this->model->find($id);
        if ($record) {
            $this->model->delete($id);
            ActivityLog::write('delete', 'public_holiday', (string)$id, $record['nama'] . ' (' . $record['tanggal'] . ')');
            $year = substr($record['tanggal'], 0, 4);
            return redirect()->to("admin/holidays?year={$year}")->with('success', 'Hari libur dihapus.');
        }
        return redirect()->to('admin/holidays')->with('error', 'Data tidak ditemukan.');
    }

    public function bulkStore()
    {
        if (! $this->isAdmin()) return redirect()->to('/');

        $raw  = trim($this->request->getPost('bulk_input') ?? '');
        $year = (int)($this->request->getPost('year') ?? date('Y'));

        if (! $raw) {
            return redirect()->back()->with('error', 'Input kosong.');
        }

        $lines   = preg_split('/\r?\n/', $raw);
        $inserted = 0;
        $errors   = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (! $line) continue;

            // Format: YYYY-MM-DD Nama Hari Libur [jenis]
            if (! preg_match('/^(\d{4}-\d{2}-\d{2})\s+(.+)$/', $line, $m)) {
                $errors[] = "Format salah: {$line}";
                continue;
            }

            $tanggal = $m[1];
            $rest    = trim($m[2]);

            // Optionally ends with |nasional|bersama|lokal
            $jenis = 'nasional';
            if (preg_match('/\|(nasional|bersama|lokal)$/i', $rest, $jm)) {
                $jenis = strtolower($jm[1]);
                $rest  = trim(substr($rest, 0, -strlen($jm[0])));
            }

            $this->model->insert(['tanggal' => $tanggal, 'nama' => $rest, 'jenis' => $jenis]);
            $inserted++;
        }

        ActivityLog::write('create', 'public_holiday', null, "bulk import {$inserted} hari libur");

        $msg = "{$inserted} hari libur berhasil ditambahkan.";
        if ($errors) $msg .= ' ' . count($errors) . ' baris diabaikan (format salah).';

        return redirect()->to("admin/holidays?year={$year}")->with('success', $msg);
    }
}
