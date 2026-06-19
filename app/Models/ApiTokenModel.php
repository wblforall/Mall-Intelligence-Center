<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiTokenModel extends Model
{
    protected $table         = 'api_tokens';
    protected $allowedFields = ['user_id', 'token', 'expires_at', 'created_at'];

    public function generate(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->insert([
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    public function findUser(string $token): ?array
    {
        $row = $this->where('token', $token)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
        if (! $row) return null;

        return (new UserModel())->find($row['user_id']);
    }

    public function revoke(string $token): void
    {
        $this->where('token', $token)->delete();
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }
}
