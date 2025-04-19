<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250417130051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE profil_conducteur ADD user_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE profil_conducteur ADD CONSTRAINT FK_18A12ABDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_18A12ABDA76ED395 ON profil_conducteur (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE profil_conducteur DROP FOREIGN KEY FK_18A12ABDA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_18A12ABDA76ED395 ON profil_conducteur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE profil_conducteur DROP user_id
        SQL);
    }
}
