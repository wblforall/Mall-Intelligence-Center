<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\EventConfigModel;
use App\Libraries\ActivityLog;

class EventInputs extends BaseController
{
    public function index(int $eventId)
    {
        if (! $this->canViewMenu('inputs')) {
            return redirect()->to('/events')->with('error', 'Akses ditolak. Departemen Anda tidak memiliki akses ke menu ini.');
        }

        $user  = $this->currentUser();
        $event = (new EventModel())->find($eventId);

        if (! $event || ! (new EventModel())->canUserAccess($eventId, $user['id'], $user['role'])) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $configModel = new EventConfigModel();
        $config      = $configModel->getByEvent($eventId) ?? [];

        return view('inputs/index', [
            'user'   => $user,
            'event'  => $event,
            'config' => $config,
        ]);
    }

    public function save(int $eventId)
    {
        if (! $this->canEditMenu('inputs')) {
            return redirect()->to("/events/{$eventId}/inputs")->with('error', 'Akses ditolak. Departemen Anda tidak memiliki izin edit.');
        }

        $user  = $this->currentUser();
        $event = (new EventModel())->find($eventId);

        if (! $event || ! (new EventModel())->canUserAccess($eventId, $user['id'], $user['role'])) {
            return redirect()->to('/events')->with('error', 'Akses ditolak.');
        }

        $post = $this->request->getPost();

        $data = [
            'royalty_character'         => (int)str_replace([',', '.'], '', $post['royalty_character'] ?? 0),
            'operational_mg'            => (int)str_replace([',', '.'], '', $post['operational_mg'] ?? 0),
            'production_decor'          => (int)str_replace([',', '.'], '', $post['production_decor'] ?? 0),
            'promotion_media'           => (int)str_replace([',', '.'], '', $post['promotion_media'] ?? 0),
            'security_cost'             => (int)str_replace([',', '.'], '', $post['security_cost'] ?? 0),
            'other_cost'                => (int)str_replace([',', '.'], '', $post['other_cost'] ?? 0),
            'target_traffic_uplift'     => (float)($post['target_traffic_uplift'] ?? 0) / 100,
            'target_engagement_rate'    => (float)($post['target_engagement_rate'] ?? 0) / 100,
            'target_member_conversion'  => (float)($post['target_member_conversion'] ?? 0) / 100,
            'target_transaction_conv'   => (float)($post['target_transaction_conv'] ?? 0) / 100,
            'target_voucher_redemption' => (float)($post['target_voucher_redemption'] ?? 0) / 100,
            'target_sales_uplift'       => (float)($post['target_sales_uplift'] ?? 0) / 100,
            'target_sponsor_coverage'   => (float)($post['target_sponsor_coverage'] ?? 0) / 100,
            'target_roi_direct'         => (float)($post['target_roi_direct'] ?? 1),
            'target_repeat_visit'       => (float)($post['target_repeat_visit'] ?? 0) / 100,
        ];

        $configModel = new EventConfigModel();
        $existing    = $configModel->getByEvent($eventId);

        if ($existing) {
            $configModel->update($existing['id'], $data);
            ActivityLog::write('update', 'event_config', (string)$eventId, $event['name']);
        } else {
            $data['event_id'] = $eventId;
            $configModel->insert($data);
            ActivityLog::write('create', 'event_config', (string)$eventId, $event['name']);
        }

        return redirect()->to("/events/{$eventId}/baseline")->with('success', 'Konfigurasi biaya & target berhasil disimpan.');
    }
}
