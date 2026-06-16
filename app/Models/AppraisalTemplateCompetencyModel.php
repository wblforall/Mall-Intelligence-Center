<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalTemplateCompetencyModel extends Model
{
    protected $table         = 'appraisal_template_competency';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['template_id', 'nama_aspek', 'deskripsi', 'urutan'];
    protected $useTimestamps = false;

    public function getByTemplate(int $templateId): array
    {
        return $this->where('template_id', $templateId)->orderBy('urutan')->orderBy('id')->findAll();
    }
}
