<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEeiPublicSurvey extends Migration
{
    public function up()
    {
        // Add survey_token to eei_periods
        $this->db->query('ALTER TABLE eei_periods ADD COLUMN survey_token VARCHAR(64) NULL UNIQUE AFTER is_active');

        // Add submission_key to eei_completions (nullable first, then populate)
        $this->db->query('ALTER TABLE eei_completions ADD COLUMN submission_key VARCHAR(64) NULL AFTER user_id');

        // Migrate existing logged-in completions to use submission_key
        $this->db->query('UPDATE eei_completions SET submission_key = CONCAT("u_", user_id) WHERE user_id IS NOT NULL AND submission_key IS NULL');

        // Make user_id nullable (for anonymous submissions)
        $this->db->query('ALTER TABLE eei_completions MODIFY COLUMN user_id INT UNSIGNED NULL');

        // Make submission_key NOT NULL (all rows now have it populated)
        $this->db->query('ALTER TABLE eei_completions MODIFY COLUMN submission_key VARCHAR(64) NOT NULL DEFAULT ""');

        // Must drop FK before dropping the unique index MySQL uses to back it
        $fks = $this->db->query("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eei_completions'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ")->getResultArray();
        foreach ($fks as $fk) {
            $this->db->query('ALTER TABLE eei_completions DROP FOREIGN KEY `' . $fk['CONSTRAINT_NAME'] . '`');
        }

        // Now drop the old unique index (period_id, user_id)
        $uniques = $this->db->query("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eei_completions'
              AND CONSTRAINT_TYPE = 'UNIQUE'
        ")->getResultArray();
        foreach ($uniques as $u) {
            $this->db->query('ALTER TABLE eei_completions DROP INDEX `' . $u['CONSTRAINT_NAME'] . '`');
        }

        // Add new unique on (period_id, submission_key) + restore FK
        $this->db->query('ALTER TABLE eei_completions ADD UNIQUE KEY uq_period_submission (period_id, submission_key)');
        $this->db->query('ALTER TABLE eei_completions ADD CONSTRAINT fk_eei_comp_period FOREIGN KEY (period_id) REFERENCES eei_periods(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE eei_completions DROP INDEX uq_period_submission');
        $this->db->query('ALTER TABLE eei_completions MODIFY COLUMN user_id INT UNSIGNED NOT NULL');
        $this->db->query('ALTER TABLE eei_completions DROP COLUMN submission_key');
        $this->db->query('ALTER TABLE eei_completions ADD UNIQUE KEY uq_period_user (period_id, user_id)');
        $this->db->query('ALTER TABLE eei_periods DROP COLUMN survey_token');
    }
}
