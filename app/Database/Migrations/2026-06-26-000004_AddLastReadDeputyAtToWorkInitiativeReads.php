<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastReadDeputyAtToWorkInitiativeReads extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('work_initiative_reads', [
            'last_read_deputy_at' => [   // GM: last read Deputy reply di gm_deputy thread
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'last_read_gm_at',
            ],
            'last_read_comment_at' => [  // Dept Head: last read komentar Deputy (dept_deputy)
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'last_read_deputy_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('work_initiative_reads', 'last_read_deputy_at');
        $this->forge->dropColumn('work_initiative_reads', 'last_read_comment_at');
    }
}
