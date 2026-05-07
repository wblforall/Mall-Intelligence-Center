<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;

class Logs extends BaseController
{
    private const PER_PAGE = 50;

    public function index()
    {
        if (! $this->can('can_view_logs')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $model   = new ActivityLogModel();
        $filters = [
            'module'  => $this->request->getGet('module')  ?? '',
            'action'  => $this->request->getGet('action')  ?? '',
            'user_id' => $this->request->getGet('user_id') ?? '',
            'from'    => $this->request->getGet('from')    ?? '',
            'to'      => $this->request->getGet('to')      ?? '',
            'q'       => $this->request->getGet('q')       ?? '',
            'page'    => $this->request->getGet('page')    ?? 1,
        ];

        $total   = $model->countFiltered($filters);
        $logs    = $model->getFiltered($filters, self::PER_PAGE);
        $pages   = (int) ceil($total / self::PER_PAGE);

        return view('logs/index', [
            'user'     => $this->currentUser(),
            'logs'     => $logs,
            'filters'  => $filters,
            'total'    => $total,
            'pages'    => $pages,
            'perPage'  => self::PER_PAGE,
            'modules'  => $model->getModules(),
            'users'    => $model->getActiveUsers(),
        ]);
    }
}
