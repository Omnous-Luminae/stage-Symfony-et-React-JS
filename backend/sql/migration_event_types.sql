-- Migration pour créer la table des types d'événements
-- À exécuter sur la base de données existante via phpMyAdmin

-- =====================================================
-- ÉTAPE 1: Créer la table event_types
-- =====================================================
CREATE TABLE IF NOT EXISTS event_types (
    id_event_type INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nom du type (ex: Cours, Réunion)',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code technique (ex: course, meeting)',
    description VARCHAR(255) NULL COMMENT 'Description du type',
    color VARCHAR(7) NOT NULL DEFAULT '#3788d8' COMMENT 'Couleur par défaut pour ce type',
    icon VARCHAR(10) NULL COMMENT 'Emoji ou icône (ex: 📚)',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Type actif ou désactivé',
    display_order INT NOT NULL DEFAULT 0 COMMENT 'Ordre d affichage',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÉTAPE 2: Insérer les types par défaut
-- =====================================================
INSERT INTO event_types (name, code, description, color, icon, display_order) VALUES
    ('Cours', 'course', 'Cours et enseignements', '#3788d8', '📚', 1),
    ('Réunion', 'meeting', 'Réunions et rencontres', '#4caf50', '👥', 2),
    ('Examen', 'exam', 'Examens et évaluations', '#f44336', '📝', 3),
    ('Formation', 'training', 'Formations et ateliers', '#ff9800', '🎓', 4),
    ('Administratif', 'administrative', 'Tâches administratives', '#9c27b0', '📋', 5),
    ('Autre', 'other', 'Autres événements', '#607d8b', '📌', 6);

-- =====================================================
-- ÉTAPE 3: Ajouter la colonne event_type_id à events
-- =====================================================
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS event_type_id INT NULL AFTER type;

-- =====================================================
-- ÉTAPE 4: Créer la clé étrangère
-- =====================================================
ALTER TABLE events
ADD CONSTRAINT FK_event_type 
FOREIGN KEY (event_type_id) REFERENCES event_types(id_event_type) 
ON DELETE SET NULL;

-- =====================================================
-- ÉTAPE 5: Migrer les données existantes
-- =====================================================
-- Mettre à jour les événements existants avec le bon type_id
UPDATE events e
INNER JOIN event_types et ON (
    (e.type = 'Cours' AND et.code = 'course') OR
    (e.type = 'Réunion' AND et.code = 'meeting') OR
    (e.type = 'Examen' AND et.code = 'exam') OR
    (e.type = 'Formation' AND et.code = 'training') OR
    (e.type = 'Administratif' AND et.code = 'administrative') OR
    (e.type = 'Autre' AND et.code = 'other')
)
SET e.event_type_id = et.id_event_type
WHERE e.event_type_id IS NULL;

-- Pour les événements sans type correspondant, les mettre en "Autre"
UPDATE events e
SET e.event_type_id = (SELECT id_event_type FROM event_types WHERE code = 'other')
WHERE e.event_type_id IS NULL;

-- =====================================================
-- ÉTAPE 6 (OPTIONNEL): Supprimer l'ancienne colonne type
-- =====================================================
-- ATTENTION: Ne faire cette étape qu'après avoir vérifié que tout fonctionne!
-- ALTER TABLE events DROP COLUMN type;

-- =====================================================
-- INDEX pour améliorer les performances
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_events_type_id ON events(event_type_id);
CREATE INDEX IF NOT EXISTS idx_event_types_code ON event_types(code);
CREATE INDEX IF NOT EXISTS idx_event_types_active ON event_types(is_active);
