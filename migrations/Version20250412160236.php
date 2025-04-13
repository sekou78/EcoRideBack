<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250412160236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE profil_conducteur (id INT AUTO_INCREMENT NOT NULL, plaque_immatriculation VARCHAR(20) NOT NULL, modele VARCHAR(50) NOT NULL, marque VARCHAR(50) NOT NULL, couleur VARCHAR(20) NOT NULL, nombre_places INT NOT NULL, accepte_fumeur TINYINT(1) NOT NULL, accepte_animaux TINYINT(1) NOT NULL, autres_preferences LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE profil_conducteur
        SQL);
    }
}
