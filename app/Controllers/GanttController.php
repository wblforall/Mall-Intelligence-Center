<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\ThemePeriodModel;
use App\Models\TnaPeriodModel;
use App\Models\TrainingProgramModel;
use App\Models\SponsorshipProgramModel;
use App\Models\LoyaltyProgramModel;
use App\Models\EeiPeriodModel;
use App\Models\PublicHolidayModel;

class GanttController extends BaseController
{
    public function index()
    {
        if (! session()->get('logged_in')) return redirect()->to('/login');
        if (! $this->can('can_view_gantt'))  return redirect()->to('/events');

        $year = (int)($this->request->getGet('year') ?? date('Y'));

        return view('gantt/index', [
            'user' => $this->currentUser(),
            'year' => $year,
        ]);
    }

    public function data(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (! session()->get('logged_in') || ! $this->can('can_view_gantt')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $year = (int)($this->request->getGet('year') ?? date('Y'));
        $from = "{$year}-01-01";
        $to   = "{$year}-12-31";

        $tasks = [];

        // Events
        if ($this->canViewMenu('events')) {
            $events = (new EventModel())->getByPeriod($from, $to, $this->canApproveEvents());
            foreach ($events as $e) {
                $days    = max(1, (int)$e['event_days']);
                $endDate = date('Y-m-d', strtotime($e['start_date'] . " +{$days} days"));
                $tasks[] = [
                    'id'           => 'ev-' . $e['id'],
                    'name'         => $e['name'],
                    'start'        => $e['start_date'],
                    'end'          => $endDate,
                    'progress'     => 0,
                    'group'        => 'Events',
                    'custom_class' => 'task-events',
                ];
            }
        }

        // People: TNA, PIP, Training, EEI
        if ($this->canViewMenu('people_dev')) {
            $tnas = (new TnaPeriodModel())
                ->where('tanggal_mulai <=', $to)
                ->where('tanggal_selesai >=', $from)
                ->orderBy('tanggal_mulai', 'ASC')
                ->findAll();
            foreach ($tnas as $t) {
                $tasks[] = [
                    'id'           => 'tna-' . $t['id'],
                    'name'         => '[TNA] ' . $t['nama'],
                    'start'        => $t['tanggal_mulai'],
                    'end'          => date('Y-m-d', strtotime($t['tanggal_selesai'] . ' +1 day')),
                    'progress'     => 0,
                    'group'        => 'People',
                    'custom_class' => 'task-people',
                ];
            }

            $pips = db_connect()->table('pip_plans p')
                ->join('employees e', 'e.id = p.employee_id', 'left')
                ->select('p.id, p.judul, p.tanggal_mulai, p.tanggal_selesai, e.nama as nama_karyawan')
                ->where('p.tanggal_mulai <=', $to)
                ->where('p.tanggal_selesai >=', $from)
                ->orderBy('p.tanggal_mulai', 'ASC')
                ->get()->getResultArray();
            foreach ($pips as $p) {
                $label   = $p['judul'] ?? '-';
                $kary    = $p['nama_karyawan'] ? ' (' . $p['nama_karyawan'] . ')' : '';
                $tasks[] = [
                    'id'           => 'pip-' . $p['id'],
                    'name'         => '[PIP] ' . $label . $kary,
                    'start'        => $p['tanggal_mulai'],
                    'end'          => date('Y-m-d', strtotime($p['tanggal_selesai'] . ' +1 day')),
                    'progress'     => 0,
                    'group'        => 'People',
                    'custom_class' => 'task-people',
                ];
            }

            $trainings = (new TrainingProgramModel())
                ->where('tanggal_mulai <=', $to)
                ->where('tanggal_selesai >=', $from)
                ->orderBy('tanggal_mulai', 'ASC')
                ->findAll();
            foreach ($trainings as $t) {
                $tasks[] = [
                    'id'           => 'tr-' . $t['id'],
                    'name'         => '[Training] ' . $t['nama'],
                    'start'        => $t['tanggal_mulai'],
                    'end'          => date('Y-m-d', strtotime($t['tanggal_selesai'] . ' +1 day')),
                    'progress'     => 0,
                    'group'        => 'People',
                    'custom_class' => 'task-people',
                ];
            }

            $eeis = (new EeiPeriodModel())
                ->where('start_date <=', $to)
                ->where('end_date >=', $from)
                ->orderBy('start_date', 'ASC')
                ->findAll();
            foreach ($eeis as $e) {
                $tasks[] = [
                    'id'           => 'eei-' . $e['id'],
                    'name'         => '[EEI] ' . $e['nama'],
                    'start'        => $e['start_date'],
                    'end'          => date('Y-m-d', strtotime($e['end_date'] . ' +1 day')),
                    'progress'     => 0,
                    'group'        => 'People',
                    'custom_class' => 'task-people',
                ];
            }
        }

        // Sponsorship
        if ($this->canViewMenu('sponsorship_main')) {
            $sps = (new SponsorshipProgramModel())
                ->where('tanggal_mulai <=', $to)
                ->where('tanggal_selesai >=', $from)
                ->orderBy('tanggal_mulai', 'ASC')
                ->findAll();
            foreach ($sps as $s) {
                $tasks[] = [
                    'id'           => 'sp-' . $s['id'],
                    'name'         => $s['nama_program'],
                    'start'        => $s['tanggal_mulai'],
                    'end'          => date('Y-m-d', strtotime($s['tanggal_selesai'] . ' +1 day')),
                    'progress'     => 0,
                    'group'        => 'Sponsorship',
                    'custom_class' => 'task-sponsorship',
                ];
            }
        }

        // Loyalty
        if ($this->canViewMenu('loyalty_main')) {
            $loyalties = (new LoyaltyProgramModel())
                ->where('tanggal_mulai <=', $to)
                ->where('tanggal_selesai >=', $from)
                ->orderBy('tanggal_mulai', 'ASC')
                ->findAll();
            foreach ($loyalties as $l) {
                $tasks[] = [
                    'id'           => 'loy-' . $l['id'],
                    'name'         => $l['nama_program'],
                    'start'        => $l['tanggal_mulai'],
                    'end'          => date('Y-m-d', strtotime($l['tanggal_selesai'] . ' +1 day')),
                    'progress'     => 0,
                    'group'        => 'Loyalty',
                    'custom_class' => 'task-loyalty',
                ];
            }
        }

        // VM deadlines (per-item deadline from event_vm_items, event-level access)
        if ($this->canViewMenu('vm') || $this->canViewMenu('vm_main')) {
            $vms = db_connect()->table('event_vm_items ev')
                ->join('events e', 'e.id = ev.event_id')
                ->select('ev.id, ev.nama_item, ev.tanggal_deadline, e.name as event_name')
                ->where('ev.tanggal_deadline IS NOT NULL')
                ->where('ev.tanggal_deadline >=', $from)
                ->where('ev.tanggal_deadline <=', $to)
                ->orderBy('ev.tanggal_deadline', 'ASC')
                ->get()->getResultArray();
            foreach ($vms as $v) {
                $tasks[] = [
                    'id'           => 'vm-' . $v['id'],
                    'name'         => '[VM] ' . $v['nama_item'],
                    'start'        => $v['tanggal_deadline'],
                    'end'          => date('Y-m-d', strtotime($v['tanggal_deadline'] . ' +1 day')),
                    'progress'     => 0,
                    'group'        => 'VM',
                    'custom_class' => 'task-vm',
                ];
            }
        }

        usort($tasks, fn($a, $b) => strcmp($a['start'], $b['start']));

        // Theme periods for background overlay
        $rawThemes = (new ThemePeriodModel())
            ->where('start_date <=', $to)
            ->where('end_date >=', $from)
            ->where('is_active', 1)
            ->orderBy('start_date', 'ASC')
            ->findAll();

        $themeData = array_map(fn($t) => [
            'nama'         => $t['nama'],
            'start_date'   => $t['start_date'],
            'end_date'     => $t['end_date'],
            'notice_start' => date('Y-m-d', strtotime($t['start_date'] . ' -' . (int)$t['alert_days'] . ' days')),
            'alert_days'   => (int)$t['alert_days'],
        ], $rawThemes);

        $holidays = (new PublicHolidayModel())->getByRange($from, $to);

        return $this->response->setJSON([
            'tasks'         => $tasks,
            'theme_periods' => $themeData,
            'holidays'      => $holidays,
        ]);
    }
}
