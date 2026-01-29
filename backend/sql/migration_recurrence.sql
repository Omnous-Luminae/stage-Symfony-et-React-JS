-- Migration pour ajouter le support complet de la récurrence aux événements
-- À exécuter sur la base de données existante via phpMyAdmin

-- Étape 1: Modifier la colonne recurrence_type pour accepter plus de valeurs
ALTER TABLE events 
MODIFY COLUMN recurrence_type VARCHAR(50) NULL 
COMMENT 'daily, weekly, biweekly, monthly, yearly';

-- Étape 2: Ajouter la colonne recurrence_interval (répéter tous les X)
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS recurrence_interval INT NULL DEFAULT 1 
COMMENT 'Répéter tous les X jours/semaines/mois'
AFTER recurrence_type;

-- Étape 3: Renommer recurrence_pattern en recurrence_days si elle existe
-- D'abord vérifier et supprimer si recurrence_days existe déjà
SET @column_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'events' 
    AND COLUMN_NAME = 'recurrence_days'
);

-- Si recurrence_pattern existe et pas recurrence_days, renommer
-- Sinon ajouter recurrence_days
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS recurrence_days JSON NULL 
COMMENT 'Jours de la semaine pour récurrence hebdo: ["mon","tue","wed"]'
AFTER recurrence_interval;

-- Supprimer l'ancienne colonne recurrence_pattern si elle existe
-- (Commenté par sécurité - décommentez si nécessaire)
-- ALTER TABLE events DROP COLUMN IF EXISTS recurrence_pattern;

-- Étape 4: Ajouter la colonne parent_event_id pour lier les occurrences
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS parent_event_id INT NULL 
COMMENT 'ID de l événement parent pour les occurrences générées'
AFTER recurrence_end_date;

-- Étape 5: Ajouter la contrainte de clé étrangère pour parent_event_id
-- D'abord supprimer si elle existe
SET @fk_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'events' 
    AND CONSTRAINT_NAME = 'FK_event_parent'
);

-- Ajouter la contrainte
ALTER TABLE events 
ADD CONSTRAINT FK_event_parent 
FOREIGN KEY (parent_event_id) REFERENCES events(id_events) ON DELETE CASCADE;

-- Étape 6: Créer un index sur parent_event_id pour les performances
CREATE INDEX IF NOT EXISTS idx_event_parent ON events(parent_event_id);

-- Vérification: Afficher la structure mise à jour
DESCRIBE events;
