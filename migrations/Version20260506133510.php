<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506133510 extends AbstractMigration {
    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE film ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOT NULL');
        $this->addSql('ALTER TABLE film ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOT NULL');
        $this->addSql('ALTER TABLE person ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOT NULL');
        $this->addSql('ALTER TABLE person ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOT NULL');
    }

    public function down(Schema $schema): void {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE film DROP created_at');
        $this->addSql('ALTER TABLE film DROP updated_at');
        $this->addSql('ALTER TABLE person DROP created_at');
        $this->addSql('ALTER TABLE person DROP updated_at');
    }
}
