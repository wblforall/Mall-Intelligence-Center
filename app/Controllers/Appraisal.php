<?php

namespace App\Controllers;

use App\Models\AppraisalTemplateModel;
use App\Models\AppraisalPeriodModel;

class Appraisal extends BaseController
{
    private function isHr(): bool { return $this->isAdmin() || $this->canViewMenu('hr_main'); }

    public function index()
    {
        if (! $this->isHr()) return redirect()->to('/')->with('error', 'Akses ditolak.');

        $templateModel = new AppraisalTemplateModel();
        $periodModel   = new AppraisalPeriodModel();

        $tplStats = [
            'total'     => $templateModel->countAllResults(false),
            'approved'  => $templateModel->where('status', 'approved')->countAllResults(false),
            'submitted' => $templateModel->where('status', 'submitted')->countAllResults(),
        ];
        $periods = $periodModel->orderBy('created_at', 'DESC')->findAll();

        return view('appraisal/index', [
            'user'     => $this->currentUser(),
            'tplStats' => $tplStats,
            'periods'  => $periods,
        ]);
    }
}
