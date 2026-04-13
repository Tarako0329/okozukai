<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddPointLogs extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `point_logs` ADD `earn_at` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'お手伝い日' AFTER `memo`;");
        $this->execute("UPDATE `point_logs` SET `earn_at` = created_at ;");
    }

    public function down(): void
    {
        // ここに元に戻すSQLを書く
    }
}