<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPushTokenToApiTokens extends Migration
{
    public function up()
    {
        $this->forge->addColumn('api_tokens', [
            'push_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
                'default'    => null,
                'after'      => 'token',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('api_tokens', 'push_token');
    }
}
