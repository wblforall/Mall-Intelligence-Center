<?php

namespace App\Controllers;

use App\Models\AppSettingsModel;
use App\Libraries\ActivityLog;
use App\Libraries\EmailNotifier;

class AdminSettings extends BaseController
{
    public function index()
    {
        $model = new AppSettingsModel();
        return view('admin/settings', [
            'user'             => $this->currentUser(),
            'trafficEmails'    => $model->getEmails('traffic_summary_emails'),
        ]);
    }

    public function save()
    {
        $post   = $this->request->getPost();
        $raw    = trim($post['traffic_emails'] ?? '');
        $emails = array_values(array_filter(
            array_map('trim', preg_split('/[\s,;]+/', $raw)),
            fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        ));

        (new AppSettingsModel())->setSetting('traffic_summary_emails', json_encode($emails));
        ActivityLog::write('update', 'app_settings', 'traffic_summary_emails', count($emails) . ' emails');
        return redirect()->to('/admin/settings')->with('success', count($emails) . ' alamat email disimpan.');
    }

    public function testEmail()
    {
        $user  = $this->currentUser();
        $email = $user['email'] ?? null;
        if (! $email) {
            return redirect()->to('/admin/settings')->with('error', 'Akun Anda tidak memiliki alamat email.');
        }

        $data    = ['eWalk' => 15234, 'Pentacity' => 9871];
        $body    = EmailNotifier::trafficSummary($data, date('Y-m-d'));
        EmailNotifier::send($email, '[TEST] Traffic Summary — ' . date('d M Y'), $body);
        return redirect()->to('/admin/settings')->with('success', 'Email tes dikirim ke ' . $email . '.');
    }
}
