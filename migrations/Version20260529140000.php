<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store appointment realtime version in MySQL (works on Railway)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE realtime_state (state_key VARCHAR(64) NOT NULL, state_value VARCHAR(255) NOT NULL, PRIMARY KEY(state_key)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO realtime_state (state_key, state_value) VALUES ('appointments_version', '1')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE realtime_state');
    }
}
