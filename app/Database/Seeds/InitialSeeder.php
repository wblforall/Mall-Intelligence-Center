<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        $this->db->table('users')->insert([
            'name'       => 'Administrator',
            'email'      => 'admin@mall-intelligence-center.com',
            'password'   => password_hash('admin123', PASSWORD_BCRYPT),
            'role'       => 'admin',
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
