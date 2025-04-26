<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250423171020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE user_trajet (user_id INT NOT NULL, trajet_id INT NOT NULL, INDEX IDX_4E09B2B1A76ED395 (user_id), INDEX IDX_4E09B2B1D12A823 (trajet_id), PRIMARY KEY(user_id, trajet_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_trajet ADD CONSTRAINT FK_4E09B2B1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_trajet ADD CONSTRAINT FK_4E09B2B1D12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trajet_user DROP FOREIGN KEY FK_825A9176D12A823
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trajet_user DROP FOREIGN KEY FK_825A9176A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE trajet_user
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE trajet_user (trajet_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_825A9176D12A823 (trajet_id), INDEX IDX_825A9176A76ED395 (user_id), PRIMARY KEY(trajet_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trajet_user ADD CONSTRAINT FK_825A9176D12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trajet_user ADD CONSTRAINT FK_825A9176A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_trajet DROP FOREIGN KEY FK_4E09B2B1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_trajet DROP FOREIGN KEY FK_4E09B2B1D12A823
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_trajet
        SQL);
    }
}
