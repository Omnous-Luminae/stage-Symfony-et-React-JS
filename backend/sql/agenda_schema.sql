-- Schéma MySQL pour import phpMyAdmin
-- Base suggérée : agenda (MySQL 8.0, utf8mb4)
-- Crée les tables principales utilisées par l'app (events + relations basiques)

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(180) NOT NULL UNIQUE,
  roles JSON NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  color VARCHAR(7) DEFAULT '#3788d8',
  created_by_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT FK_calendar_user FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description LONGTEXT NULL,
  start_date DATETIME NOT NULL,
  end_date DATETIME NOT NULL,
  location VARCHAR(255) NULL,
  type VARCHAR(50) NOT NULL,
  color VARCHAR(7) NULL,
  is_recurrent TINYINT(1) NOT NULL DEFAULT 0,
  recurrence_pattern JSON NULL,
  calendar_id INT NULL,
  created_by_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT FK_event_calendar FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
  CONSTRAINT FK_event_user FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  calendar_id INT NOT NULL,
  user_id INT NOT NULL,
  permission VARCHAR(20) NOT NULL, -- ex: view, edit, admin
  CONSTRAINT FK_perm_calendar FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
  CONSTRAINT FK_perm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_calendar_user (calendar_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index utiles
CREATE INDEX idx_event_start_date ON events(start_date);
CREATE INDEX idx_event_end_date ON events(end_date);
CREATE INDEX idx_event_calendar ON events(calendar_id);
CREATE INDEX idx_event_created_by ON events(created_by_id);
