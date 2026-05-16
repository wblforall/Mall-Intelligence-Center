<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table         = 'password_resets';
    protected $allowedFields = ['email', 'token', 'expires_at', 'created_at'];
    protected $useTimestamps = false;

    public function createToken(string $email): string
    {
        $this->where('email', $email)->delete();

        $token = bin2hex(random_bytes(32));
        $this->insert([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    public function findValid(string $token): ?array
    {
        return $this->where('token', $token)
                    ->where('expires_at >=', date('Y-m-d H:i:s'))
                    ->first();
    }

    public function consume(string $token): void
    {
        $this->where('token', $token)->delete();
    }
}
