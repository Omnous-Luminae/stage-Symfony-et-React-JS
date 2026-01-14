# Script de démarrage Docker pour le projet Stage
# Usage: .\docker-start.ps1

Write-Host "🐳 Démarrage de l'environnement Docker..." -ForegroundColor Green
Write-Host ""

# Vérifier que Docker est installé
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Docker n'est pas installé ou non disponible dans le PATH" -ForegroundColor Red
    exit 1
}

# Vérifier que Docker Compose est installé
if (-not (docker-compose --version 2>&1)) {
    Write-Host "❌ Docker Compose n'est pas installé" -ForegroundColor Red
    exit 1
}

# Créer le fichier .env s'il n'existe pas
if (-not (Test-Path ".env")) {
    Write-Host "📝 Création du fichier .env depuis .env.docker..." -ForegroundColor Yellow
    Copy-Item .env.docker .env
}

# Démarrer les conteneurs
Write-Host "▶️  Démarrage des conteneurs..." -ForegroundColor Cyan
docker-compose up -d --build

# Attendre que MySQL soit prêt
Write-Host "⏳ Attente du démarrage de MySQL..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Synchroniser les métadonnées des migrations
Write-Host "🔄 Synchronisation des migrations..." -ForegroundColor Cyan
docker-compose exec -T backend php bin/console doctrine:migrations:sync-metadata-storage

Write-Host ""
Write-Host "✅ Environnement Docker démarré avec succès !" -ForegroundColor Green
Write-Host ""
Write-Host "📡 Services disponibles:" -ForegroundColor Green
Write-Host "   • Frontend React:  http://localhost:5173" -ForegroundColor White
Write-Host "   • Backend Symfony: http://localhost:8000" -ForegroundColor White
Write-Host "   • PHPMyAdmin:      http://localhost:8080" -ForegroundColor White
Write-Host "   • MySQL:           localhost:3306" -ForegroundColor White
Write-Host ""
Write-Host "📚 Commandes utiles:" -ForegroundColor Green
Write-Host '   • Logs:            docker-compose logs -f' -ForegroundColor White
Write-Host '   • Backend logs:    docker-compose logs -f backend' -ForegroundColor White
Write-Host '   • Frontend logs:   docker-compose logs -f frontend' -ForegroundColor White
Write-Host '   • Arrêter:         docker-compose down' -ForegroundColor White
Write-Host '   • Arrêter (avec données): docker-compose down -v' -ForegroundColor White
Write-Host ""
