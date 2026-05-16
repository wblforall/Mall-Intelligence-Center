<?php

namespace App\Models;

use CodeIgniter\Model;

class PipReviewModel extends Model
{
    protected $table      = 'pip_reviews';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['pip_id','tanggal_review','reviewer_name','progres','catatan','created_at'];

    public function getByPip(int $pipId): array
    {
        return $this->where('pip_id', $pipId)->orderBy('tanggal_review', 'DESC')->findAll();
    }
}
