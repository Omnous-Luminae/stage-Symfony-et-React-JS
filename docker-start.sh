#!/bin/bash
# Script de démarrage Docker pour le projet Stage (Linux/Mac)
# Usage: ./docker-start.sh

echo "🐳 Démarrage de l'environnement Docker..."
echo ""

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé"
    exit 1
fi

# Vérifier que Docker Compose est installé
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé"
    exit 1
fi

# Créer le fichier .env s'il n'existe pas
if [ ! -f ".env" ]; then
    echo "📝 Création du fichier .env depuis .env.docker..."
    cp .env.docker .env
fi

# Démarrer les conteneurs
echo "▶️  Démarrage des conteneurs..."
docker-compose up -d --build

# Attendre que MySQL soit prêt
echo "⏳ Attente du démarrage de MySQL..."
sleep 10

# Synchroniser les métadonnées des migrations
echo "🔄 Synchronisation des migrations..."
docker-compose exec -T backend php bin/console doctrine:migrations:sync-metadata-storage

echo ""
echo "✅ Environnement Docker démarré avec succès !"
echo ""
echo "📡 Services disponibles:"
echo "   • Frontend React:  http://localhost:5173"
echo "   • Backend Symfony: http://localhost:8000"
echo "   • PHPMyAdmin:      http://localhost:8080"
echo "   • MySQL:           localhost:3306"
echo ""
echo "📚 Commandes utiles:"
echo '   • Logs:            docker-compose logs -f'
echo '   • Backend logs:    docker-compose logs -f backend'
echo '   • Frontend logs:   docker-compose logs -f frontend'
echo '   • Arrêter:         docker-compose down'
echo '   • Arrêter (avec données): docker-compose down -v'
echo ""
