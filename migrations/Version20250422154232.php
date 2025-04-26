<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422154232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0F971F91F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8F91ABF0F971F91F ON avis
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD is_visible TINYINT(1) NOT NULL, DROP employes_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employes DROP validation_avis
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD employes_id INT DEFAULT NULL, DROP is_visible
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0F971F91F FOREIGN KEY (employes_id) REFERENCES employes (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8F91ABF0F971F91F ON avis (employes_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employes ADD validation_avis TINYINT(1) NOT NULL
        SQL);
    }
}
