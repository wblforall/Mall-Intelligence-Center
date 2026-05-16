<?php

namespace App\Models;

use CodeIgniter\Model;

class AppSettingsModel extends Model
{
    protected $table         = 'app_settings';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['key', 'value', 'label'];
    protected $useTimestamps = true;

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->where('key', $key)->first();
        return $row ? $row['value'] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $exists = $this->where('key', $key)->first();
        if ($exists) {
            $this->where('key', $key)->set('value', $value)->update();
        } else {
            $this->insert(['key' => $key, 'value' => $value]);
        }
    }

    public function getEmails(string $key): array
    {
        $raw = $this->get($key, '[]');
        return json_decode($raw, true) ?? [];
    }
}
