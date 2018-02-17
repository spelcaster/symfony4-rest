<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180217205158 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE battle_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(64) NOT NULL, UNIQUE INDEX UNIQ_DAF415DFF85E0677 (username), UNIQUE INDEX UNIQ_DAF415DFE7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE battle_project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, difficulty_level INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE battle_battle (id INT AUTO_INCREMENT NOT NULL, programmer_id INT NOT NULL, project_id INT NOT NULL, did_programmer_win TINYINT(1) NOT NULL, fought_at DATETIME NOT NULL, notes LONGTEXT NOT NULL, INDEX IDX_36EFFEC5181DAE45 (programmer_id), INDEX IDX_36EFFEC5166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE battle_programmer (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, nickname VARCHAR(100) NOT NULL, avatarNumber INT NOT NULL, tagLine VARCHAR(255) DEFAULT NULL, powerLevel INT NOT NULL, UNIQUE INDEX UNIQ_EBBE5C73A188FE64 (nickname), INDEX IDX_EBBE5C73A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE battle_api_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(100) NOT NULL, notes LONGTEXT NOT NULL, INDEX IDX_F97E7085A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE battle_battle ADD CONSTRAINT FK_36EFFEC5181DAE45 FOREIGN KEY (programmer_id) REFERENCES battle_programmer (id)');
        $this->addSql('ALTER TABLE battle_battle ADD CONSTRAINT FK_36EFFEC5166D1F9C FOREIGN KEY (project_id) REFERENCES battle_project (id)');
        $this->addSql('ALTER TABLE battle_programmer ADD CONSTRAINT FK_EBBE5C73A76ED395 FOREIGN KEY (user_id) REFERENCES battle_user (id)');
        $this->addSql('ALTER TABLE battle_api_token ADD CONSTRAINT FK_F97E7085A76ED395 FOREIGN KEY (user_id) REFERENCES battle_user (id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE battle_programmer DROP FOREIGN KEY FK_EBBE5C73A76ED395');
        $this->addSql('ALTER TABLE battle_api_token DROP FOREIGN KEY FK_F97E7085A76ED395');
        $this->addSql('ALTER TABLE battle_battle DROP FOREIGN KEY FK_36EFFEC5166D1F9C');
        $this->addSql('ALTER TABLE battle_battle DROP FOREIGN KEY FK_36EFFEC5181DAE45');
        $this->addSql('DROP TABLE battle_user');
        $this->addSql('DROP TABLE battle_project');
        $this->addSql('DROP TABLE battle_battle');
        $this->addSql('DROP TABLE battle_programmer');
        $this->addSql('DROP TABLE battle_api_token');
    }
}
