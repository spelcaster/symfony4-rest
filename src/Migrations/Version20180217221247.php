<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180217221247 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_DAF415DFF85E0677 ON battle_user');
        $this->addSql('DROP INDEX UNIQ_DAF415DFE7927C74 ON battle_user');
        $this->addSql('ALTER TABLE battle_user ADD username_canonical VARCHAR(180) NOT NULL, ADD email_canonical VARCHAR(180) NOT NULL, ADD enabled TINYINT(1) NOT NULL, ADD salt VARCHAR(255) DEFAULT NULL, ADD last_login DATETIME DEFAULT NULL, ADD confirmation_token VARCHAR(180) DEFAULT NULL, ADD password_requested_at DATETIME DEFAULT NULL, ADD roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE username username VARCHAR(180) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DAF415DF92FC23A8 ON battle_user (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DAF415DFA0D96FBF ON battle_user (email_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DAF415DFC05FB297 ON battle_user (confirmation_token)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_DAF415DF92FC23A8 ON battle_user');
        $this->addSql('DROP INDEX UNIQ_DAF415DFA0D96FBF ON battle_user');
        $this->addSql('DROP INDEX UNIQ_DAF415DFC05FB297 ON battle_user');
        $this->addSql('ALTER TABLE battle_user DROP username_canonical, DROP email_canonical, DROP enabled, DROP salt, DROP last_login, DROP confirmation_token, DROP password_requested_at, DROP roles, CHANGE username username VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE password password VARCHAR(64) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DAF415DFF85E0677 ON battle_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DAF415DFE7927C74 ON battle_user (email)');
    }
}
