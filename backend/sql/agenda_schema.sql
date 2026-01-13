-- Schéma MySQL pour import phpMyAdmin
-- Base suggérée : agenda (MySQL 8.0, utf8mb4)
-- Crée les tables principales utilisées par l'app (events + relations basiques)

CREATE TABLE IF NOT EXISTS users (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  role ENUM('Élève', 'Professeur', 'Personnel', 'Intervenant') NOT NULL DEFAULT 'Intervenant',
  roles JSON NOT NULL,
  password VARCHAR(255) NOT NULL,
  status ENUM('Actif', 'Inactif') NOT NULL DEFAULT 'Actif',
  last_password_change DATETIME NULL,
  remember_me TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendars (
  id_calendar INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  color VARCHAR(7) DEFAULT '#3788d8',
  created_by_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT FK_calendar_user FOREIGN KEY (created_by_id) REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id_events INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description LONGTEXT NULL,
  start_date DATETIME NOT NULL,
  end_date DATETIME NOT NULL,
  location VARCHAR(255) NULL,
  type ENUM('Cours', 'Réunion', 'Examen', 'Administratif', 'Formation', 'Autre') NOT NULL DEFAULT 'Autre',
  color VARCHAR(7) NULL,
  is_recurrent TINYINT(1) NOT NULL DEFAULT 0,
  recurrence_type ENUM('Quotidien', 'Hebdomadaire', 'Mensuel') NULL,
  recurrence_pattern JSON NULL,
  recurrence_end_date DATE NULL,
  calendar_id INT NULL,
  created_by_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT FK_event_calendar FOREIGN KEY (calendar_id) REFERENCES calendars(id_calendar) ON DELETE CASCADE,
  CONSTRAINT FK_event_user FOREIGN KEY (created_by_id) REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_permissions (
  id_c_perm INT AUTO_INCREMENT PRIMARY KEY,
  calendar_id INT NOT NULL,
  user_id INT NULL,
  role_name VARCHAR(50) NULL,
  permission ENUM('Consultation', 'Modification', 'Administration') NOT NULL DEFAULT 'Consultation',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT FK_perm_calendar FOREIGN KEY (calendar_id) REFERENCES calendars(id_calendar) ON DELETE CASCADE,
  CONSTRAINT FK_perm_user FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE,
  UNIQUE KEY uniq_calendar_user (calendar_id, user_id),
  UNIQUE KEY uniq_calendar_role (calendar_id, role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index utiles
CREATE INDEX idx_event_start_date ON events(start_date);
CREATE INDEX idx_event_end_date ON events(end_date);
CREATE INDEX idx_event_calendar ON events(calendar_id);
CREATE INDEX idx_event_created_by ON events(created_by_id);
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_status ON users(status);
CREATE INDEX idx_calendar_created_by ON calendars(created_by_id);

-- Table pour les notifications/alertes
CREATE TABLE IF NOT EXISTS notifications (
  id_notif INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NULL,
  title VARCHAR(255) NOT NULL,
  message LONGTEXT NOT NULL,
  type ENUM('Nouvel_événement', 'Modification_événement', 'Partage_agenda', 'Rappel') NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT FK_notif_user FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE,
  CONSTRAINT FK_notif_event FOREIGN KEY (event_id) REFERENCES events(id_events) ON DELETE CASCADE,
  INDEX idx_user_notifications (user_id, is_read),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les préférences utilisateur
CREATE TABLE IF NOT EXISTS user_preferences (
  id_u_pref INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  email_notifications TINYINT(1) NOT NULL DEFAULT 1,
  event_notifications TINYINT(1) NOT NULL DEFAULT 1,
  reminder_time INT NOT NULL DEFAULT 15,
  default_view ENUM('Jour', 'Semaine', 'Mois', 'Liste') NOT NULL DEFAULT 'Mois',
  week_start ENUM('Lundi', 'Dimanche') NOT NULL DEFAULT 'Lundi',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT FK_prefs_user FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table séparée pour les administrateurs (Sécurité renforcée)
CREATE TABLE IF NOT EXISTS administrators (
  id_admin INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  permission_level ENUM('Super_Admin', 'Admin') NOT NULL DEFAULT 'Admin',
  can_manage_users TINYINT(1) NOT NULL DEFAULT 1,
  can_manage_calendars TINYINT(1) NOT NULL DEFAULT 1,
  can_manage_permissions TINYINT(1) NOT NULL DEFAULT 1,
  can_view_audit_logs TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  failed_login_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT FK_admin_user FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE,
  INDEX idx_permission_level (permission_level),
  INDEX idx_last_login (last_login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table d'audit pour les actions administrateur
CREATE TABLE IF NOT EXISTS audit_logs (
  id_log INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NULL,
  old_value JSON NULL,
  new_value JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT FK_audit_admin FOREIGN KEY (admin_id) REFERENCES administrators(id_admin) ON DELETE CASCADE,
  INDEX idx_admin_action (admin_id, action),
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les classes d'élèves
CREATE TABLE IF NOT EXISTS classes (
  id_classe INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  level VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pivot pour lier les élèves aux classes
CREATE TABLE IF NOT EXISTS student_class (
  id_s_classe INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  class_id INT NOT NULL,
  enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT FK_student_user FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE,
  CONSTRAINT FK_student_class FOREIGN KEY (class_id) REFERENCES classes(id_classe) ON DELETE CASCADE,
  UNIQUE KEY uniq_student_class (user_id, class_id),
  INDEX idx_class_id (class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour lier les professeurs aux classes
CREATE TABLE IF NOT EXISTS teacher_class (
  id_t_classe INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  class_id INT NOT NULL,
  subject VARCHAR(100) NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT FK_teacher_user FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE,
  CONSTRAINT FK_teacher_class FOREIGN KEY (class_id) REFERENCES classes(id_classe) ON DELETE CASCADE,
  UNIQUE KEY uniq_teacher_class (user_id, class_id),
  INDEX idx_class_id (class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
