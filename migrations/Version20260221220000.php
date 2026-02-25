<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add google_authenticator_secret to user for 2FA TOTP.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user');
        $table->addColumn('google_authenticator_secret', 'string', ['length' => 255, 'notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user');
        $table->dropColumn('google_authenticator_secret');
    }
}
