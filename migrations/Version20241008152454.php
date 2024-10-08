<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241008152454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE bed_reservation_period_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE bed_reservation_period (id INT NOT NULL, bed_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BCCCE4D888688BB9 ON bed_reservation_period (bed_id)');
        $this->addSql('ALTER TABLE bed_reservation_period ADD CONSTRAINT FK_BCCCE4D888688BB9 FOREIGN KEY (bed_id) REFERENCES bed (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE bed_reservation_period_id_seq CASCADE');
        $this->addSql('ALTER TABLE bed_reservation_period DROP CONSTRAINT FK_BCCCE4D888688BB9');
        $this->addSql('DROP TABLE bed_reservation_period');
    }
}
