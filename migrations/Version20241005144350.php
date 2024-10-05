<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241005144350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE bed_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE room_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE bed (id INT NOT NULL, room_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E647FCFF54177093 ON bed (room_id)');
        $this->addSql('CREATE TABLE room (id INT NOT NULL, name TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE bed ADD CONSTRAINT FK_E647FCFF54177093 FOREIGN KEY (room_id) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE bed_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE room_id_seq CASCADE');
        $this->addSql('ALTER TABLE bed DROP CONSTRAINT FK_E647FCFF54177093');
        $this->addSql('DROP TABLE bed');
        $this->addSql('DROP TABLE room');
    }
}
