# Guide d'installation Symfony sur Windows

## Prérequis

Avant d'installer Symfony, assurez-vous d'avoir :
- PHP 8.1 ou supérieur
- Composer (gestionnaire de dépendances PHP)
- Git (optionnel mais recommandé)

## Vérification des prérequis

Ouvrez PowerShell et vérifiez les versions installées :

```powershell
php --version
composer --version
```

## Installation de Symfony CLI

### Téléchargement et installation

1. Téléchargez Symfony CLI depuis GitHub :
```powershell
Invoke-WebRequest -Uri https://github.com/symfony-cli/symfony-cli/releases/latest/download/symfony-cli_windows_amd64.zip -OutFile symfony.zip
```

2. Extrayez et ajoutez au PATH :
```powershell
Expand-Archive -Path symfony.zip -DestinationPath $env:USERPROFILE\.symfony -Force
$env:Path += ";$env:USERPROFILE\.symfony"
[Environment]::SetEnvironmentVariable("Path", $env:Path, [EnvironmentVariableTarget]::User)
```

3. Rechargez les variables d'environnement dans le terminal actuel :
```powershell
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","User") + ";" + [System.Environment]::GetEnvironmentVariable("Path","Machine")
```

4. Vérifiez l'installation :
```powershell
symfony version
```

## Vérification de la configuration système

Vérifiez que votre système est prêt pour Symfony :

```powershell
symfony check:requirements
```

## Créer un nouveau projet Symfony

### Application web complète (avec Twig, Doctrine, etc.)

```powershell
symfony new mon_projet --version=7.2 --webapp
cd mon_projet
```

### Microservice ou API (installation minimale)

```powershell
symfony new mon_projet --version=7.2
cd mon_projet
```

### Avec Composer (alternative)

```powershell
# Application web complète
composer create-project symfony/skeleton:"7.2.*" mon_projet
cd mon_projet
composer require webapp

# OU microservice/API
composer create-project symfony/skeleton:"7.2.*" mon_projet
```

## Démarrer le serveur de développement

```powershell
cd mon_projet
symfony server:start
```

Accédez à votre application sur : `http://127.0.0.1:8000`

Pour arrêter le serveur :
```powershell
symfony server:stop
```

## Commandes utiles

### Symfony CLI

```powershell
# Afficher toutes les commandes disponibles
symfony help

# Vérifier les vulnérabilités de sécurité
symfony security:check

# Démarrer le serveur en arrière-plan
symfony server:start -d

# Voir le statut du serveur
symfony server:status

# Afficher les logs du serveur
symfony server:log
```

### Console Symfony

```powershell
# Liste des commandes Symfony
php bin/console

# Créer un contrôleur
php bin/console make:controller

# Créer une entité
php bin/console make:entity

# Créer/mettre à jour la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear
```

## Recommandations optionnelles

### Extensions PHP recommandées

Activez ces extensions dans `php.ini` (C:\xampp\php\php.ini) :

```ini
extension=intl
extension=pdo_mysql
extension=pdo_pgsql
extension=mbstring
extension=curl
extension=openssl
```

### Optimisation des performances

Ajoutez dans `php.ini` :

```ini
realpath_cache_size = 5M
post_max_size = 20M
upload_max_filesize = 20M
```

Après modification, redémarrez Apache via XAMPP.

## Dépannage

### La commande `symfony` n'est pas reconnue

Redémarrez votre terminal ou rechargez le PATH :
```powershell
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","User") + ";" + [System.Environment]::GetEnvironmentVariable("Path","Machine")
```

### Erreurs de droits d'exécution

Autorisez l'exécution de scripts :
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force
```

### Port 8000 déjà utilisé

Spécifiez un autre port :
```powershell
symfony server:start --port=8001
```

## Ressources

- Documentation officielle : https://symfony.com/doc/current/index.html
- Symfony CLI : https://symfony.com/download
- Best practices : https://symfony.com/doc/current/best_practices.html
- Communauté : https://symfony.com/community

## Version installée

- PHP : 8.2.12
- Composer : 2.8.8
- Symfony CLI : 5.16.1
- Date d'installation : Janvier 2026
