<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250920090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create refresh_token table for refresh tokens';
    }

    public function up(Schema $schema): void
    {
        // MySQL specific DDL (adjust if using another platform)
        $this->addSql('CREATE TABLE refresh_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            revoked_at DATETIME DEFAULT NULL,
            replaced_by VARCHAR(64) DEFAULT NULL,
            last_used_at DATETIME DEFAULT NULL,
            INDEX IDX_REFRESH_TOKEN_USER (user_id),
            INDEX IDX_REFRESH_TOKEN_EXPIRES (expires_at),
            UNIQUE INDEX UNIQ_REFRESH_TOKEN_HASH (token_hash),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_REFRESH_TOKEN_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_token');
    }
}

