<?php

namespace App\Libraries;

class ActivityLog
{
    public static function write(
        string  $action,
        string  $module,
        ?string $targetId    = null,
        ?string $targetLabel = null,
        array   $detail      = []
    ): void {
        $session = session();
        $ip      = service('request')->getIPAddress();
        $loopback = ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'];
        $computerName = in_array($ip, $loopback) ? 'localhost' : $ip;

        db_connect()->table('activity_logs')->insert([
            'user_id'      => $session->get('user_id'),
            'user_name'    => $session->get('user_name') ?? 'System',
            'user_role'    => $session->get('user_role') ?? '',
            'action'       => $action,
            'module'       => $module,
            'target_id'    => $targetId,
            'target_label' => $targetLabel,
            'detail'       => !empty($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null,
            'ip_address'    => $ip,
            'computer_name' => $computerName,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
