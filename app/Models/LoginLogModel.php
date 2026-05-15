<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginLogModel extends Model
{
    protected $table         = 'login_logs';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id', 'ip', 'hostname', 'browser', 'browser_ver', 'platform', 'device_type', 'device_name', 'login_at'];
    protected $useTimestamps = false;

    public function record(int $userId): void
    {
        $request = \Config\Services::request();
        $agent   = $request->getUserAgent();

        $ip       = $request->getIPAddress();
        $hostname = @gethostbyaddr($ip);
        if ($hostname === $ip) $hostname = null; // resolve gagal → simpan null

        $deviceType = 'desktop';
        $deviceName = null;
        if ($agent->isMobile()) {
            $deviceType = 'mobile';
            $deviceName = $agent->mobile() ?: null;
        } elseif (method_exists($agent, 'isTablet') && $agent->isTablet()) {
            $deviceType = 'tablet';
            $deviceName = $agent->mobile() ?: null;
        }

        $this->insert([
            'user_id'     => $userId,
            'ip'          => $ip,
            'hostname'    => $hostname,
            'browser'     => $agent->getBrowser() ?: null,
            'browser_ver' => $agent->getVersion()  ?: null,
            'platform'    => $agent->getPlatform()  ?: null,
            'device_type' => $deviceType,
            'device_name' => $deviceName,
            'login_at'    => date('Y-m-d H:i:s'),
        ]);

        // Update last_login_at on users
        db_connect()->table('users')->where('id', $userId)->update(['last_login_at' => date('Y-m-d H:i:s')]);

        // Keep only 20 most recent logs per user
        $ids = $this->select('id')->where('user_id', $userId)
                    ->orderBy('login_at', 'DESC')->limit(20)->findAll();
        if (count($ids) >= 20) {
            $keepIds = array_column($ids, 'id');
            $this->where('user_id', $userId)->whereNotIn('id', $keepIds)->delete();
        }
    }

    public function getByUser(int $userId, int $limit = 10): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('login_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
}
