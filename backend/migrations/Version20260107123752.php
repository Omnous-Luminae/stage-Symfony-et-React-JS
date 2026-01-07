<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107123752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE calendar_permissions ADD granted_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE permission permission VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE calendar_permissions RENAME INDEX fk_perm_user TO IDX_5D117224A76ED395');
        $this->addSql('ALTER TABLE calendar_permissions RENAME INDEX uniq_calendar_user TO unique_calendar_user');
        $this->addSql('ALTER TABLE calendars DROP FOREIGN KEY FK_calendar_user');
        $this->addSql('DROP INDEX FK_calendar_user ON calendars');
        $this->addSql('ALTER TABLE calendars ADD owner_id INT NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD type VARCHAR(50) NOT NULL, DROP created_by_id, CHANGE color color VARCHAR(7) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE calendars ADD CONSTRAINT FK_84DF820F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_84DF820F7E3C61F9 ON calendars (owner_id)');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_event_user');
        $this->addSql('DROP INDEX idx_event_start_date ON events');
        $this->addSql('DROP INDEX idx_event_end_date ON events');
        $this->addSql('ALTER TABLE events CHANGE location location VARCHAR(255) DEFAULT NULL, CHANGE color color VARCHAR(7) DEFAULT NULL, CHANGE is_recurrent is_recurrent TINYINT(1) NOT NULL, CHANGE recurrence_pattern recurrence_pattern JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE events RENAME INDEX idx_event_calendar TO IDX_5387574AA40A2C8');
        $this->addSql('ALTER TABLE events RENAME INDEX idx_event_created_by TO IDX_5387574AB03A8386');
        $this->addSql('ALTER TABLE users ADD first_name VARCHAR(100) NOT NULL, ADD last_name VARCHAR(100) NOT NULL, CHANGE roles roles JSON NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE users RENAME INDEX email TO UNIQ_1483A5E9E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE calendars DROP FOREIGN KEY FK_84DF820F7E3C61F9');
        $this->addSql('DROP INDEX IDX_84DF820F7E3C61F9 ON calendars');
        $this->addSql('ALTER TABLE calendars ADD created_by_id INT DEFAULT NULL, DROP owner_id, DROP description, DROP type, CHANGE color color VARCHAR(7) DEFAULT \'\'\'#3788d8\'\'\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE calendars ADD CONSTRAINT FK_calendar_user FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX FK_calendar_user ON calendars (created_by_id)');
        $this->addSql('ALTER TABLE calendar_permissions DROP granted_at, CHANGE permission permission VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE calendar_permissions RENAME INDEX idx_5d117224a76ed395 TO FK_perm_user');
        $this->addSql('ALTER TABLE calendar_permissions RENAME INDEX unique_calendar_user TO uniq_calendar_user');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574AB03A8386');
        $this->addSql('ALTER TABLE events CHANGE location location VARCHAR(255) DEFAULT \'NULL\', CHANGE color color VARCHAR(7) DEFAULT \'NULL\', CHANGE is_recurrent is_recurrent TINYINT(1) DEFAULT 0 NOT NULL, CHANGE recurrence_pattern recurrence_pattern LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_event_user FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_event_start_date ON events (start_date)');
        $this->addSql('CREATE INDEX idx_event_end_date ON events (end_date)');
        $this->addSql('ALTER TABLE events RENAME INDEX idx_5387574aa40a2c8 TO idx_event_calendar');
        $this->addSql('ALTER TABLE events RENAME INDEX idx_5387574ab03a8386 TO idx_event_created_by');
        $this->addSql('ALTER TABLE users DROP first_name, DROP last_name, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_1483a5e9e7927c74 TO email');
    }
}
