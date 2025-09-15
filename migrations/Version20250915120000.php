<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250915120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type column to transactions table (SQLite compatible)';
    }

    public function up(Schema $schema): void
    {
        // SQLite supports adding a column with default value
        $this->addSql("ALTER TABLE transactions ADD COLUMN type VARCHAR(255) NOT NULL DEFAULT 'deposit'");
    }

    public function down(Schema $schema): void
    {
        // SQLite cannot drop columns easily; recreate table if needed. For simplicity, skip.
        // No-op
    }
}


