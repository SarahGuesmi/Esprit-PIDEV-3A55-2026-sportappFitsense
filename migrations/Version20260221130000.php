<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add passkey_credential table for Face ID / WebAuthn login';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE passkey_credential (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, credential_id VARCHAR(512) NOT NULL, credential_public_key LONGTEXT NOT NULL, signature_counter INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_PASSKEY_USER (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE passkey_credential ADD CONSTRAINT FK_PASSKEY_USER_ID FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE passkey_credential DROP FOREIGN KEY FK_PASSKEY_USER_ID');
        $this->addSql('DROP TABLE passkey_credential');
    }
}
