<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalTemplateKpiModel extends Model
{
    protected $table         = 'appraisal_template_kpi';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['template_id', 'area', 'indikator', 'unit', 'bobot', 'target', 'urutan'];
    protected $useTimestamps = false;

    public function getByTemplate(int $templateId): array
    {
        return $this->where('template_id', $templateId)->orderBy('urutan')->orderBy('id')->findAll();
    }

    public function totalBobot(int $templateId): float
    {
        $row = $this->selectSum('bobot', 'total')->where('template_id', $templateId)->get()->getRow();
        return (float) ($row->total ?? 0);
    }
}
