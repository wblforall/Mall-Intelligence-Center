<?php

namespace App\Models;

use CodeIgniter\Model;

class EventTenantImpactModel extends Model
{
    protected $table      = 'event_tenant_impact';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'event_id', 'tenant_id', 'tracking_date',
        'actual_sales', 'receipts', 'voucher_redemptions', 'notes',
    ];
    protected $useTimestamps = true;

    public function getByEventAndTenant(int $eventId, int $tenantId): array
    {
        return $this->where('event_id', $eventId)
            ->where('tenant_id', $tenantId)
            ->orderBy('tracking_date')
            ->findAll();
    }

    public function getTotalsByTenant(int $eventId): array
    {
        return $this->db->table('event_tenant_impact eti')
            ->select('eti.tenant_id, t.name, t.category, t.baseline_sales, t.participating_promo, t.event_relevance,
                SUM(eti.actual_sales) as total_actual_sales,
                SUM(eti.receipts) as total_receipts,
                SUM(eti.voucher_redemptions) as total_voucher_redemptions')
            ->join('event_tenants t', 't.id = eti.tenant_id')
            ->where('eti.event_id', $eventId)
            ->groupBy('eti.tenant_id')
            ->get()->getResultArray();
    }
}
