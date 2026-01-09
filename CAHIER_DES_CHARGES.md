# Cahier des Charges - Application d'Agenda Partagé pour Lycée/BTS

## 1. Présentation du Projet

### 1.1 Contexte
Développement d'une application web d'agenda partagé destinée aux professeurs et membres du personnel d'un établissement scolaire (Lycée/BTS).

### 1.2 Objectifs
- Faciliter la gestion des emplois du temps et événements
- Permettre la collaboration entre professeurs et personnel administratif
- Gérer finement les droits d'accès (consultation, modification, administration)
- Centraliser les informations calendaires de l'établissement

### 1.3 Périmètre
Application web accessible via navigateur, composée de :
- Backend : Symfony (PHP)
- Frontend : React (JavaScript)
- Base de données : MySQL/PostgreSQL
- API RESTful pour la communication

---

## 2. Acteurs du Système

### 2.1 Utilisateurs
| Profil | Description | Droits |
|--------|-------------|--------|
| **Administrateur** | Direction, secrétariat | Gestion complète : utilisateurs, événements, droits |
| **Professeur** | Enseignant | Consultation tous agendas, modification agenda personnel et partagé |
| **Personnel administratif** | CPE, secrétariat | Consultation tous agendas, création événements partagés |
| **Intervenant externe** | Vacataires | Consultation limitée, modification agenda personnel |

---

## 3. Besoins Fonctionnels

### 3.1 Gestion des Utilisateurs

#### F1.1 - Authentification
- Connexion sécurisée (email/mot de passe)
- Récupération de mot de passe
- Session sécurisée avec tokens JWT

#### F1.2 - Gestion des Profils
- Création de compte (par administrateur)
- Modification des informations personnelles
- Attribution de rôles et permissions
- Désactivation/suppression de comptes

### 3.2 Gestion des Événements

#### F2.1 - Création d'Événements
- **Champs obligatoires** : titre, date/heure début, date/heure fin
- **Champs optionnels** : description, lieu, couleur, type
- **Types d'événements** :
  - Cours
  - Réunion
  - Examen
  - Événement administratif
  - Formation
  - Autre

#### F2.2 - Modification d'Événements
- Modification selon les droits de l'utilisateur
- Historique des modifications
- Notification des participants en cas de modification

#### F2.3 - Suppression d'Événements
- Suppression selon les droits
- Confirmation avant suppression
- Archive des événements supprimés

#### F2.4 - Événements Récurrents
- Répétition quotidienne, hebdomadaire, mensuelle
- Gestion des exceptions
- Modification en masse ou individuelle

### 3.3 Gestion des Agendas

#### F3.1 - Types d'Agendas
- **Agenda personnel** : visible uniquement par le propriétaire (sauf administrateur)
- **Agenda partagé** : visible par un groupe d'utilisateurs
- **Agenda public** : visible par tous les utilisateurs

#### F3.2 - Partage et Permissions
- Définir les utilisateurs ayant accès à un agenda
- Niveaux de permissions :
  - **Consultation** : voir uniquement
  - **Modification** : ajouter/modifier événements
  - **Administration** : gérer les permissions + tout
- Partage par utilisateur ou par groupe (ex: tous les professeurs de maths)

### 3.4 Visualisation

#### F4.1 - Vues du Calendrier
- Vue journalière
- Vue hebdomadaire
- Vue mensuelle
- Vue agenda (liste)

#### F4.2 - Filtres et Recherche
- Filtrer par type d'événement
- Filtrer par utilisateur/agenda
- Recherche par mot-clé
- Filtrer par lieu

#### F4.3 - Affichage
- Codes couleur par type ou par agenda
- Superposition de plusieurs agendas
- Légende claire des couleurs

### 3.5 Notifications

#### F5.1 - Notifications en Temps Réel
- Notification lors de l'ajout d'un événement sur un agenda partagé
- Notification de modification/suppression
- Rappels avant événements

#### F5.2 - Canaux de Notification
- Notifications in-app
- Email (optionnel, paramétrable par utilisateur)

### 3.6 Export et Synchronisation

#### F6.1 - Export
- Export iCal (.ics) pour intégration dans autres calendriers
- Export PDF d'une période donnée

#### F6.2 - Import
- Import de fichiers .ics

---

## 4. Besoins Non Fonctionnels

### 4.1 Performance
- Temps de réponse < 2 secondes pour affichage du calendrier
- Support de 100 utilisateurs simultanés minimum

### 4.2 Sécurité
- Chiffrement des mots de passe (bcrypt)
- Protection CSRF
- Validation des entrées utilisateur
- Tokens JWT pour l'authentification API
- HTTPS obligatoire en production

### 4.3 Ergonomie
- Interface responsive (mobile, tablette, desktop)
- Navigation intuitive
- Design moderne et épuré
- Accessibilité WCAG 2.1 niveau AA

### 4.4 Fiabilité
- Sauvegarde quotidienne de la base de données
- Gestion des erreurs avec messages explicites
- Logs des actions critiques

### 4.5 Maintenabilité
- Code documenté
- Architecture MVC/API REST
- Tests unitaires et fonctionnels

---

## 5. Architecture Technique

### 5.1 Stack Technologique

#### Backend
- **Framework** : Symfony 6.x/7.x
- **Language** : PHP 8.x
- **ORM** : Doctrine
- **API** : API Platform ou contrôleurs custom
- **Sécurité** : Symfony Security + LexikJWTAuthenticationBundle

#### Frontend
- **Framework** : React 18.x
- **Build** : Vite
- **Gestion d'état** : React Context API ou Redux
- **HTTP Client** : Axios
- **Calendrier** : FullCalendar ou React Big Calendar
- **UI** : Material-UI ou TailwindCSS

#### Base de Données
- **SGBD** : MySQL 8.x ou PostgreSQL
- **Migrations** : Doctrine Migrations

### 5.2 Architecture Applicative
```
┌─────────────────────────────────────────┐
│         Frontend (React)                │
│  - Composants UI                        │
│  - Gestion d'état                       │
│  - Appels API                           │
└──────────────┬──────────────────────────┘
               │ HTTP/REST + JWT
               ▼
┌─────────────────────────────────────────┐
│      API Backend (Symfony)              │
│  - Contrôleurs API                      │
│  - Services métier                      │
│  - Authentification JWT                 │
└──────────────┬──────────────────────────┘
               │ Doctrine ORM
               ▼
┌─────────────────────────────────────────┐
│      Base de Données (MySQL/PG)         │
│  - Utilisateurs                         │
│  - Événements                           │
│  - Agendas                              │
│  - Permissions                          │
└─────────────────────────────────────────┘
```

---

## 6. Modèle de Données (Simplifié)

### 6.1 Entités Principales

#### User
- id
- email
- password
- firstName
- lastName
- role (ROLE_ADMIN, ROLE_TEACHER, ROLE_STAFF, ROLE_GUEST)
- createdAt
- updatedAt

#### Calendar
- id
- name
- description
- type (personal, shared, public)
- owner (User)
- color
- createdAt

#### Event
- id
- title
- description
- startDate
- endDate
- location
- type (course, meeting, exam, administrative, training, other)
- color
- isRecurrent
- recurrencePattern
- calendar (Calendar)
- createdBy (User)
- createdAt
- updatedAt

#### CalendarPermission
- id
- calendar (Calendar)
- user (User)
- permission (view, edit, admin)
- grantedAt

---

## 7. Fonctionnalités Prioritaires (MVP)

### Phase 1 - MVP
1. Authentification utilisateurs
2. Création/modification/suppression d'événements
3. Affichage calendrier (vue mois/semaine/jour)
4. Gestion des permissions de base (consultation/modification)
5. Agenda personnel et partagé

### Phase 2 - Améliorations
1. Événements récurrents
2. Notifications in-app
3. Filtres et recherche avancée
4. Export iCal

### Phase 3 - Fonctionnalités Avancées
1. Notifications email
2. Import/export PDF
3. Statistiques d'utilisation
4. Thèmes personnalisables

---

## 8. Contraintes et Limites

### 8.1 Contraintes Techniques
- Compatibilité navigateurs : Chrome, Firefox, Safari, Edge (dernières versions)
- Hébergement : serveur LAMP/LEMP
- PHP 8.1 minimum
- Node.js 18+ pour le build frontend

### 8.2 Contraintes Réglementaires
- Conformité RGPD pour les données personnelles
- Politique de confidentialité
- Conditions générales d'utilisation

### 8.3 Limites
- Pas de synchronisation bidirectionnelle avec Google Calendar/Outlook
- Pas d'application mobile native (responsive web uniquement)
- Limite de 500 événements affichés simultanément

---

## 9. Planning Prévisionnel

| Phase | Durée | Tâches |
|-------|-------|--------|
| **Analyse** | 1 semaine | Spécifications détaillées, maquettes |
| **Conception** | 1 semaine | Modèle de données, architecture API |
| **Développement Backend** | 3 semaines | Entités, API, authentification, permissions |
| **Développement Frontend** | 3 semaines | Composants, intégration calendrier, formulaires |
| **Intégration** | 1 semaine | Tests d'intégration, corrections |
| **Tests** | 1 semaine | Tests fonctionnels, corrections bugs |
| **Déploiement** | 1 semaine | Mise en production, formation utilisateurs |
| **Total** | **11 semaines** | |

---

## 10. Critères de Validation

### 10.1 Tests Fonctionnels
- [ ] Un utilisateur peut se connecter
- [ ] Un administrateur peut créer des utilisateurs
- [ ] Un utilisateur peut créer un événement sur son agenda
- [ ] Un utilisateur peut partager son agenda avec permissions
- [ ] Un utilisateur voit uniquement les agendas autorisés
- [ ] Les permissions sont respectées (consultation vs modification)
- [ ] Les événements s'affichent correctement dans toutes les vues

### 10.2 Tests de Sécurité
- [ ] Impossible d'accéder aux événements sans permission
- [ ] Protection contre les injections SQL
- [ ] Protection CSRF opérationnelle
- [ ] Tokens JWT valides et expiration fonctionnelle

### 10.3 Tests de Performance
- [ ] Chargement du calendrier < 2s
- [ ] 100 utilisateurs simultanés sans dégradation

---

## 11. Livrables

1. **Code source** : repository Git avec backend et frontend
2. **Documentation technique** : installation, API, architecture
3. **Cahier des charges** : ce document
4. **Maquettes** : wireframes et mockups
5. **Guide utilisateur** : manuel d'utilisation
6. **Jeux de tests** : données de test et scénarios

---

## 12. Glossaire

- **Agenda** : ensemble d'événements appartenant à un utilisateur ou partagé
- **Événement** : élément calendaire avec date, heure, titre
- **Permission** : droit d'accès à un agenda (consultation, modification, administration)
- **Récurrent** : événement qui se répète selon un motif
- **JWT** : JSON Web Token, système d'authentification
- **RGPD** : Règlement Général sur la Protection des Données

---

**Version** : 1.0  
**Date** : Janvier 2026  
**Auteur** : Équipe de développement
