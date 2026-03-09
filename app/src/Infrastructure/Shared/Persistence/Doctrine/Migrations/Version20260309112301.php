<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309112301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team ADD over15_home INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE team ADD over35_home INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE team ADD over05_ht_home INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE team ADD win_both_halves_home INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE team ADD over25_away INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE team ADD over35_away INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE team ADD over05_ht_away INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE team ADD win_both_halves_away INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team DROP over15_home');
        $this->addSql('ALTER TABLE team DROP over35_home');
        $this->addSql('ALTER TABLE team DROP over05_ht_home');
        $this->addSql('ALTER TABLE team DROP win_both_halves_home');
        $this->addSql('ALTER TABLE team DROP over25_away');
        $this->addSql('ALTER TABLE team DROP over35_away');
        $this->addSql('ALTER TABLE team DROP over05_ht_away');
        $this->addSql('ALTER TABLE team DROP win_both_halves_away');
    }
}
