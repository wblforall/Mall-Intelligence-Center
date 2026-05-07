<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\DailyTrafficModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $user   = $this->currentUser();
        $model  = new EventModel();
        $events = $model->getEventsForUser($user['id'], $user['role']);

        $counts = [
            'total'     => count($events),
            'active'    => count(array_filter($events, fn($e) => $e['status'] === 'active')),
            'draft'     => count(array_filter($events, fn($e) => $e['status'] === 'draft')),
            'completed' => count(array_filter($events, fn($e) => $e['status'] === 'completed')),
        ];

        $today      = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $trafficModel = new DailyTrafficModel();

        $traffic = [
            'ewalk' => [
                'today'       => $trafficModel->getPeriodTotal($today, $today, 'ewalk'),
                'month'       => $trafficModel->getPeriodTotal($monthStart, $today, 'ewalk'),
                'last_date'   => $trafficModel->getLatestDate('ewalk'),
            ],
            'pentacity' => [
                'today'       => $trafficModel->getPeriodTotal($today, $today, 'pentacity'),
                'month'       => $trafficModel->getPeriodTotal($monthStart, $today, 'pentacity'),
                'last_date'   => $trafficModel->getLatestDate('pentacity'),
            ],
        ];

        return view('dashboard/index', [
            'user'    => $user,
            'events'  => $events,
            'counts'  => $counts,
            'traffic' => $traffic,
            'today'   => $today,
        ]);
    }
}
