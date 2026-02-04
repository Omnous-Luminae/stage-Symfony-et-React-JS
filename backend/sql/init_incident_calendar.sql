-- Script pour créer le calendrier des incidents et le type d'événement associé
-- À exécuter une seule fois pour initialiser le système

-- Créer le calendrier des incidents s'il n'existe pas
INSERT INTO calendars (name, description, color, type, owner_id, created_at, updated_at)
SELECT 
    '🚨 Calendrier des Incidents',
    'Calendrier des incidents signalés. Visible par le personnel et les professeurs.',
    '#dc2626',
    'public',
    NULL,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM calendars WHERE name = '🚨 Calendrier des Incidents'
);

-- Créer le type d'événement "Incident" s'il n'existe pas
INSERT INTO event_types (name, code, description, color, icon, is_active, display_order, created_at, updated_at)
SELECT 
    'Incident',
    'incident',
    'Événement automatiquement créé pour un incident signalé',
    '#dc2626',
    '🚨',
    1,
    100,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM event_types WHERE code = 'incident'
);

-- Afficher les résultats
SELECT 'Calendrier des incidents:' as info, id_calendar, name, type FROM calendars WHERE name = '🚨 Calendrier des Incidents';
SELECT 'Type événement incident:' as info, id_event_type, name, code FROM event_types WHERE code = 'incident';
