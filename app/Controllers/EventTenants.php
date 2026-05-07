<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventTenantModel;
use App\Models\EventTenantImpactModel;
use App\Models\EventDailyTrackingModel;
use App\Libraries\ActivityLog;

class EventTenants extends BaseController
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
        if (! $this->canViewMenu('tenants')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Departemen Anda tidak memiliki akses ke menu ini.');
        }

        $user  = $this->currentUser();
        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $tenants = (new EventTenantModel())->getByEvent($eventId);
        return view('tenants/index', ['user' => $user, 'event' => $event, 'tenants' => $tenants, 'canEdit' => $this->canEditMenu('tenants')]);
    }

    public function store(int $eventId)
    {
        if (! $this->canEditMenu('tenants')) {
            return redirect()->to("/events/{$eventId}/tenants")->with('error', 'Akses ditolak.');
        }

        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post        = $this->request->getPost();
        $tenantModel = new EventTenantModel();
        $tenantModel->insert([
            'event_id'            => $eventId,
            'name'                => $post['name'],
            'category'            => $post['category'],
            'participating_promo' => isset($post['participating_promo']) ? 1 : 0,
            'baseline_sales'      => (int)str_replace([',', '.'], '', $post['baseline_sales'] ?? 0),
            'event_relevance'     => $post['event_relevance'] ?? 'Medium',
        ]);
        ActivityLog::write('create', 'event_tenant', (string)$tenantModel->getInsertID(), $post['name'], ['event_id' => $eventId]);

        return redirect()->to("/events/{$eventId}/tenants")->with('success', 'Tenant berhasil ditambahkan.');
    }

    public function update(int $eventId, int $tenantId)
    {
        if (! $this->canEditMenu('tenants')) {
            return redirect()->to("/events/{$eventId}/tenants")->with('error', 'Akses ditolak.');
        }

        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post = $this->request->getPost();
        (new EventTenantModel())->update($tenantId, [
            'name'                => $post['name'],
            'category'            => $post['category'],
            'participating_promo' => isset($post['participating_promo']) ? 1 : 0,
            'baseline_sales'      => (int)str_replace([',', '.'], '', $post['baseline_sales'] ?? 0),
            'event_relevance'     => $post['event_relevance'] ?? 'Medium',
        ]);
        ActivityLog::write('update', 'event_tenant', (string)$tenantId, $post['name'], ['event_id' => $eventId]);

        return redirect()->to("/events/{$eventId}/tenants")->with('success', 'Tenant berhasil diperbarui.');
    }

    public function delete(int $eventId, int $tenantId)
    {
        if (! $this->canEditMenu('tenants')) {
            return redirect()->to("/events/{$eventId}/tenants")->with('error', 'Akses ditolak.');
        }

        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $tenantModel = new EventTenantModel();
        $tenant      = $tenantModel->find($tenantId);
        $tenantModel->delete($tenantId);
        ActivityLog::write('delete', 'event_tenant', (string)$tenantId, $tenant['name'] ?? '', ['event_id' => $eventId]);
        return redirect()->to("/events/{$eventId}/tenants")->with('success', 'Tenant berhasil dihapus.');
    }

    public function impact(int $eventId)
    {
        if (! $this->canViewMenu('tenant_impact')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Departemen Anda tidak memiliki akses ke menu ini.');
        }

        $user  = $this->currentUser();
        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $tenants       = (new EventTenantModel())->getByEvent($eventId);
        $impactModel   = new EventTenantImpactModel();
        $impactTotals  = $impactModel->getTotalsByTenant($eventId);
        $trackingDates = array_column((new EventDailyTrackingModel())->getByEvent($eventId), 'tracking_date');

        // Map impact totals by tenant_id
        $impactMap = [];
        foreach ($impactTotals as $imp) {
            $impactMap[$imp['tenant_id']] = $imp;
        }

        return view('tenants/impact', [
            'user'          => $user,
            'event'         => $event,
            'tenants'       => $tenants,
            'impactMap'     => $impactMap,
            'trackingDates' => $trackingDates,
        ]);
    }

    public function saveImpact(int $eventId)
    {
        if (! $this->canEditMenu('tenant_impact')) {
            return redirect()->to("/events/{$eventId}/tenants/impact")->with('error', 'Akses ditolak.');
        }

        $event = $this->getEventOrFail($eventId);
        if (! $event) return redirect()->to('/events')->with('error', 'Akses ditolak.');

        $post        = $this->request->getPost();
        $impactModel = new EventTenantImpactModel();

        // Format: tenant_id[], tracking_date[], actual_sales[], receipts[], voucher_redemptions[]
        $tenantIds = $post['tenant_id'] ?? [];
        foreach ($tenantIds as $i => $tenantId) {
            $date = $post['tracking_date'][$i] ?? null;
            if (! $date || ! $tenantId) continue;

            $existing = $impactModel->where('event_id', $eventId)
                ->where('tenant_id', $tenantId)
                ->where('tracking_date', $date)
                ->first();

            $rowData = [
                'event_id'            => $eventId,
                'tenant_id'           => $tenantId,
                'tracking_date'       => $date,
                'actual_sales'        => (int)str_replace([',', '.'], '', $post['actual_sales'][$i] ?? 0),
                'receipts'            => (int)($post['receipts'][$i] ?? 0),
                'voucher_redemptions' => (int)($post['voucher_redemptions'][$i] ?? 0),
            ];

            if ($existing) {
                $impactModel->update($existing['id'], $rowData);
            } else {
                $impactModel->insert($rowData);
            }
        }

        ActivityLog::write('update', 'event_tenant_impact', (string)$eventId, $event['name'], ['count' => count($tenantIds)]);
        return redirect()->to("/events/{$eventId}/tenants/impact")->with('success', 'Data tenant impact berhasil disimpan.');
    }
}
