<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventBaselineModel;
use App\Models\EventDailyTrackingModel;
use App\Libraries\ActivityLog;

class EventTracking extends BaseController
{
    private function getEventOrFail(int $eventId)
    {
        $user  = $this->currentUser();
        $event = (new EventModel())->find($eventId);
        if (! $event || ! (new EventModel())->canUserAccess($eventId, $user['id'], $user['role'])) {
            return null;
        }
        return $event;
    }

    public function index(int $eventId)
    {
        if (! $this->canViewMenu('tracking')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Departemen Anda tidak memiliki akses ke menu ini.');
        }

        $user  = $this->currentUser();
        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $trackingModel = new EventDailyTrackingModel();
        $baselineModel = new EventBaselineModel();
        $rows          = $trackingModel->getByEvent($eventId);
        $baselines     = $baselineModel->getByEvent($eventId);

        // Map baselines by day number
        $baselineMap = [];
        foreach ($baselines as $b) {
            $num = (int)str_replace('DAY-', '', $b['day_label']);
            $baselineMap[$num] = $b;
        }

        // Enrich rows with baseline data
        foreach ($rows as &$row) {
            $bl = $baselineMap[$row['day_number']] ?? null;
            $row['baseline_traffic']      = $bl['baseline_traffic'] ?? 0;
            $row['baseline_tenant_sales'] = $bl['baseline_tenant_sales'] ?? 0;
            $row['baseline_parking']      = $bl['baseline_parking_revenue'] ?? 0;
            $row['engaged_visitors']      = (int)$row['mg_registration'] + (int)$row['photo_game_participants'] + (int)$row['qr_scans'];
            $row['traffic_uplift']        = $row['baseline_traffic'] > 0 && $row['actual_traffic']
                ? (($row['actual_traffic'] - $row['baseline_traffic']) / $row['baseline_traffic'])
                : null;
            $row['engagement_rate']       = $row['event_area_visitors'] > 0
                ? ($row['engaged_visitors'] / $row['event_area_visitors'])
                : null;
            $row['total_direct_revenue']  = (int)$row['sponsor_revenue'] + (int)$row['booth_cl_revenue'] + (int)$row['media_revenue'];
        }

        return view('tracking/index', [
            'user'        => $user,
            'event'       => $event,
            'rows'        => $rows,
            'baselines'   => $baselines,
            'canEdit'     => $this->canEditMenu('tracking'),
            'sectionType' => $this->getSectionType('tracking'),
        ]);
    }

    public function add(int $eventId)
    {
        if (! $this->canEditMenu('tracking')) {
            return redirect()->to("/events/{$eventId}/tracking")->with('error', 'Akses ditolak. Departemen Anda tidak memiliki izin edit.');
        }

        $user  = $this->currentUser();
        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $baselines   = (new EventBaselineModel())->getByEvent($eventId);
        $existingDays = array_column((new EventDailyTrackingModel())->getByEvent($eventId), 'day_number');

        // Generate available day slots based on event_days
        $availableDays = [];
        for ($i = 1; $i <= $event['event_days']; $i++) {
            if (! in_array($i, $existingDays)) {
                $date = date('Y-m-d', strtotime($event['start_date'] . " +" . ($i - 1) . " days"));
                $availableDays[] = ['number' => $i, 'date' => $date];
            }
        }

        return view('tracking/form', [
            'user'          => $user,
            'event'         => $event,
            'baselines'     => $baselines,
            'availableDays' => $availableDays,
            'row'           => null,
            'sectionType'   => $this->getSectionType('tracking'),
        ]);
    }

    public function store(int $eventId)
    {
        if (! $this->canEditMenu('tracking')) {
            return redirect()->to("/events/{$eventId}/tracking")->with('error', 'Akses ditolak.');
        }

        $user  = $this->currentUser();
        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();

        $model = new EventDailyTrackingModel();
        $model->insert([
            'event_id'              => $eventId,
            'tracking_date'         => $post['tracking_date'],
            'day_number'            => (int)$post['day_number'],
            'day_type'              => $post['day_type'],
            'actual_traffic'        => $post['actual_traffic'] !== '' ? (int)$post['actual_traffic'] : null,
            'event_area_visitors'   => $post['event_area_visitors'] !== '' ? (int)$post['event_area_visitors'] : null,
            'mg_registration'       => (int)($post['mg_registration'] ?? 0),
            'photo_game_participants'=> (int)($post['photo_game_participants'] ?? 0),
            'qr_scans'              => (int)($post['qr_scans'] ?? 0),
            'new_pam_members'       => (int)($post['new_pam_members'] ?? 0),
            'voucher_claims'        => (int)($post['voucher_claims'] ?? 0),
            'voucher_redemptions'   => (int)($post['voucher_redemptions'] ?? 0),
            'receipt_uploads'       => (int)($post['receipt_uploads'] ?? 0),
            'actual_tenant_sales'   => $post['actual_tenant_sales'] !== '' ? (int)str_replace([',', '.'], '', $post['actual_tenant_sales']) : null,
            'sponsor_revenue'       => (int)str_replace([',', '.'], '', $post['sponsor_revenue'] ?? 0),
            'booth_cl_revenue'      => (int)str_replace([',', '.'], '', $post['booth_cl_revenue'] ?? 0),
            'media_revenue'         => (int)str_replace([',', '.'], '', $post['media_revenue'] ?? 0),
            'parking_actual'        => $post['parking_actual'] !== '' ? (int)str_replace([',', '.'], '', $post['parking_actual']) : null,
            'notes'                 => $post['notes'] ?? null,
            'created_by'            => $user['id'],
        ]);
        ActivityLog::write('create', 'event_tracking', (string)$model->getInsertID(), 'Day ' . $post['day_number'], ['event_id' => $eventId]);

        return redirect()->to("/events/{$eventId}/tracking")->with('success', 'Data hari berhasil disimpan.');
    }

    public function edit(int $eventId, int $rowId)
    {
        if (! $this->canEditMenu('tracking')) {
            return redirect()->to("/events/{$eventId}/tracking")->with('error', 'Akses ditolak.');
        }

        $user  = $this->currentUser();
        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $model = new EventDailyTrackingModel();
        $row   = $model->find($rowId);
        if (! $row || $row['event_id'] != $eventId) {
            return redirect()->to("/events/{$eventId}/tracking")->with('error', 'Data tidak ditemukan.');
        }

        $baselines = (new EventBaselineModel())->getByEvent($eventId);

        return view('tracking/form', [
            'user'          => $user,
            'event'         => $event,
            'baselines'     => $baselines,
            'availableDays' => [],
            'row'           => $row,
            'sectionType'   => $this->getSectionType('tracking'),
        ]);
    }

    public function update(int $eventId, int $rowId)
    {
        if (! $this->canEditMenu('tracking')) {
            return redirect()->to("/events/{$eventId}/tracking")->with('error', 'Akses ditolak.');
        }

        $user  = $this->currentUser();
        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post  = $this->request->getPost();
        $model = new EventDailyTrackingModel();

        $row = $model->find($rowId);
        $model->update($rowId, [
            'day_type'              => $post['day_type'],
            'actual_traffic'        => $post['actual_traffic'] !== '' ? (int)$post['actual_traffic'] : null,
            'event_area_visitors'   => $post['event_area_visitors'] !== '' ? (int)$post['event_area_visitors'] : null,
            'mg_registration'       => (int)($post['mg_registration'] ?? 0),
            'photo_game_participants'=> (int)($post['photo_game_participants'] ?? 0),
            'qr_scans'              => (int)($post['qr_scans'] ?? 0),
            'new_pam_members'       => (int)($post['new_pam_members'] ?? 0),
            'voucher_claims'        => (int)($post['voucher_claims'] ?? 0),
            'voucher_redemptions'   => (int)($post['voucher_redemptions'] ?? 0),
            'receipt_uploads'       => (int)($post['receipt_uploads'] ?? 0),
            'actual_tenant_sales'   => $post['actual_tenant_sales'] !== '' ? (int)str_replace([',', '.'], '', $post['actual_tenant_sales']) : null,
            'sponsor_revenue'       => (int)str_replace([',', '.'], '', $post['sponsor_revenue'] ?? 0),
            'booth_cl_revenue'      => (int)str_replace([',', '.'], '', $post['booth_cl_revenue'] ?? 0),
            'media_revenue'         => (int)str_replace([',', '.'], '', $post['media_revenue'] ?? 0),
            'parking_actual'        => $post['parking_actual'] !== '' ? (int)str_replace([',', '.'], '', $post['parking_actual']) : null,
            'notes'                 => $post['notes'] ?? null,
        ]);
        ActivityLog::write('update', 'event_tracking', (string)$rowId, 'Day ' . ($row['day_number'] ?? ''), ['event_id' => $eventId]);

        return redirect()->to("/events/{$eventId}/tracking")->with('success', 'Data berhasil diperbarui.');
    }

    public function delete(int $eventId, int $rowId)
    {
        $user  = $this->currentUser();
        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $trackingModel = new EventDailyTrackingModel();
        $row = $trackingModel->find($rowId);
        $trackingModel->delete($rowId);
        ActivityLog::write('delete', 'event_tracking', (string)$rowId, 'Day ' . ($row['day_number'] ?? ''), ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/tracking")->with('success', 'Data berhasil dihapus.');
    }
}
