<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'email', 'password', 'role', 'role_id', 'department_id', 'is_active', 'must_change_password', 'failed_login_attempts', 'locked_until', 'theme', 'last_login_at'];
    protected $useTimestamps = true;

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    public function getActiveUsers(): array
    {
        return $this->where('is_active', 1)->findAll();
    }
}
