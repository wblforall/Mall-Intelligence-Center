<?php

namespace App\Controllers\Api;

use App\Models\EventModel;
use App\Models\PromoMediaUsageModel;

class DashboardController extends BaseApiController
{
    public function summary()
    {
        if (! $this->requireAuth()) return $this->response;

        $eventModel = new EventModel();
        $events     = $eventModel->findAll();

        $counts = ['draft' => 0, 'active' => 0, 'waiting_data' => 0, 'completed' => 0];
        foreach ($events as $event) {
            $status = $event['status'] ?? 'draft';
            if (isset($counts[$status])) $counts[$status]++;
        }

        $pending_approvals = (new PromoMediaUsageModel())
            ->where('approval_status', 'pending')
            ->countAllResults();

        return $this->success([
            'events'            => $counts,
            'total_events'      => count($events),
            'pending_approvals' => $pending_approvals,
        ]);
    }
}
