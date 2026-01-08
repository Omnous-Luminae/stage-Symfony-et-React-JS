# 🐳 Guide Docker - Projet Stage

## 📋 Prérequis

- Docker Desktop installé (version 20.10 ou supérieure)
- Docker Compose installé (version 2.0 ou supérieure)
- Au moins 4 GB de RAM disponible pour Docker

## 🚀 Démarrage rapide

### 1. Configuration initiale

Copiez le fichier d'environnement exemple :
```bash
cp .env.docker .env
```

Modifiez les valeurs dans `.env` selon vos besoins (mots de passe, ports, etc.)

### 2. Démarrer tous les services

```bash
docker-compose up -d
```

Cette commande va :
- Télécharger les images Docker nécessaires
- Construire les images personnalisées pour le backend et frontend
- Démarrer MySQL, PHPMyAdmin, Symfony (backend) et React (frontend)

### 3. Initialiser la base de données

```bash
# Créer le schéma de la base de données
docker-compose exec backend php bin/console doctrine:migrations:migrate --no-interaction

# Charger les données de démo (optionnel)
docker-compose exec backend php bin/console app:init-demo-data
```

### 4. Installer les dépendances

Si ce n'est pas déjà fait :

```bash
# Backend Symfony
docker-compose exec backend composer install

# Frontend React
docker-compose exec frontend npm install
```

## 🌐 Accéder aux services

Une fois les conteneurs démarrés :

- **Frontend React** : http://localhost:5173
- **Backend Symfony** : http://localhost:8000
- **PHPMyAdmin** : http://localhost:8080
  - Utilisateur : `agenda_user` (ou valeur de MYSQL_USER)
  - Mot de passe : `agenda_pass_secure` (ou valeur de MYSQL_PASSWORD)

## 📦 Services disponibles

### MySQL
- Image : `mysql:8.0`
- Port : `3306`
- Base de données : `agenda_db`
- Scripts d'initialisation : `backend/sql/`

### Backend Symfony (PHP 8.2 + Nginx)
- PHP-FPM avec extensions nécessaires
- Symfony CLI
- Xdebug (mode développement)
- Composer

### Frontend React
- Node.js 20
- Vite dev server avec hot reload
- Port : `5173`

### PHPMyAdmin
- Interface web pour gérer MySQL
- Port : `8080`

## 🛠️ Commandes utiles

### Gérer les conteneurs

```bash
# Démarrer les services
docker-compose up -d

# Arrêter les services
docker-compose down

# Redémarrer un service spécifique
docker-compose restart backend

# Voir les logs
docker-compose logs -f

# Voir les logs d'un service spécifique
docker-compose logs -f backend
```

### Exécuter des commandes dans les conteneurs

```bash
# Commandes Symfony
docker-compose exec backend php bin/console cache:clear
docker-compose exec backend php bin/console make:entity
docker-compose exec backend composer require package-name

# Commandes React
docker-compose exec frontend npm install package-name
docker-compose exec frontend npm run build

# Accéder au shell d'un conteneur
docker-compose exec backend sh
docker-compose exec frontend sh
```

### Base de données

```bash
# Créer une migration
docker-compose exec backend php bin/console make:migration

# Exécuter les migrations
docker-compose exec backend php bin/console doctrine:migrations:migrate

# Accéder à MySQL en ligne de commande
docker-compose exec mysql mysql -u agenda_user -p agenda_db
```

### Tests

```bash
# Exécuter les tests PHPUnit
docker-compose exec backend php bin/phpunit

# Tests avec couverture
docker-compose exec backend php bin/phpunit --coverage-html var/coverage
```

## 🔧 Développement

### Hot Reload

- **Frontend** : Le serveur Vite détecte automatiquement les changements
- **Backend** : Les modifications PHP sont prises en compte immédiatement (aucun redémarrage nécessaire)

### Debugging

**Xdebug** est configuré sur le backend :
- Port : `9003`
- Mode : debug et couverture de code
- Configuration dans votre IDE :
  - Host : `localhost`
  - Port : `9003`
  - Path mapping : `/var/www/html` → `./backend`

## 🗄️ Volumes et persistance

Les données suivantes sont persistées dans des volumes Docker :

- `mysql_data` : Données de la base MySQL
- `backend_vendor` : Dépendances Composer
- `backend_var` : Cache et logs Symfony
- `frontend_node_modules` : Dépendances npm

Pour réinitialiser complètement :
```bash
docker-compose down -v
```
⚠️ Attention : Cette commande supprime toutes les données de la base !

## 🚨 Dépannage

### Les conteneurs ne démarrent pas

```bash
# Vérifier les logs
docker-compose logs

# Reconstruire les images
docker-compose build --no-cache
docker-compose up -d
```

### Erreur de connexion à la base de données

```bash
# Vérifier que MySQL est prêt
docker-compose exec mysql mysqladmin ping -h localhost -u root -p

# Attendre que le healthcheck passe
docker-compose ps
```

### Le frontend ne se connecte pas au backend

Vérifiez la configuration CORS dans :
- `.env` : `CORS_ALLOW_ORIGIN=http://localhost:5173`
- `backend/config/packages/nelmio_cors.yaml`

### Problèmes de permissions

```bash
# Sur Linux/Mac, ajuster les permissions
sudo chown -R $USER:$USER backend/var
```

### Port déjà utilisé

Modifiez les ports dans le fichier `.env` :
```env
BACKEND_PORT=8001
FRONTEND_PORT=5174
PHPMYADMIN_PORT=8081
```

## 🔒 Production

Pour déployer en production :

1. Utilisez des variables d'environnement sécurisées
2. Changez `APP_ENV=prod` dans `.env`
3. Utilisez le Dockerfile de production pour le frontend :
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```
4. Configurez des certificats SSL
5. Désactivez Xdebug et PHPMyAdmin

## 📝 Variables d'environnement

Voir le fichier `.env.docker` pour toutes les variables configurables.

Variables importantes :
- `MYSQL_ROOT_PASSWORD` : Mot de passe root MySQL
- `MYSQL_DATABASE` : Nom de la base de données
- `APP_SECRET` : Secret Symfony
- `CORS_ALLOW_ORIGIN` : Origine autorisée pour CORS
- `VITE_API_URL` : URL de l'API backend

## 🤝 Partage avec l'équipe

Chaque membre de l'équipe doit :

1. Cloner le repository
2. Copier `.env.docker` vers `.env`
3. Exécuter `docker-compose up -d`
4. Initialiser la base de données

Tout le monde aura ainsi :
- PHP 8.2
- MySQL 8.0
- Node.js 20
- Symfony 7.2
- React 19.2

## 📚 Ressources

- [Documentation Docker](https://docs.docker.com/)
- [Documentation Docker Compose](https://docs.docker.com/compose/)
- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Documentation React](https://react.dev/)
