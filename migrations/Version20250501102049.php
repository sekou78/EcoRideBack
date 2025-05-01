<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501102049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP INDEX UNIQ_42C84955A76ED395, ADD INDEX IDX_42C84955A76ED395 (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation CHANGE user_id user_id INT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP INDEX IDX_42C84955A76ED395, ADD UNIQUE INDEX UNIQ_42C84955A76ED395 (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation CHANGE user_id user_id INT DEFAULT NULL
        SQL);
    }
}
