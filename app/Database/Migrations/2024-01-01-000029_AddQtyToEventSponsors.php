<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQtyToEventSponsors extends Migration
{
    public function up()
    {
        $this->forge->addColumn('event_sponsors', [
            'qty' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'jenis',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('event_sponsors', 'qty');
    }
}
