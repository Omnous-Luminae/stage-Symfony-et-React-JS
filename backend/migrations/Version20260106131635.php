<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106131635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE calendar_permissions (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, calendar_id INTEGER NOT NULL, user_id INTEGER NOT NULL, permission VARCHAR(50) NOT NULL, granted_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5D117224A40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendars (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5D117224A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5D117224A40A2C8 ON calendar_permissions (calendar_id)');
        $this->addSql('CREATE INDEX IDX_5D117224A76ED395 ON calendar_permissions (user_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_calendar_user ON calendar_permissions (calendar_id, user_id)');
        $this->addSql('CREATE TABLE calendars (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, owner_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, type VARCHAR(50) NOT NULL, color VARCHAR(7) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_84DF820F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_84DF820F7E3C61F9 ON calendars (owner_id)');
        $this->addSql('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, calendar_id INTEGER NOT NULL, created_by_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, location VARCHAR(255) DEFAULT NULL, type VARCHAR(50) NOT NULL, color VARCHAR(7) DEFAULT NULL, is_recurrent BOOLEAN NOT NULL, recurrence_pattern CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5387574AA40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendars (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5387574AB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5387574AA40A2C8 ON events (calendar_id)');
        $this->addSql('CREATE INDEX IDX_5387574AB03A8386 ON events (created_by_id)');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE calendar_permissions');
        $this->addSql('DROP TABLE calendars');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
