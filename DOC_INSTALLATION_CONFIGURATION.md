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

### Méthode 1 : Avec Scoop (recommandé si disponible)

Vérifiez si Scoop est installé :
```powershell
scoop --version
```

Si Scoop est installé, installez Symfony CLI simplement :
```powershell
scoop install symfony-cli
symfony version
```

Si Scoop n'est pas installé, vous pouvez l'installer avec :
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
Invoke-RestMethod -Uri https://get.scoop.sh | Invoke-Expression
scoop install symfony-cli
```

### Méthode 2 : Installation manuelle (si Scoop n'est pas disponible)

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

## Installation de React.js (Frontend)

### Méthode 1 : Avec Vite (recommandé)

```powershell
# Créer un nouveau projet React avec Vite
npm create vite@latest frontend -- --template react
cd frontend
npm install
npm run dev
```

### Méthode 2 : Avec Create React App

```powershell
npx create-react-app frontend
cd frontend
npm start
```

### Installation de Node.js et npm (si nécessaire)

Vérifiez si Node.js est installé :
```powershell
node --version
npm --version
```

Si non installé :

**Avec Scoop (recommandé) :**
```powershell
scoop install nodejs
```

**Ou téléchargez depuis :** https://nodejs.org/

### Intégration React avec Symfony

Pour utiliser React comme frontend avec Symfony comme backend API :

```powershell
# Dans le projet Symfony
composer require symfony/webpack-encore-bundle
npm install

# Configuration de Webpack Encore
npm install --save-dev @babel/preset-react
npm install react react-dom

# Build des assets
npm run dev      # Mode développement
npm run watch    # Mode watch
npm run build    # Mode production
```

## Configuration Git

### Pour tous les utilisateurs

1. **Vérifier que Git est installé :**
```powershell
git --version
```

Si non installé, installer avec Scoop :
```powershell
scoop install git
```

2. **Configuration initiale de Git :**
```powershell
git config --global user.name "Votre Nom"
git config --global user.email "votre.email@example.com"
```

3. **Vérifier la configuration :**
```powershell
git config --global user.name
git config --global user.email
```

### Cas 1 : Cloner un projet existant (utilisateur standard)

```powershell
# Cloner le repository
git clone https://github.com/Omnous-Luminae/stage-Symfony-et-React-JS.git
cd stage-Symfony-et-React-JS

# Voir toutes les branches disponibles
git branch -a

# Créer une branche locale pour dev
git checkout -b dev origin/dev

# Travailler sur votre branche
git checkout -b feature/ma-fonctionnalite

# Faire des commits
git add .
git commit -m "Description de vos modifications"

# Pousser votre branche
git push -u origin feature/ma-fonctionnalite
```

**Important pour les utilisateurs non-admin :**
- ❌ Vous ne pouvez **PAS** push directement sur `main`
- ✅ Créez toujours une nouvelle branche pour vos modifications
- ✅ Créez une **Pull Request** sur GitHub pour merger vers `main`
- ✅ L'admin approuvera et mergera vos changements

### Cas 2 : Configuration pour Admin

```powershell
# Cloner ou initialiser le repository
git clone https://github.com/Omnous-Luminae/stage-Symfony-et-React-JS.git
cd stage-Symfony-et-React-JS

# Récupérer toutes les branches
git fetch origin

# Créer les branches locales
git checkout -b dev origin/dev
git checkout main

# Voir toutes les branches
git branch -a
```

**Privilèges admin :**
- ✅ Vous pouvez push directement sur `main`
- ✅ Vous pouvez merger les Pull Requests
- ✅ Vous pouvez créer/supprimer des branches protégées
- ✅ Les rulesets GitHub vous autorisent en tant qu'admin

**Authentification GitHub (obligatoire pour push) :**

Lors du premier push, vous serez invité à vous authentifier. Utilisez un **Personal Access Token** :

1. Allez sur GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token (classic)
3. Cochez les permissions :
   - `repo` (accès complet aux repos)
   - `workflow` (si vous utilisez GitHub Actions)
4. Copiez le token généré
5. Utilisez-le comme mot de passe lors du push

Windows stockera automatiquement vos credentials via **Git Credential Manager**.

### Configuration VS Code pour Git

Créez `.vscode/settings.json` dans votre projet :

```json
{
  "git.enabled": true,
  "git.autofetch": true,
  "git.confirmSync": false,
  "git.enableSmartCommit": true,
  "git.postCommitCommand": "none",
  "git.showPushSuccessNotification": true
}
```

**Faire des commits depuis VS Code :**

1. **Ouvrir Source Control** (Ctrl+Shift+G)
2. Voir les fichiers modifiés
3. Cliquer sur `+` pour ajouter les fichiers (stage)
4. Taper votre message de commit
5. Cliquer sur ✓ (commit)
6. Cliquer sur `Sync Changes` ou `Push` pour envoyer vers GitHub

**Changer de branche dans VS Code :**
- Cliquez en bas à gauche sur le nom de la branche actuelle
- Sélectionnez la branche souhaitée dans la liste

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

### Erreur "unable to detect the front controller"

Si vous obtenez cette erreur en démarrant le serveur :
```
unable to detect the front controller, disabling the PHP server
error="Passthru script \"/index.php\" does not exist
```

**Cause :** Vous n'êtes pas dans un projet Symfony.

**Solution :** Créez d'abord un projet Symfony :
```powershell
# Application web complète
symfony new backend --version=7.2 --webapp

# Puis démarrez le serveur dans le bon dossier
cd backend
symfony server:start --no-tls
```

### Warnings "Module XXX is already loaded"

Si vous voyez ces warnings PHP :
```
PHP Warning: Module "bz2" is already loaded
PHP Warning: Module "curl" is already loaded
...
```

**Cause :** Extensions chargées en double dans `php.ini`.

**Solution (optionnelle) :** Éditez `C:\xampp\php\php.ini` et commentez les duplicats :
```powershell
# Ouvrir php.ini avec notepad
notepad C:\xampp\php\php.ini

# Chercher les lignes dupliquées comme :
# extension=curl
# extension=curl
# 
# Et commentez une des deux avec un point-virgule :
# extension=curl
# ;extension=curl
```

**Note :** Ces warnings n'empêchent pas Symfony de fonctionner.

### Erreur SSL "unable to get local issuer certificate"

Si vous voyez des erreurs SSL lors de l'installation :
```
SSL certificate problem: unable to get local issuer certificate
```

**Solutions :**

1. **Télécharger le certificat CA (recommandé) :**
```powershell
# Télécharger le bundle CA
Invoke-WebRequest -Uri https://curl.se/ca/cacert.pem -OutFile C:\xampp\php\cacert.pem

# Puis éditer php.ini
notepad C:\xampp\php\php.ini

# Ajouter cette ligne (sans le ;) :
# curl.cainfo = "C:\xampp\php\cacert.pem"
# openssl.cafile = "C:\xampp\php\cacert.pem"
```

2. **Ou démarrer le serveur avec --no-tls :**
```powershell
symfony server:start --no-tls
```

### Port 8000 déjà utilisé

Spécifiez un autre port :
```powershell
symfony server:start --port=8001
```

### Problèmes avec npm ou Node.js

Vérifiez que Node.js est dans le PATH :
```powershell
node --version
npm --version
```

Si non reconnu après installation, rechargez le PATH ou redémarrez le terminal.

### Erreur "refusing to merge unrelated histories"

Si vous avez initialisé un repo local et voulez le connecter à un repo existant :
```powershell
git pull origin main --allow-unrelated-histories
```

### Push refusé sur la branche main (utilisateur non-admin)

**Erreur typique :**
```
! [remote rejected] main -> main (protected branch hook declined)
```

**Solution :**
- Ne travaillez jamais directement sur `main`
- Créez une branche de fonctionnalité
- Créez une Pull Request sur GitHub
- Attendez l'approbation de l'admin

```powershell
# Créer une nouvelle branche
git checkout -b feature/ma-fonctionnalite

# Faire vos modifications et commit
git add .
git commit -m "Ajout de ma fonctionnalité"

# Push vers votre branche
git push -u origin feature/ma-fonctionnalite
```

Ensuite, allez sur GitHub et créez une Pull Request.

### Authentification GitHub échoue

Si vous avez des problèmes d'authentification :

1. **Vérifier le credential helper :**
```powershell
git config credential.helper
```

2. **Supprimer les anciennes credentials :**
   - Panneau de configuration → Gestionnaire d'identification
   - Supprimer les credentials GitHub
   - Recommencer le push

3. **Utiliser un Personal Access Token :**
   - Créez un token sur GitHub (Settings → Developer settings → Personal access tokens)
   - Utilisez le token comme mot de passe lors du push

## Workflow de développement recommandé

### Pour les utilisateurs non-admin

1. **Cloner le projet**
```powershell
git clone https://github.com/Omnous-Luminae/stage-Symfony-et-React-JS.git
cd stage-Symfony-et-React-JS
```

2. **Créer une branche de travail**
```powershell
git checkout dev
git checkout -b feature/nom-de-ma-fonctionnalite
```

3. **Développer et tester**
```powershell
# Backend Symfony
symfony server:start

# Frontend React (dans un autre terminal)
cd frontend
npm run dev
```

4. **Commit et push**
```powershell
git add .
git commit -m "Description claire des modifications"
git push -u origin feature/nom-de-ma-fonctionnalite
```

5. **Créer une Pull Request sur GitHub**
   - Allez sur le repo GitHub
   - Cliquez sur "Compare & pull request"
   - Décrivez vos modifications
   - Assignez l'admin comme reviewer

### Pour l'admin

1. **Review des Pull Requests**
   - Vérifier le code
   - Tester les modifications
   - Approuver ou demander des changements

2. **Merge vers main**
```powershell
git checkout main
git pull origin main
git merge --no-ff feature/nom-de-fonctionnalite
git push origin main
```

3. **Ou utiliser l'interface GitHub** (recommandé)
   - Cliquer sur "Merge pull request"
   - Choisir le type de merge (squash, rebase, ou merge commit)

## Ressources

- **Symfony :**
  - Documentation officielle : https://symfony.com/doc/current/index.html
  - Symfony CLI : https://symfony.com/download
  - Best practices : https://symfony.com/doc/current/best_practices.html
  - Communauté : https://symfony.com/community

- **React :**
  - Documentation React : https://react.dev/
  - Vite : https://vitejs.dev/
  - Create React App : https://create-react-app.dev/

- **Git :**
  - Documentation Git : https://git-scm.com/doc
  - GitHub Docs : https://docs.github.com/
  - Git Credential Manager : https://github.com/git-ecosystem/git-credential-manager

- **Outils Windows :**
  - Scoop : https://scoop.sh/
  - Node.js : https://nodejs.org/
  - Composer : https://getcomposer.org/

## Configuration système recommandée

### Versions installées (Janvier 2026)
- PHP : 8.2.12
- Composer : 2.8.8
- Symfony CLI : 5.16.1
- Git : 2.49.0
- Node.js : 20.x ou supérieur (recommandé)
- npm : 10.x ou supérieur

### Structure de projet recommandée

```
stage-Symfony-et-React-JS/
├── backend/              # Projet Symfony
│   ├── src/
│   ├── config/
│   ├── public/
│   └── composer.json
├── frontend/             # Projet React
│   ├── src/
│   ├── public/
│   └── package.json
├── .gitignore
├── README.md
└── INSTALLATION_SYMFONY.md
```

## Checklist d'installation

- [ ] PHP 8.2+ installé et configuré
- [ ] Composer installé
- [ ] Symfony CLI installé (via Scoop ou manuellement)
- [ ] Node.js et npm installés
- [ ] Git installé et configuré
- [ ] Compte GitHub avec accès au repo
- [ ] Personal Access Token créé pour l'authentification
- [ ] VS Code configuré pour Git
- [ ] Branches locales créées (main, dev)
- [ ] Serveur Symfony démarre correctement
- [ ] Application React démarre correctement
- [ ] Commits et push fonctionnent

## Notes importantes

1. **Toujours travailler sur une branche** (sauf si vous êtes admin)
2. **Faire des commits fréquents** avec des messages clairs
3. **Tester localement** avant de push
4. **Utiliser les Pull Requests** pour la review de code
5. **Garder vos branches à jour** avec `git pull origin main`
6. **Ne jamais commit les fichiers sensibles** (.env, credentials, etc.)
7. **Utiliser .gitignore** pour exclure les dossiers node_modules, vendor, var/cache

---

**Date de création :** Janvier 2026  
**Auteur :** Configuration pour le projet stage-Symfony-et-React-JS  
**Repository :** https://github.com/Omnous-Luminae/stage-Symfony-et-React-JS
