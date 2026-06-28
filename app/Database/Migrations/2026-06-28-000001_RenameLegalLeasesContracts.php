<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameLegalLeasesContracts extends Migration
{
    public function up()
    {
        $this->db->query('RENAME TABLE legal_leases TO legal_leases_bak');
        $this->db->query('RENAME TABLE legal_contracts TO legal_contracts_bak');
    }

    public function down()
    {
        $this->db->query('RENAME TABLE legal_leases_bak TO legal_leases');
        $this->db->query('RENAME TABLE legal_contracts_bak TO legal_contracts');
    }
}
