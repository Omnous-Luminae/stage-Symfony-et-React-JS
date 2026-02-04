-- Migration pour le système de signalement d'incidents
-- À exécuter dans phpMyAdmin ou via MySQL CLI

USE agenda_db;

-- ================================================================
-- Table des catégories d'incidents
-- ================================================================
CREATE TABLE IF NOT EXISTS incident_categories (
    id_category INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7) DEFAULT '#6366f1',
    icon VARCHAR(10),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    default_assignee_role VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_active (is_active),
    INDEX idx_category_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table des incidents
-- ================================================================
CREATE TABLE IF NOT EXISTS incidents (
    id_incident INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255),
    category_id INT NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(20) DEFAULT 'open',
    reporter_id INT NOT NULL,
    assignee_id INT,
    assignee_role VARCHAR(50),
    resolution TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    FOREIGN KEY (category_id) REFERENCES incident_categories(id_category),
    FOREIGN KEY (reporter_id) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(id_user) ON DELETE SET NULL,
    INDEX idx_incident_status (status),
    INDEX idx_incident_priority (priority),
    INDEX idx_incident_reporter (reporter_id),
    INDEX idx_incident_assignee (assignee_id),
    INDEX idx_incident_assignee_role (assignee_role),
    INDEX idx_incident_category (category_id),
    INDEX idx_incident_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Table des commentaires d'incidents
-- ================================================================
CREATE TABLE IF NOT EXISTS incident_comments (
    id_comment INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    FOREIGN KEY (incident_id) REFERENCES incidents(id_incident) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id_user) ON DELETE CASCADE,
    INDEX idx_comment_incident (incident_id),
    INDEX idx_comment_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Catégories par défaut
-- ================================================================
INSERT INTO incident_categories (name, code, description, color, icon, display_order, default_assignee_role) VALUES
('Matériel', 'hardware', 'Problèmes de matériel informatique (écrans, claviers, souris, etc.)', '#ef4444', '🖥️', 1, 'Personnel'),
('Logiciel', 'software', 'Problèmes logiciels (bugs, erreurs, installations)', '#f97316', '💾', 2, 'Personnel'),
('Réseau', 'network', 'Problèmes de connexion réseau ou internet', '#eab308', '🌐', 3, 'Personnel'),
('Infrastructure', 'infrastructure', 'Problèmes d''infrastructure (mobilier, éclairage, climatisation)', '#22c55e', '🏢', 4, 'Personnel'),
('Sécurité', 'security', 'Incidents de sécurité (accès non autorisé, vol, etc.)', '#dc2626', '🔒', 5, 'Personnel'),
('Autre', 'other', 'Autres types d''incidents', '#6b7280', '📋', 99, NULL);

-- ================================================================
-- Vérification
-- ================================================================
SELECT 'Tables créées:' as info;
SHOW TABLES LIKE 'incident%';

SELECT 'Catégories par défaut:' as info;
SELECT id_category, name, code, color, icon, default_assignee_role FROM incident_categories;
