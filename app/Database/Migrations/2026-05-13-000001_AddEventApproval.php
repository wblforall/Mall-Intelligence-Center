<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventApproval extends Migration
{
    public function up()
    {
        // Approval fields on events
        $this->db->query("
            ALTER TABLE events
              ADD COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER event_days,
              ADD COLUMN approved_by     INT UNSIGNED NULL AFTER approval_status,
              ADD COLUMN approved_at     DATETIME     NULL AFTER approved_by,
              ADD COLUMN rejection_reason TEXT         NULL AFTER approved_at
        ");

        // Permission on roles
        $this->db->query("
            ALTER TABLE roles
              ADD COLUMN can_approve_events TINYINT(1) NOT NULL DEFAULT 0 AFTER can_view_logs
        ");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE events DROP COLUMN approval_status, DROP COLUMN approved_by, DROP COLUMN approved_at, DROP COLUMN rejection_reason");
        $this->db->query("ALTER TABLE roles DROP COLUMN can_approve_events");
    }
}
