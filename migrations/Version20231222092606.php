<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231222092606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_link (user_id INT NOT NULL, link_id INT NOT NULL, INDEX IDX_4C2DD538A76ED395 (user_id), INDEX IDX_4C2DD538ADA40271 (link_id), PRIMARY KEY(user_id, link_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_link ADD CONSTRAINT FK_4C2DD538A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_link ADD CONSTRAINT FK_4C2DD538ADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_link DROP FOREIGN KEY FK_4C2DD538A76ED395');
        $this->addSql('ALTER TABLE user_link DROP FOREIGN KEY FK_4C2DD538ADA40271');
        $this->addSql('DROP TABLE user_link');
    }
}
