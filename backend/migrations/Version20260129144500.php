<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Change onDelete from CASCADE to SET NULL for parent_event_id foreign key
 * This allows deleting a parent recurring event without deleting all children
 * 
 * NOTE: This migration was already applied manually. It's kept for reference.
 */
final class Version20260129144500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change parent_event_id foreign key from CASCADE to SET NULL on delete';
    }

    public function up(Schema $schema): void
    {
        // First, drop the existing foreign key constraint
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY IF EXISTS FK_event_parent');
        
        // Add the new foreign key with SET NULL on delete
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_event_parent FOREIGN KEY (parent_event_id) REFERENCES events(id_events) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert to CASCADE on delete
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY IF EXISTS FK_event_parent');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_event_parent FOREIGN KEY (parent_event_id) REFERENCES events(id_events) ON DELETE CASCADE');
    }
}
