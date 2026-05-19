<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407153259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // This migration overlaps older migrations in this project.
        // If the tables already exist, we safely no-op to avoid "table already exists" errors.
        $sm = $this->connection->createSchemaManager();
        if ($sm->tablesExist(['activity_log', 'appointment', 'doctor', 'service', 'user'])) {
            return;
        }

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(120) NOT NULL, details LONGTEXT DEFAULT NULL, username VARCHAR(180) DEFAULT NULL, role VARCHAR(50) DEFAULT NULL, target_data LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, doctor_id INT DEFAULT NULL, service_id INT DEFAULT NULL, patient_name VARCHAR(255) NOT NULL, appointment_date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, notes VARCHAR(255) DEFAULT NULL, INDEX IDX_FE38F84487F4FB17 (doctor_id), INDEX IDX_FE38F844ED5CA9E6 (service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE appointment_payment (id INT AUTO_INCREMENT NOT NULL, appointment_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, change_amount DOUBLE PRECISION DEFAULT NULL, payment_method VARCHAR(50) DEFAULT NULL, paid_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FC0BB625E5B533F9 (appointment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE doctor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, specialization VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, contact_number VARCHAR(20) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE doctor_availability (id INT AUTO_INCREMENT NOT NULL, doctor_id INT NOT NULL, available_date DATE NOT NULL, available TINYINT(1) NOT NULL, INDEX IDX_155FB69F87F4FB17 (doctor_id), UNIQUE INDEX doctor_date_unique (doctor_id, available_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(10, 0) NOT NULL, doctor VARCHAR(255) NOT NULL, text VARCHAR(255) NOT NULL, duration INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, profile_picture VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84487F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE appointment_payment ADD CONSTRAINT FK_FC0BB625E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('ALTER TABLE doctor_availability ADD CONSTRAINT FK_155FB69F87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84487F4FB17');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844ED5CA9E6');
        $this->addSql('ALTER TABLE appointment_payment DROP FOREIGN KEY FK_FC0BB625E5B533F9');
        $this->addSql('ALTER TABLE doctor_availability DROP FOREIGN KEY FK_155FB69F87F4FB17');
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE appointment_payment');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE doctor');
        $this->addSql('DROP TABLE doctor_availability');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
