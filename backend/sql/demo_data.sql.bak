-- Données de démonstration pour l'agenda partagé
-- À exécuter après la création du schéma

-- Insertion d'un utilisateur de démonstration
INSERT INTO users (email, roles, password, first_name, last_name, created_at, updated_at) 
VALUES (
    'demo@lycee.fr',
    '["ROLE_USER"]',
    '$2y$13$dummy.hash.for.demo.user.only.not.for.production.use',
    'Utilisateur',
    'Démo',
    NOW(),
    NOW()
);

SET @demo_user_id = LAST_INSERT_ID();

-- Insertion d'un calendrier de démonstration
INSERT INTO calendars (owner_id, name, description, type, color, created_at, updated_at)
VALUES (
    @demo_user_id,
    'Mon Agenda Personnel',
    'Calendrier de démonstration',
    'personal',
    '#3788d8',
    NOW(),
    NOW()
);

SET @demo_calendar_id = LAST_INSERT_ID();

-- Insertion des événements de démonstration
INSERT INTO events (calendar_id, created_by_id, title, description, start_date, end_date, location, type, color, is_recurrent, recurrence_pattern, created_at, updated_at)
VALUES
    (
        @demo_calendar_id,
        @demo_user_id,
        'Cours de Mathématiques',
        'Cours de mathématiques pour BTS SIO 1ère année',
        '2026-01-07 09:00:00',
        '2026-01-07 11:00:00',
        'Salle B204',
        'course',
        '#3788d8',
        0,
        NULL,
        NOW(),
        NOW()
    ),
    (
        @demo_calendar_id,
        @demo_user_id,
        'Réunion Pédagogique',
        'Bilan du trimestre et axes d''amélioration',
        '2026-01-07 14:00:00',
        '2026-01-07 16:00:00',
        'Salle des professeurs',
        'meeting',
        '#4caf50',
        0,
        NULL,
        NOW(),
        NOW()
    ),
    (
        @demo_calendar_id,
        @demo_user_id,
        'Examen BTS SIO',
        'Examen de développement web',
        '2026-01-08 10:00:00',
        '2026-01-08 12:00:00',
        'Salle A101',
        'exam',
        '#f44336',
        0,
        NULL,
        NOW(),
        NOW()
    ),
    (
        @demo_calendar_id,
        @demo_user_id,
        'Formation Continue',
        'Formation sur les nouvelles technologies',
        '2026-01-09 09:00:00',
        '2026-01-09 17:00:00',
        'Salle C301',
        'training',
        '#ff9800',
        0,
        NULL,
        NOW(),
        NOW()
    );
