<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventBaselineModel;
use App\Libraries\ActivityLog;

class EventBaseline extends BaseController
{
    public function index(int $eventId)
    {
        if (! $this->canViewMenu('baseline')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Departemen Anda tidak memiliki akses ke menu ini.');
        }

        $user  = $this->currentUser();
        $event = (new EventModel())->find($eventId);

        if (! $event || ! (new EventModel())->canUserAccess($eventId, $user['id'], $user['role'])) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $baselineModel = new EventBaselineModel();
        $baselines     = $baselineModel->getByEvent($eventId);

        return view('baseline/index', [
            'user'        => $user,
            'event'       => $event,
            'baselines'   => $baselines,
            'canEdit'     => $this->canEditMenu('baseline'),
            'sectionType' => $this->getSectionType('baseline'),
        ]);
    }

    public function save(int $eventId)
    {
        if (! $this->canEditMenu('baseline')) {
            return redirect()->to("/events/{$eventId}/baseline")->with('error', 'Akses ditolak. Departemen Anda tidak memiliki izin edit.');
        }

        $user  = $this->currentUser();
        $event = (new EventModel())->find($eventId);

        if (! $event || ! (new EventModel())->canUserAccess($eventId, $user['id'], $user['role'])) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $post  = $this->request->getPost();
        $model = new EventBaselineModel();

        // Process posted rows: day_label[], comparable_period[], day_type[], baseline_traffic[], etc.
        $labels = $post['day_label'] ?? [];
        foreach ($labels as $i => $label) {
            if (empty($label)) continue;

            $rowData = [
                'event_id'                     => $eventId,
                'day_label'                    => $label,
                'comparable_period'            => $post['comparable_period'][$i] ?? '',
                'day_type'                     => $post['day_type'][$i] ?? 'Weekday',
                'baseline_traffic'             => (int)($post['baseline_traffic'][$i] ?? 0),
                'baseline_event_area_visitors' => (int)($post['baseline_event_area_visitors'][$i] ?? 0),
                'baseline_transactions'        => (int)($post['baseline_transactions'][$i] ?? 0),
                'baseline_tenant_sales'        => (int)str_replace([',', '.', ' '], '', $post['baseline_tenant_sales'][$i] ?? 0),
                'baseline_parking_revenue'     => (int)str_replace([',', '.', ' '], '', $post['baseline_parking_revenue'][$i] ?? 0),
            ];

            $existing = $model->getByDayLabel($eventId, $label);
            if ($existing) {
                $model->update($existing['id'], $rowData);
            } else {
                $model->insert($rowData);
            }
        }

        ActivityLog::write('update', 'event_baseline', (string)$eventId, $event['name'], ['rows' => count($labels)]);

        return redirect()->to("/events/{$eventId}/baseline")->with('success', 'Data baseline berhasil disimpan.');
    }
}
