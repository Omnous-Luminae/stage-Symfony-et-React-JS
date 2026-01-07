<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107083635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__events AS SELECT id, calendar_id, created_by_id, title, description, start_date, end_date, location, type, color, is_recurrent, recurrence_pattern, created_at, updated_at FROM events');
        $this->addSql('DROP TABLE events');
        $this->addSql('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, calendar_id INTEGER DEFAULT NULL, created_by_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, location VARCHAR(255) DEFAULT NULL, type VARCHAR(50) NOT NULL, color VARCHAR(7) DEFAULT NULL, is_recurrent BOOLEAN NOT NULL, recurrence_pattern CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5387574AA40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendars (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5387574AB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO events (id, calendar_id, created_by_id, title, description, start_date, end_date, location, type, color, is_recurrent, recurrence_pattern, created_at, updated_at) SELECT id, calendar_id, created_by_id, title, description, start_date, end_date, location, type, color, is_recurrent, recurrence_pattern, created_at, updated_at FROM __temp__events');
        $this->addSql('DROP TABLE __temp__events');
        $this->addSql('CREATE INDEX IDX_5387574AB03A8386 ON events (created_by_id)');
        $this->addSql('CREATE INDEX IDX_5387574AA40A2C8 ON events (calendar_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__events AS SELECT id, calendar_id, created_by_id, title, description, start_date, end_date, location, type, color, is_recurrent, recurrence_pattern, created_at, updated_at FROM events');
        $this->addSql('DROP TABLE events');
        $this->addSql('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, calendar_id INTEGER NOT NULL, created_by_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, location VARCHAR(255) DEFAULT NULL, type VARCHAR(50) NOT NULL, color VARCHAR(7) DEFAULT NULL, is_recurrent BOOLEAN NOT NULL, recurrence_pattern CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5387574AA40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendars (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5387574AB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO events (id, calendar_id, created_by_id, title, description, start_date, end_date, location, type, color, is_recurrent, recurrence_pattern, created_at, updated_at) SELECT id, calendar_id, created_by_id, title, description, start_date, end_date, location, type, color, is_recurrent, recurrence_pattern, created_at, updated_at FROM __temp__events');
        $this->addSql('DROP TABLE __temp__events');
        $this->addSql('CREATE INDEX IDX_5387574AA40A2C8 ON events (calendar_id)');
        $this->addSql('CREATE INDEX IDX_5387574AB03A8386 ON events (created_by_id)');
    }
}
