<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250913215535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_type VARCHAR(2) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            document VARCHAR(14) NOT NULL,
            email VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            balance REAL NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2A457AACD8698A76 ON user_accounts (document)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user_accounts');
    }
}
