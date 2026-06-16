<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalFormKpiModel extends Model
{
    protected $table         = 'appraisal_form_kpi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['form_id', 'area', 'indikator', 'unit', 'bobot', 'target', 'realisasi', 'skor', 'urutan'];
    protected $useTimestamps = false;

    public function getByForm(int $formId): array
    {
        return $this->where('form_id', $formId)->orderBy('urutan')->orderBy('id')->findAll();
    }
}
