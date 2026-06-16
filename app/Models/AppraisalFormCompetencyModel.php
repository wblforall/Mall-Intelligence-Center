<?php

namespace App\Models;

use CodeIgniter\Model;

class AppraisalFormCompetencyModel extends Model
{
    protected $table         = 'appraisal_form_competency';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['form_id', 'nama_aspek', 'deskripsi', 'nilai', 'urutan'];
    protected $useTimestamps = false;

    public function getByForm(int $formId): array
    {
        return $this->where('form_id', $formId)->orderBy('urutan')->orderBy('id')->findAll();
    }
}
