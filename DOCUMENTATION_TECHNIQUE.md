# Documentation Technique - Application d'Agenda Partagé

## Table des Matières
1. [Architecture Générale](#1-architecture-générale)
2. [Stack Technologique](#2-stack-technologique)
3. [Installation et Configuration](#3-installation-et-configuration)
4. [Structure du Projet](#4-structure-du-projet)
5. [Base de Données](#5-base-de-données)
6. [Backend - Symfony](#6-backend---symfony)
7. [Frontend - React](#7-frontend---react)
8. [API REST](#8-api-rest)
9. [Authentification et Sécurité](#9-authentification-et-sécurité)
10. [Tests](#10-tests)
11. [Déploiement](#11-déploiement)
12. [Maintenance](#12-maintenance)

---

## 1. Architecture Générale

### 1.1 Vue d'ensemble

L'application suit une architecture client-serveur avec séparation frontend/backend :

```
┌─────────────────────────────────────────────────────────────┐
│                    NAVIGATEUR WEB                           │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐ │
│  │           React Application (Frontend)                │ │
│  │  - Components UI                                      │ │
│  │  - State Management                                   │ │
│  │  - API Client (Axios)                                 │ │
│  │  - Routing (React Router)                             │ │
│  └────────────────────┬──────────────────────────────────┘ │
└─────────────────────────┼────────────────────────────────────┘
                          │
                          │ HTTPS / REST API
                          │ JSON + JWT Token
                          ▼
┌─────────────────────────────────────────────────────────────┐
│               SERVEUR APPLICATION                           │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐ │
│  │           Symfony Backend (API)                       │ │
│  │                                                       │ │
│  │  ┌─────────────────────────────────────────────────┐ │ │
│  │  │  Controllers (API Endpoints)                    │ │ │
│  │  │  - UserController                               │ │ │
│  │  │  - EventController                              │ │ │
│  │  │  - CalendarController                           │ │ │
│  │  │  - PermissionController                         │ │ │
│  │  └─────────────────────────────────────────────────┘ │ │
│  │                       │                               │ │
│  │  ┌─────────────────────────────────────────────────┐ │ │
│  │  │  Services (Business Logic)                      │ │ │
│  │  │  - UserService                                  │ │ │
│  │  │  - EventService                                 │ │ │
│  │  │  - CalendarService                              │ │ │
│  │  │  - PermissionService                            │ │ │
│  │  │  - NotificationService                          │ │ │
│  │  └─────────────────────────────────────────────────┘ │ │
│  │                       │                               │ │
│  │  ┌─────────────────────────────────────────────────┐ │ │
│  │  │  Entities (Domain Model)                        │ │ │
│  │  │  - User                                         │ │ │
│  │  │  - Event                                        │ │ │
│  │  │  - Calendar                                     │ │ │
│  │  │  - CalendarPermission                           │ │ │
│  │  └─────────────────────────────────────────────────┘ │ │
│  │                       │                               │ │
│  │  ┌─────────────────────────────────────────────────┐ │ │
│  │  │  Repositories (Data Access)                     │ │ │
│  │  └─────────────────────────────────────────────────┘ │ │
│  │                       │                               │ │
│  │                       │ Doctrine ORM                  │ │
│  └───────────────────────┼───────────────────────────────┘ │
└─────────────────────────┼─────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                  BASE DE DONNÉES                            │
│                    MySQL / PostgreSQL                       │
│                                                             │
│  Tables : users, calendars, events, calendar_permissions    │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Pattern MVC / API REST

- **Frontend (React)** : Vue et Contrôleur
- **Backend (Symfony)** : Contrôleur API et Modèle
- **Communication** : API REST avec JSON
- **Authentification** : JWT (JSON Web Tokens)

---

## 2. Stack Technologique

### 2.1 Backend

| Technologie | Version | Rôle |
|-------------|---------|------|
| **PHP** | 8.1+ | Langage serveur |
| **Symfony** | 6.4 / 7.x | Framework PHP |
| **Doctrine ORM** | 2.x | Mapping objet-relationnel |
| **Doctrine Migrations** | 3.x | Gestion des migrations DB |
| **LexikJWTAuthenticationBundle** | 2.x | Authentification JWT |
| **NelmioApiDocBundle** | 4.x | Documentation API |
| **Composer** | 2.x | Gestionnaire de dépendances |

### 2.2 Frontend

| Technologie | Version | Rôle |
|-------------|---------|------|
| **React** | 18.x | Library UI |
| **Vite** | 5.x | Build tool |
| **React Router** | 6.x | Routing |
| **Axios** | 1.x | Client HTTP |
| **FullCalendar** | 6.x | Composant calendrier |
| **Material-UI (MUI)** | 5.x | Bibliothèque UI |
| **React Context API** | - | Gestion d'état |
| **npm / yarn** | - | Gestionnaire de paquets |

### 2.3 Base de Données

| Technologie | Version | Rôle |
|-------------|---------|------|
| **MySQL** | 8.0+ | SGBD relationnel |
| **ou PostgreSQL** | 14+ | Alternative SGBD |

### 2.4 Outils de Développement

| Outil | Usage |
|-------|-------|
| **Git** | Versioning |
| **PHPUnit** | Tests unitaires backend |
| **Jest** | Tests unitaires frontend |
| **PHPStan** | Analyse statique PHP |
| **ESLint** | Linter JavaScript |
| **Postman** | Tests API |

---

## 3. Installation et Configuration

### 3.1 Prérequis

- PHP 8.1 ou supérieur
- Composer
- Node.js 18+ et npm
- MySQL 8.0+ ou PostgreSQL 14+
- Git

### 3.2 Installation Backend (Symfony)

```bash
# Cloner le projet
git clone https://github.com/votre-repo/agenda-partage.git
cd agenda-partage/backend

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env .env.local

# Éditer .env.local avec vos paramètres
# DATABASE_URL="mysql://user:password@127.0.0.1:3306/agenda_db"

# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# (Optionnel) Charger les fixtures
php bin/console doctrine:fixtures:load

# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair

# Lancer le serveur de développement
symfony server:start
# ou
php -S localhost:8000 -t public
```

### 3.3 Installation Frontend (React)

```bash
# Aller dans le dossier frontend
cd ../frontend

# Installer les dépendances
npm install

# Configurer l'environnement
cp .env.example .env

# Éditer .env avec l'URL de l'API
# VITE_API_URL=http://localhost:8000/api

# Lancer le serveur de développement
npm run dev

# L'application est accessible sur http://localhost:5173
```

### 3.4 Configuration de la Base de Données

#### MySQL
```sql
CREATE DATABASE agenda_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'agenda_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON agenda_db.* TO 'agenda_user'@'localhost';
FLUSH PRIVILEGES;
```

#### PostgreSQL
```sql
CREATE DATABASE agenda_db;
CREATE USER agenda_user WITH ENCRYPTED PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE agenda_db TO agenda_user;
```

### 3.5 Variables d'Environnement

#### Backend (.env.local)
```env
APP_ENV=dev
APP_SECRET=your-secret-key

# Database
DATABASE_URL="mysql://agenda_user:secure_password@127.0.0.1:3306/agenda_db?serverVersion=8.0"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase
JWT_TOKEN_TTL=3600

# CORS
CORS_ALLOW_ORIGIN=^http://localhost:5173$

# Mailer (optionnel)
MAILER_DSN=smtp://localhost:1025
```

#### Frontend (.env)
```env
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=Agenda Partagé
```

---

## 4. Structure du Projet

### 4.1 Structure Backend (Symfony)

```
backend/
├── bin/
│   └── console                 # CLI Symfony
├── config/
│   ├── packages/               # Configuration bundles
│   │   ├── doctrine.yaml
│   │   ├── security.yaml
│   │   ├── lexik_jwt_authentication.yaml
│   │   └── ...
│   ├── routes/                 # Routes
│   │   └── api.yaml
│   ├── bundles.php
│   └── services.yaml           # Services
├── migrations/                 # Migrations Doctrine
├── public/
│   └── index.php               # Point d'entrée
├── src/
│   ├── Controller/             # Contrôleurs API
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   ├── UserController.php
│   │   │   ├── CalendarController.php
│   │   │   ├── EventController.php
│   │   │   └── PermissionController.php
│   ├── Entity/                 # Entités
│   │   ├── User.php
│   │   ├── Calendar.php
│   │   ├── Event.php
│   │   └── CalendarPermission.php
│   ├── Repository/             # Repositories
│   │   ├── UserRepository.php
│   │   ├── CalendarRepository.php
│   │   ├── EventRepository.php
│   │   └── CalendarPermissionRepository.php
│   ├── Service/                # Services métier
│   │   ├── UserService.php
│   │   ├── CalendarService.php
│   │   ├── EventService.php
│   │   ├── PermissionService.php
│   │   └── NotificationService.php
│   ├── Security/               # Sécurité
│   │   ├── JWTAuthenticator.php
│   │   └── Voter/
│   │       ├── CalendarVoter.php
│   │       └── EventVoter.php
│   ├── DataFixtures/           # Données de test
│   │   └── AppFixtures.php
│   └── Kernel.php
├── tests/                      # Tests
│   ├── Unit/
│   └── Functional/
├── var/                        # Cache, logs
├── vendor/                     # Dépendances
├── .env                        # Config environnement
├── composer.json
└── phpunit.xml.dist
```

### 4.2 Structure Frontend (React)

```
frontend/
├── public/                     # Fichiers statiques
│   └── assets/
├── src/
│   ├── api/                    # Client API
│   │   ├── axios.js            # Configuration Axios
│   │   ├── auth.js             # Auth endpoints
│   │   ├── calendars.js        # Calendar endpoints
│   │   ├── events.js           # Event endpoints
│   │   └── users.js            # User endpoints
│   ├── components/             # Composants
│   │   ├── common/             # Composants réutilisables
│   │   │   ├── Header.jsx
│   │   │   ├── Sidebar.jsx
│   │   │   ├── Button.jsx
│   │   │   └── Modal.jsx
│   │   ├── auth/               # Authentification
│   │   │   ├── LoginForm.jsx
│   │   │   └── PrivateRoute.jsx
│   │   ├── calendar/           # Calendrier
│   │   │   ├── CalendarView.jsx
│   │   │   ├── EventModal.jsx
│   │   │   ├── EventForm.jsx
│   │   │   └── CalendarFilter.jsx
│   │   ├── agenda/             # Agendas
│   │   │   ├── AgendaList.jsx
│   │   │   ├── AgendaForm.jsx
│   │   │   └── ShareModal.jsx
│   │   └── users/              # Utilisateurs
│   │       ├── UserList.jsx
│   │       └── UserForm.jsx
│   ├── contexts/               # Contextes React
│   │   ├── AuthContext.jsx
│   │   └── CalendarContext.jsx
│   ├── hooks/                  # Custom hooks
│   │   ├── useAuth.js
│   │   ├── useCalendar.js
│   │   └── useEvents.js
│   ├── pages/                  # Pages
│   │   ├── LoginPage.jsx
│   │   ├── DashboardPage.jsx
│   │   ├── CalendarPage.jsx
│   │   ├── AgendasPage.jsx
│   │   ├── ProfilePage.jsx
│   │   └── UsersPage.jsx (Admin)
│   ├── utils/                  # Utilitaires
│   │   ├── dateUtils.js
│   │   ├── validation.js
│   │   └── constants.js
│   ├── styles/                 # Styles
│   │   └── theme.js            # Thème MUI
│   ├── App.jsx                 # Composant racine
│   ├── main.jsx                # Point d'entrée
│   └── router.jsx              # Configuration routes
├── .env
├── .eslintrc.js
├── package.json
├── vite.config.js
└── index.html
```

---

## 5. Base de Données

### 5.1 Schéma de la Base de Données

```sql
┌─────────────────────────┐
│        users            │
├─────────────────────────┤
│ id (PK)                 │
│ email (UNIQUE)          │
│ password                │
│ first_name              │
│ last_name               │
│ roles (JSON)            │
│ created_at              │
│ updated_at              │
└─────────────────────────┘
           │ 1
           │
           │ N
┌─────────────────────────┐
│      calendars          │
├─────────────────────────┤
│ id (PK)                 │
│ name                    │
│ description             │
│ type (enum)             │
│ color                   │
│ owner_id (FK → users)   │
│ created_at              │
│ updated_at              │
└─────────────────────────┘
           │ 1
           │
           │ N
┌─────────────────────────┐
│        events           │
├─────────────────────────┤
│ id (PK)                 │
│ title                   │
│ description             │
│ start_date              │
│ end_date                │
│ location                │
│ type (enum)             │
│ color                   │
│ is_recurrent            │
│ recurrence_pattern      │
│ calendar_id (FK)        │
│ created_by_id (FK)      │
│ created_at              │
│ updated_at              │
└─────────────────────────┘

┌─────────────────────────────┐
│  calendar_permissions       │
├─────────────────────────────┤
│ id (PK)                     │
│ calendar_id (FK→calendars)  │
│ user_id (FK → users)        │
│ permission (enum)           │
│ granted_at                  │
└─────────────────────────────┘
```

### 5.2 Types et Enums

```php
// User Roles
ROLE_ADMIN         // Administrateur
ROLE_TEACHER       // Professeur
ROLE_STAFF         // Personnel administratif
ROLE_GUEST         // Intervenant externe

// Calendar Type
TYPE_PERSONAL      // Agenda personnel
TYPE_SHARED        // Agenda partagé
TYPE_PUBLIC        // Agenda public

// Event Type
TYPE_COURSE        // Cours
TYPE_MEETING       // Réunion
TYPE_EXAM          // Examen
TYPE_ADMINISTRATIVE // Administratif
TYPE_TRAINING      // Formation
TYPE_OTHER         // Autre

// Permission Level
PERMISSION_VIEW    // Consultation
PERMISSION_EDIT    // Modification
PERMISSION_ADMIN   // Administration
```

### 5.3 Contraintes et Index

```sql
-- Index pour optimisation
CREATE INDEX idx_events_calendar ON events(calendar_id);
CREATE INDEX idx_events_dates ON events(start_date, end_date);
CREATE INDEX idx_permissions_calendar ON calendar_permissions(calendar_id);
CREATE INDEX idx_permissions_user ON calendar_permissions(user_id);

-- Contraintes
ALTER TABLE calendars 
  ADD CONSTRAINT fk_calendar_owner 
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE events 
  ADD CONSTRAINT fk_event_calendar 
  FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE;

ALTER TABLE calendar_permissions 
  ADD CONSTRAINT fk_permission_calendar 
  FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE;

ALTER TABLE calendar_permissions 
  ADD CONSTRAINT fk_permission_user 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Contrainte unique (un utilisateur ne peut avoir qu'une permission par agenda)
ALTER TABLE calendar_permissions 
  ADD CONSTRAINT unique_calendar_user 
  UNIQUE(calendar_id, user_id);
```

---

## 6. Backend - Symfony

### 6.1 Entités Doctrine

#### User.php
```php
<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    // Getters et Setters...
}
```

#### Calendar.php
```php
<?php

namespace App\Entity;

use App\Repository\CalendarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendarRepository::class)]
#[ORM\Table(name: 'calendars')]
class Calendar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // personal, shared, public

    #[ORM\Column(length: 7)]
    private ?string $color = null; // HEX color

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'calendar', targetEntity: Event::class, orphanRemoval: true)]
    private Collection $events;

    #[ORM\OneToMany(mappedBy: 'calendar', targetEntity: CalendarPermission::class, orphanRemoval: true)]
    private Collection $permissions;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters et Setters...
}
```

#### Event.php
```php
<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // course, meeting, exam, etc.

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isRecurrent = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $recurrencePattern = null;

    #[ORM\ManyToOne(targetEntity: Calendar::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Calendar $calendar = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    // Getters et Setters...
}
```

#### CalendarPermission.php
```php
<?php

namespace App\Entity;

use App\Repository\CalendarPermissionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendarPermissionRepository::class)]
#[ORM\Table(name: 'calendar_permissions')]
#[ORM\UniqueConstraint(name: 'unique_calendar_user', columns: ['calendar_id', 'user_id'])]
class CalendarPermission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Calendar::class, inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Calendar $calendar = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $permission = null; // view, edit, admin

    #[ORM\Column]
    private ?\DateTimeImmutable $grantedAt = null;

    // Getters et Setters...
}
```

### 6.2 Services

Les services encapsulent la logique métier :

```php
<?php

namespace App\Service;

use App\Entity\Calendar;
use App\Entity\CalendarPermission;
use App\Entity\User;
use App\Repository\CalendarPermissionRepository;
use App\Repository\CalendarRepository;
use Doctrine\ORM\EntityManagerInterface;

class CalendarService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CalendarRepository $calendarRepository,
        private CalendarPermissionRepository $permissionRepository
    ) {}

    public function createCalendar(User $owner, array $data): Calendar
    {
        $calendar = new Calendar();
        $calendar->setName($data['name']);
        $calendar->setDescription($data['description'] ?? null);
        $calendar->setType($data['type']);
        $calendar->setColor($data['color'] ?? '#3788d8');
        $calendar->setOwner($owner);
        
        $this->em->persist($calendar);
        $this->em->flush();
        
        return $calendar;
    }

    public function getAccessibleCalendars(User $user): array
    {
        // Calendars owned by user
        $ownedCalendars = $this->calendarRepository->findBy(['owner' => $user]);
        
        // Calendars shared with user
        $permissions = $this->permissionRepository->findBy(['user' => $user]);
        $sharedCalendars = array_map(
            fn($p) => $p->getCalendar(), 
            $permissions
        );
        
        // Public calendars
        $publicCalendars = $this->calendarRepository->findBy(['type' => 'public']);
        
        return array_unique(array_merge($ownedCalendars, $sharedCalendars, $publicCalendars));
    }

    public function shareCalendar(Calendar $calendar, User $user, string $permission): void
    {
        $calendarPermission = new CalendarPermission();
        $calendarPermission->setCalendar($calendar);
        $calendarPermission->setUser($user);
        $calendarPermission->setPermission($permission);
        $calendarPermission->setGrantedAt(new \DateTimeImmutable());
        
        $this->em->persist($calendarPermission);
        $this->em->flush();
    }

    public function getUserPermission(Calendar $calendar, User $user): ?string
    {
        // Owner has admin permission
        if ($calendar->getOwner()->getId() === $user->getId()) {
            return 'admin';
        }
        
        // Check explicit permissions
        $permission = $this->permissionRepository->findOneBy([
            'calendar' => $calendar,
            'user' => $user
        ]);
        
        return $permission?->getPermission();
    }
}
```

### 6.3 Contrôleurs API

```php
<?php

namespace App\Controller\Api;

use App\Entity\Calendar;
use App\Service\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/calendars', name: 'api_calendars_')]
class CalendarController extends AbstractController
{
    public function __construct(
        private CalendarService $calendarService
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $calendars = $this->calendarService->getAccessibleCalendars($user);
        
        return $this->json($calendars, Response::HTTP_OK, [], [
            'groups' => ['calendar:read']
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $calendar = $this->calendarService->createCalendar(
            $this->getUser(),
            $data
        );
        
        return $this->json($calendar, Response::HTTP_CREATED, [], [
            'groups' => ['calendar:read']
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Calendar $calendar): JsonResponse
    {
        // Vérifier les permissions via Voter
        $this->denyAccessUnlessGranted('view', $calendar);
        
        return $this->json($calendar, Response::HTTP_OK, [], [
            'groups' => ['calendar:read', 'calendar:detail']
        ]);
    }

    // Autres méthodes : update, delete, share...
}
```

### 6.4 Voters (Gestion des Autorisations)

```php
<?php

namespace App\Security\Voter;

use App\Entity\Calendar;
use App\Entity\User;
use App\Service\CalendarService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CalendarVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const ADMIN = 'admin';

    public function __construct(
        private CalendarService $calendarService
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::ADMIN])
            && $subject instanceof Calendar;
    }

    protected function voteOnAttribute(
        string $attribute, 
        mixed $subject, 
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Calendar $calendar */
        $calendar = $subject;

        // Admin a tous les droits
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        $permission = $this->calendarService->getUserPermission($calendar, $user);

        return match($attribute) {
            self::VIEW => in_array($permission, ['view', 'edit', 'admin']),
            self::EDIT => in_array($permission, ['edit', 'admin']),
            self::ADMIN => $permission === 'admin',
            default => false,
        };
    }
}
```

---

## 7. Frontend - React

### 7.1 Configuration Axios

```javascript
// src/api/axios.js
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Intercepteur pour ajouter le token JWT
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Intercepteur pour gérer les erreurs
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### 7.2 Services API

```javascript
// src/api/calendars.js
import api from './axios';

export const calendarService = {
  getAll: () => api.get('/calendars'),
  
  getById: (id) => api.get(`/calendars/${id}`),
  
  create: (data) => api.post('/calendars', data),
  
  update: (id, data) => api.put(`/calendars/${id}`, data),
  
  delete: (id) => api.delete(`/calendars/${id}`),
  
  share: (id, userId, permission) => 
    api.post(`/calendars/${id}/share`, { userId, permission }),
  
  getPermissions: (id) => api.get(`/calendars/${id}/permissions`),
};
```

```javascript
// src/api/events.js
import api from './axios';

export const eventService = {
  getAll: (calendarId) => 
    api.get(`/calendars/${calendarId}/events`),
  
  getByDateRange: (start, end) => 
    api.get('/events', { params: { start, end } }),
  
  create: (data) => api.post('/events', data),
  
  update: (id, data) => api.put(`/events/${id}`, data),
  
  delete: (id) => api.delete(`/events/${id}`),
};
```

### 7.3 Contexte d'Authentification

```javascript
// src/contexts/AuthContext.jsx
import React, { createContext, useState, useEffect } from 'react';
import { authService } from '../api/auth';

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (token) {
      loadUser();
    } else {
      setLoading(false);
    }
  }, []);

  const loadUser = async () => {
    try {
      const response = await authService.me();
      setUser(response.data);
    } catch (error) {
      localStorage.removeItem('token');
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    const response = await authService.login(email, password);
    localStorage.setItem('token', response.data.token);
    setUser(response.data.user);
  };

  const logout = () => {
    localStorage.removeItem('token');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};
```

### 7.4 Composant Calendrier

```javascript
// src/components/calendar/CalendarView.jsx
import React, { useState, useEffect } from 'react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { eventService } from '../../api/events';

const CalendarView = ({ selectedCalendars }) => {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadEvents();
  }, [selectedCalendars]);

  const loadEvents = async () => {
    try {
      setLoading(true);
      const responses = await Promise.all(
        selectedCalendars.map(cal => eventService.getAll(cal.id))
      );
      
      const allEvents = responses.flatMap(r => 
        r.data.map(event => ({
          id: event.id,
          title: event.title,
          start: event.startDate,
          end: event.endDate,
          backgroundColor: event.color || event.calendar.color,
          extendedProps: event
        }))
      );
      
      setEvents(allEvents);
    } catch (error) {
      console.error('Error loading events:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleEventClick = (info) => {
    // Ouvrir modal de détails/édition
    console.log('Event clicked:', info.event);
  };

  const handleDateClick = (info) => {
    // Ouvrir modal de création
    console.log('Date clicked:', info.dateStr);
  };

  return (
    <FullCalendar
      plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
      initialView="dayGridMonth"
      headerToolbar={{
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      }}
      events={events}
      eventClick={handleEventClick}
      dateClick={handleDateClick}
      editable={true}
      selectable={true}
      locale="fr"
      firstDay={1}
      height="auto"
    />
  );
};

export default CalendarView;
```

### 7.5 Routage

```javascript
// src/router.jsx
import { createBrowserRouter } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import CalendarPage from './pages/CalendarPage';
import AgendasPage from './pages/AgendasPage';
import ProfilePage from './pages/ProfilePage';
import UsersPage from './pages/UsersPage';
import PrivateRoute from './components/auth/PrivateRoute';
import AdminRoute from './components/auth/AdminRoute';

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <LoginPage />,
  },
  {
    path: '/',
    element: <PrivateRoute />,
    children: [
      {
        index: true,
        element: <DashboardPage />,
      },
      {
        path: 'calendar',
        element: <CalendarPage />,
      },
      {
        path: 'agendas',
        element: <AgendasPage />,
      },
      {
        path: 'profile',
        element: <ProfilePage />,
      },
      {
        path: 'users',
        element: (
          <AdminRoute>
            <UsersPage />
          </AdminRoute>
        ),
      },
    ],
  },
]);
```

---

## 8. API REST

### 8.1 Endpoints Principaux

#### Authentification
```
POST   /api/auth/login       # Connexion
POST   /api/auth/register    # Inscription (admin seulement)
GET    /api/auth/me          # Utilisateur connecté
POST   /api/auth/refresh     # Rafraîchir token
```

#### Utilisateurs
```
GET    /api/users            # Liste des utilisateurs (admin)
GET    /api/users/{id}       # Détails utilisateur
POST   /api/users            # Créer utilisateur (admin)
PUT    /api/users/{id}       # Modifier utilisateur
DELETE /api/users/{id}       # Supprimer utilisateur (admin)
```

#### Calendriers
```
GET    /api/calendars                    # Calendriers accessibles
GET    /api/calendars/{id}               # Détails calendrier
POST   /api/calendars                    # Créer calendrier
PUT    /api/calendars/{id}               # Modifier calendrier
DELETE /api/calendars/{id}               # Supprimer calendrier
GET    /api/calendars/{id}/permissions   # Permissions du calendrier
POST   /api/calendars/{id}/share         # Partager calendrier
DELETE /api/calendars/{id}/permissions/{userId}  # Retirer permission
```

#### Événements
```
GET    /api/events                       # Tous les événements (avec filtres)
GET    /api/events/{id}                  # Détails événement
POST   /api/events                       # Créer événement
PUT    /api/events/{id}                  # Modifier événement
DELETE /api/events/{id}                  # Supprimer événement
GET    /api/calendars/{id}/events        # Événements d'un calendrier
```

### 8.2 Formats de Requête/Réponse

#### POST /api/auth/login
```json
// Request
{
  "email": "j.dupont@lycee.fr",
  "password": "password123"
}

// Response 200
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "j.dupont@lycee.fr",
    "firstName": "Jean",
    "lastName": "Dupont",
    "roles": ["ROLE_TEACHER"]
  }
}
```

#### POST /api/calendars
```json
// Request
{
  "name": "Professeurs Mathématiques",
  "description": "Agenda partagé des profs de maths",
  "type": "shared",
  "color": "#4caf50"
}

// Response 201
{
  "id": 5,
  "name": "Professeurs Mathématiques",
  "description": "Agenda partagé des profs de maths",
  "type": "shared",
  "color": "#4caf50",
  "owner": {
    "id": 1,
    "firstName": "Jean",
    "lastName": "Dupont"
  },
  "createdAt": "2026-01-06T10:30:00+00:00"
}
```

#### POST /api/events
```json
// Request
{
  "title": "Réunion pédagogique",
  "description": "Bilan du trimestre",
  "startDate": "2026-01-07T14:00:00",
  "endDate": "2026-01-07T16:00:00",
  "location": "Salle B204",
  "type": "meeting",
  "calendarId": 5,
  "color": "#f44336"
}

// Response 201
{
  "id": 42,
  "title": "Réunion pédagogique",
  "description": "Bilan du trimestre",
  "startDate": "2026-01-07T14:00:00+00:00",
  "endDate": "2026-01-07T16:00:00+00:00",
  "location": "Salle B204",
  "type": "meeting",
  "color": "#f44336",
  "calendar": {
    "id": 5,
    "name": "Professeurs Mathématiques"
  },
  "createdBy": {
    "id": 1,
    "firstName": "Jean",
    "lastName": "Dupont"
  },
  "createdAt": "2026-01-06T10:35:00+00:00"
}
```

### 8.3 Codes de Statut HTTP

| Code | Signification | Usage |
|------|---------------|-------|
| 200 | OK | Requête réussie (GET, PUT) |
| 201 | Created | Ressource créée (POST) |
| 204 | No Content | Suppression réussie (DELETE) |
| 400 | Bad Request | Données invalides |
| 401 | Unauthorized | Non authentifié |
| 403 | Forbidden | Pas les permissions |
| 404 | Not Found | Ressource introuvable |
| 422 | Unprocessable Entity | Validation échouée |
| 500 | Server Error | Erreur serveur |

---

## 9. Authentification et Sécurité

### 9.1 JWT (JSON Web Token)

#### Configuration Symfony
```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600 # 1 heure
```

#### Génération des Clés
```bash
php bin/console lexik:jwt:generate-keypair
```

#### Flux d'Authentification
1. Client envoie email/password à `/api/auth/login`
2. Backend valide les credentials
3. Backend génère un token JWT signé
4. Client stocke le token (localStorage)
5. Client envoie le token dans chaque requête (header `Authorization: Bearer {token}`)
6. Backend vérifie et décode le token

### 9.2 Configuration de la Sécurité

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
    
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
    
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        
        login:
            pattern: ^/api/auth/login
            stateless: true
            json_login:
                check_path: /api/auth/login
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
        
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
    
    access_control:
        - { path: ^/api/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/users, roles: ROLE_ADMIN, methods: [POST, DELETE] }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

### 9.3 Protection CSRF

```yaml
# config/packages/framework.yaml
framework:
    csrf_protection: true
```

Pour les formulaires Symfony, utiliser `csrf_token()`. Pour l'API REST avec JWT, CSRF n'est pas nécessaire (stateless).

### 9.4 CORS (Cross-Origin Resource Sharing)

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/': ~
```

### 9.5 Validation des Données

```php
// Utilisation des contraintes Symfony
use Symfony\Component\Validator\Constraints as Assert;

class Event
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null;

    #[Assert\NotBlank]
    private ?\DateTimeInterface $startDate = null;

    #[Assert\NotBlank]
    #[Assert\GreaterThan(propertyPath: 'startDate')]
    private ?\DateTimeInterface $endDate = null;
}
```

---

## 10. Tests

### 10.1 Tests Backend (PHPUnit)

```php
<?php
// tests/Functional/CalendarControllerTest.php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalendarControllerTest extends WebTestCase
{
    private $client;
    private $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->token = $this->authenticate();
    }

    private function authenticate(): string
    {
        $this->client->request('POST', '/api/auth/login', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@lycee.fr',
                'password' => 'password'
            ])
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['token'];
    }

    public function testGetCalendars(): void
    {
        $this->client->request('GET', '/api/calendars', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testCreateCalendar(): void
    {
        $this->client->request('POST', '/api/calendars', [], [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'name' => 'Test Calendar',
                'type' => 'personal',
                'color' => '#3788d8'
            ])
        );

        $this->assertResponseStatusCodeSame(201);
    }
}
```

### 10.2 Tests Frontend (Jest + React Testing Library)

```javascript
// src/components/__tests__/LoginForm.test.jsx
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import LoginForm from '../auth/LoginForm';
import { AuthProvider } from '../../contexts/AuthContext';

describe('LoginForm', () => {
  test('renders login form', () => {
    render(
      <AuthProvider>
        <LoginForm />
      </AuthProvider>
    );

    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/mot de passe/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /se connecter/i }))
      .toBeInTheDocument();
  });

  test('submits login form', async () => {
    const mockLogin = jest.fn();
    
    render(
      <AuthProvider value={{ login: mockLogin }}>
        <LoginForm />
      </AuthProvider>
    );

    fireEvent.change(screen.getByLabelText(/email/i), {
      target: { value: 'test@lycee.fr' }
    });
    
    fireEvent.change(screen.getByLabelText(/mot de passe/i), {
      target: { value: 'password' }
    });

    fireEvent.click(screen.getByRole('button', { name: /se connecter/i }));

    await waitFor(() => {
      expect(mockLogin).toHaveBeenCalledWith('test@lycee.fr', 'password');
    });
  });
});
```

### 10.3 Commandes de Test

```bash
# Backend
php bin/phpunit                    # Tous les tests
php bin/phpunit --testdox          # Format lisible
php bin/phpunit --coverage-html    # Couverture de code

# Frontend
npm run test                       # Tests interactifs
npm run test:coverage              # Couverture de code
```

---

## 11. Déploiement

### 11.1 Environnement de Production

#### Backend
```bash
# Installer les dépendances (sans dev)
composer install --no-dev --optimize-autoloader

# Vider le cache
APP_ENV=prod php bin/console cache:clear

# Créer les assets (si nécessaire)
APP_ENV=prod php bin/console asset-map:compile

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Optimiser Composer
composer dump-autoload --optimize --classmap-authoritative
```

#### Frontend
```bash
# Build de production
npm run build

# Résultat dans le dossier dist/
# À déployer sur un serveur web (Nginx, Apache, CDN)
```

### 11.2 Configuration Serveur Web

#### Apache (.htaccess pour Symfony)
```apache
# public/.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name agenda.lycee.fr;
    root /var/www/agenda/backend/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}

# Frontend
server {
    listen 80;
    server_name app.agenda.lycee.fr;
    root /var/www/agenda/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

### 11.3 Docker (Optionnel)

```yaml
# compose.yaml
version: '3.8'

services:
  php:
    image: php:8.1-fpm
    volumes:
      - ./backend:/var/www/html
    depends_on:
      - db

  nginx:
    image: nginx:alpine
    ports:
      - "8000:80"
    volumes:
      - ./backend:/var/www/html
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: agenda_db
      MYSQL_USER: agenda_user
      MYSQL_PASSWORD: password
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

  frontend:
    image: node:18-alpine
    working_dir: /app
    volumes:
      - ./frontend:/app
    command: npm run dev
    ports:
      - "5173:5173"

volumes:
  db_data:
```

### 11.4 Checklist de Déploiement

- [ ] Variables d'environnement configurées (.env.prod)
- [ ] Base de données créée et migrée
- [ ] Clés JWT générées
- [ ] Cache Symfony vidé
- [ ] Assets frontend compilés
- [ ] Permissions fichiers correctes (var/, config/jwt/)
- [ ] HTTPS configuré (Let's Encrypt)
- [ ] Logs activés et monitoration
- [ ] Sauvegardes automatiques configurées
- [ ] Tests de charge effectués

---

## 12. Maintenance

### 12.1 Logs

#### Backend
```bash
# Consulter les logs
tail -f backend/var/log/dev.log
tail -f backend/var/log/prod.log

# Logs par niveau
grep ERROR backend/var/log/prod.log
```

#### Configuration Monolog
```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['app', 'security']
    handlers:
        main:
            type: rotating_file
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            level: info
            max_files: 10
        
        security:
            type: stream
            path: '%kernel.logs_dir%/security.log'
            level: debug
            channels: ['security']
```

### 12.2 Sauvegardes

```bash
#!/bin/bash
# backup.sh - Script de sauvegarde quotidienne

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/agenda"

# Sauvegarde de la base de données
mysqldump -u agenda_user -p'password' agenda_db > "$BACKUP_DIR/db_$DATE.sql"

# Sauvegarde des fichiers
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /var/www/agenda

# Nettoyer les sauvegardes de plus de 30 jours
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

Ajouter au cron :
```bash
0 2 * * * /usr/local/bin/backup.sh
```

### 12.3 Mise à Jour

```bash
# Backend
composer update
php bin/console doctrine:migrations:migrate
php bin/console cache:clear

# Frontend
npm update
npm run build

# Tests après mise à jour
php bin/phpunit
npm run test
```

### 12.4 Monitoring

- **Logs applicatifs** : Monolog
- **Logs serveur** : Nginx/Apache access.log, error.log
- **Performance** : Symfony Profiler (dev), New Relic, Blackfire
- **Uptime** : UptimeRobot, Pingdom
- **Erreurs** : Sentry

---

## Annexes

### A. Commandes Utiles

#### Symfony
```bash
# Créer une entité
php bin/console make:entity

# Créer une migration
php bin/console make:migration

# Créer un contrôleur
php bin/console make:controller

# Lister les routes
php bin/console debug:router

# Vider le cache
php bin/console cache:clear
```

#### React
```bash
# Créer un composant
# (manuellement dans src/components/)

# Analyser le bundle
npm run build -- --report

# Formater le code
npm run format
```

### B. Ressources

- **Symfony** : https://symfony.com/doc/current/index.html
- **React** : https://react.dev/
- **Doctrine** : https://www.doctrine-project.org/
- **FullCalendar** : https://fullcalendar.io/docs
- **JWT** : https://jwt.io/
- **Material-UI** : https://mui.com/

---

**Version** : 1.0  
**Date** : Janvier 2026  
**Maintenu par** : Équipe de développement
