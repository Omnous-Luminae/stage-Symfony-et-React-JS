-- Migration: Ajouter la colonne user_id à audit_logs pour tracer tous les utilisateurs
-- Permet de logger les actions de TOUS les utilisateurs, pas seulement les admins

-- Modifier la colonne admin_id pour permettre NULL (si pas déjà fait)
ALTER TABLE audit_logs MODIFY COLUMN admin_id INT(11) NULL;

-- Ajouter la colonne user_id si elle n'existe pas
ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS user_id INT(11) NULL;

-- Ajouter la contrainte de clé étrangère (si elle n'existe pas déjà)
-- Note: On ignore les erreurs si la contrainte existe déjà
ALTER TABLE audit_logs 
ADD CONSTRAINT fk_audit_logs_user 
FOREIGN KEY (user_id) REFERENCES users(id_user) 
ON DELETE SET NULL;

-- Mettre à jour les anciens logs pour associer user_id à partir de admin_id
-- (Les admins ont un utilisateur associé)
UPDATE audit_logs al
JOIN administrators a ON al.admin_id = a.id_admin
SET al.user_id = a.user_id
WHERE al.user_id IS NULL AND al.admin_id IS NOT NULL;
