-- Migration: Création de la table user_roles pour gérer les rôles utilisateur
-- Permet aux admins de gérer dynamiquement les rôles disponibles

CREATE TABLE IF NOT EXISTS user_roles (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#6366f1',
    icon VARCHAR(50) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    can_create_events BOOLEAN DEFAULT TRUE,
    can_create_public_events BOOLEAN DEFAULT FALSE,
    can_share_calendars BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_active (is_active),
    INDEX idx_role_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les rôles par défaut (basés sur les constantes existantes)
-- Note: is_system = FALSE pour permettre aux admins de les modifier
INSERT INTO user_roles (name, code, description, color, icon, is_system, display_order, can_create_events, can_create_public_events, can_share_calendars) VALUES
('Élève', 'ELEVE', 'Étudiants et apprenants', '#3b82f6', '🎓', FALSE, 1, TRUE, FALSE, FALSE),
('Professeur', 'PROFESSEUR', 'Enseignants et formateurs', '#10b981', '👨‍🏫', FALSE, 2, TRUE, TRUE, TRUE),
('Personnel', 'PERSONNEL', 'Personnel administratif et technique', '#f59e0b', '👤', FALSE, 3, TRUE, FALSE, TRUE),
('Intervenant', 'INTERVENANT', 'Intervenants externes', '#8b5cf6', '🎤', FALSE, 4, TRUE, FALSE, TRUE)
ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    color = VALUES(color),
    icon = VALUES(icon);
