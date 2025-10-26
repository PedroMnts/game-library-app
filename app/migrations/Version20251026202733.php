<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251026202733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE games_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE libraries_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE library_games_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE games (id INT NOT NULL, title VARCHAR(255) NOT NULL, platform VARCHAR(100) DEFAULT NULL, release_year INT DEFAULT NULL, genre VARCHAR(100) DEFAULT NULL, developer VARCHAR(150) DEFAULT NULL, cover_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_game_title ON games (title)');
        $this->addSql('COMMENT ON COLUMN games.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN games.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE libraries (id INT NOT NULL, owner_id INT NOT NULL, name VARCHAR(120) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3ADD55A97E3C61F9 ON libraries (owner_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_library_owner_name ON libraries (owner_id, name)');
        $this->addSql('COMMENT ON COLUMN libraries.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN libraries.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE library_games (id INT NOT NULL, library_id INT NOT NULL, game_id INT NOT NULL, status VARCHAR(255) NOT NULL, rating SMALLINT DEFAULT NULL, progress_percent SMALLINT DEFAULT NULL, hours_played NUMERIC(6, 1) DEFAULT NULL, added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CC86F91EFE2541D7 ON library_games (library_id)');
        $this->addSql('CREATE INDEX IDX_CC86F91EE48FD905 ON library_games (game_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_library_game ON library_games (library_id, game_id)');
        $this->addSql('COMMENT ON COLUMN library_games.added_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE libraries ADD CONSTRAINT FK_3ADD55A97E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE library_games ADD CONSTRAINT FK_CC86F91EFE2541D7 FOREIGN KEY (library_id) REFERENCES libraries (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE library_games ADD CONSTRAINT FK_CC86F91EE48FD905 FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE games_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE libraries_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE library_games_id_seq CASCADE');
        $this->addSql('ALTER TABLE libraries DROP CONSTRAINT FK_3ADD55A97E3C61F9');
        $this->addSql('ALTER TABLE library_games DROP CONSTRAINT FK_CC86F91EFE2541D7');
        $this->addSql('ALTER TABLE library_games DROP CONSTRAINT FK_CC86F91EE48FD905');
        $this->addSql('DROP TABLE games');
        $this->addSql('DROP TABLE libraries');
        $this->addSql('DROP TABLE library_games');
    }
}
