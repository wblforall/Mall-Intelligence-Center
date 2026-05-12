<?php

namespace App\Controllers;

use App\Models\DivisionModel;
use App\Models\DepartmentModel;

class PeopleOrgChart extends BaseController
{
    public function index()
    {
        if (! $this->canViewMenu('people_dev')) return redirect()->to('/events')->with('error', 'Akses ditolak.');
        $db = db_connect();

        $divisions = (new DivisionModel())->orderBy('nama')->findAll();
        $depts     = (new DepartmentModel())->orderBy('name')->findAll();

        $jabatans  = $db->table('jabatans')
            ->orderBy('grade')->orderBy('nama')
            ->get()->getResultArray();

        $employees = $db->table('employees')
            ->select('id, nama, jabatan_id, dept_id, foto')
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get()->getResultArray();

        // Map employees by jabatan_id
        $empByJab = [];
        foreach ($employees as $e) {
            if ($e['jabatan_id']) {
                $empByJab[(int)$e['jabatan_id']][] = $e;
            }
        }

        // Attach employees to jabatans, bucket by level
        $topJabs  = []; // no dept_id, no division_id (company-level)
        $divJabs  = []; // division_id only
        $deptJabs = []; // dept_id only

        foreach ($jabatans as $j) {
            $j['employees'] = $empByJab[(int)$j['id']] ?? [];
            if (! $j['dept_id'] && ! $j['division_id']) {
                $topJabs[] = $j;
            } elseif ($j['division_id'] && ! $j['dept_id']) {
                $divJabs[(int)$j['division_id']][] = $j;
            } elseif ($j['dept_id']) {
                $deptJabs[(int)$j['dept_id']][] = $j;
            }
        }

        // Attach jabatans + depts to divisions
        $divMap = [];
        foreach ($divisions as $div) {
            $div['jabatans']    = $divJabs[(int)$div['id']] ?? [];
            $div['departments'] = [];
            $divMap[(int)$div['id']] = $div;
        }

        $noDivDepts = [];
        foreach ($depts as $d) {
            $d['jabatans'] = $deptJabs[(int)$d['id']] ?? [];
            if ($d['division_id'] && isset($divMap[(int)$d['division_id']])) {
                $divMap[(int)$d['division_id']]['departments'][] = $d;
            } else {
                $noDivDepts[] = $d;
            }
        }

        return view('people/orgchart/index', [
            'user'       => $this->currentUser(),
            'topJabs'    => $topJabs,
            'divisions'  => array_values($divMap),
            'noDivDepts' => $noDivDepts,
            'stats'      => [
                'employees' => count($employees),
                'depts'     => count($depts),
                'jabatans'  => count($jabatans),
                'divisions' => count($divisions),
            ],
        ]);
    }
}
