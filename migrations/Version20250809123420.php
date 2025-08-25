<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250809123420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE support_comment (id INT AUTO_INCREMENT NOT NULL, support_message_id INT DEFAULT NULL, content LONGTEXT DEFAULT NULL, author_name VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_29415A9071CED70B (support_message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_comment ADD CONSTRAINT FK_29415A9071CED70B FOREIGN KEY (support_message_id) REFERENCES support_message (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_message DROP internal_comment
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE support_comment DROP FOREIGN KEY FK_29415A9071CED70B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE support_comment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_message ADD internal_comment LONGTEXT DEFAULT NULL
        SQL);
    }
}
