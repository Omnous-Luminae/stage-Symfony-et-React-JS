SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Mise à jour du calendrier des incidents avec l'emoji
UPDATE calendars 
SET name = CONCAT(CHAR(0xF0, 0x9F, 0x9A, 0xA8 USING utf8mb4), ' Calendrier des Incidents')
WHERE id_calendar = 7;

-- Mise à jour du type d'événement incident avec l'emoji
UPDATE event_types 
SET icon = CHAR(0xF0, 0x9F, 0x9A, 0xA8 USING utf8mb4)
WHERE code = 'incident';

-- Vérification
SELECT id_calendar, name, LENGTH(name), CHAR_LENGTH(name) FROM calendars WHERE id_calendar = 7;
SELECT id_event_type, name, icon, LENGTH(icon) FROM event_types WHERE code = 'incident';
