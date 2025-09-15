<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250914034417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            from_user_id INTEGER NOT NULL,
            to_user_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            created_at DATETIME NOT NULL
        )');
        $this->addSql('CREATE INDEX IDX_EAA81A4C2130303A ON transactions (from_user_id)');
        $this->addSql('CREATE INDEX IDX_EAA81A4C29F6EE60 ON transactions (to_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IF EXISTS IDX_EAA81A4C2130303A');
        $this->addSql('DROP INDEX IF EXISTS IDX_EAA81A4C29F6EE60');
        $this->addSql('DROP TABLE transactions');
    }
}
