<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add next_fixture_opponent_name column to team table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team ADD next_fixture_opponent_name VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team DROP COLUMN next_fixture_opponent_name');
    }
}
