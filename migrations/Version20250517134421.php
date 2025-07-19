<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250517134421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE profil_conducteur DROP accepte_fumeur, DROP accepte_animaux, DROP autres_preferences
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD accepte_fumeur TINYINT(1) NOT NULL, ADD accepte_animaux TINYINT(1) NOT NULL, ADD autres_preferences LONGTEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE profil_conducteur ADD accepte_fumeur TINYINT(1) NOT NULL, ADD accepte_animaux TINYINT(1) NOT NULL, ADD autres_preferences LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP accepte_fumeur, DROP accepte_animaux, DROP autres_preferences
        SQL);
    }
}
