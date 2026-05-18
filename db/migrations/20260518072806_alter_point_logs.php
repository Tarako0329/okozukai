<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AlterPointLogs extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE `point_logs` ADD `shounin_date` DATETIME NULL COMMENT '承認日' AFTER `created_at`");
        $this->execute("UPDATE point_logs SET shounin_date=now();");
    }

    public function down(): void
    {
        // ここに元に戻すSQLを書く
    }
}