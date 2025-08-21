<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250821161333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE support_comment ADD author_id INT DEFAULT NULL, DROP author_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_comment ADD CONSTRAINT FK_29415A90F675F31B FOREIGN KEY (author_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_29415A90F675F31B ON support_comment (author_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE support_comment DROP FOREIGN KEY FK_29415A90F675F31B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_29415A90F675F31B ON support_comment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_comment ADD author_name VARCHAR(50) DEFAULT NULL, DROP author_id
        SQL);
    }
}
