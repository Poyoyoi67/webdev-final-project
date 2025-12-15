<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211052415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(120) NOT NULL, details LONGTEXT DEFAULT NULL, username VARCHAR(180) DEFAULT NULL, role VARCHAR(50) DEFAULT NULL, target_data LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE appointment_payment (id INT AUTO_INCREMENT NOT NULL, appointment_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, change_amount DOUBLE PRECISION DEFAULT NULL, paid_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FC0BB625E5B533F9 (appointment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE doctor_availability (id INT AUTO_INCREMENT NOT NULL, doctor_id INT NOT NULL, available_date DATE NOT NULL, available TINYINT(1) NOT NULL, INDEX IDX_155FB69F87F4FB17 (doctor_id), UNIQUE INDEX doctor_date_unique (doctor_id, available_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE appointment_payment ADD CONSTRAINT FK_FC0BB625E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('ALTER TABLE doctor_availability ADD CONSTRAINT FK_155FB69F87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment_payment DROP FOREIGN KEY FK_FC0BB625E5B533F9');
        $this->addSql('ALTER TABLE doctor_availability DROP FOREIGN KEY FK_155FB69F87F4FB17');
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('DROP TABLE appointment_payment');
        $this->addSql('DROP TABLE doctor_availability');
    }
}
