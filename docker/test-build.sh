#!/bin/bash
# Script pour tester le build Docker localement

set -e

echo "🐳 Docker Build Test Script"
echo "============================"
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

info() {
    echo "ℹ️  $1"
}

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    error "Docker n'est pas installé. Installer Docker Desktop: https://www.docker.com/products/docker-desktop"
fi

success "Docker est installé"

# Vérifier que Docker est en cours d'exécution
if ! docker info &> /dev/null; then
    error "Docker n'est pas en cours d'exécution. Démarrer Docker Desktop"
fi

success "Docker est en cours d'exécution"

# Construire l'image
info "Construction de l'image Docker..."
if docker build -t interventio-api:test . ; then
    success "Image construite avec succès"
else
    error "Échec de la construction de l'image"
fi

# Afficher la taille de l'image
IMAGE_SIZE=$(docker images interventio-api:test --format "{{.Size}}")
info "Taille de l'image: $IMAGE_SIZE"

# Vérifier la structure de l'image
info "Vérification de la structure de l'image..."
docker run --rm interventio-api:test ls -la /var/www/html > /dev/null 2>&1 && success "Structure OK" || warning "Structure non vérifiable"

# Proposer de démarrer le container
echo ""
read -p "Voulez-vous démarrer le container pour tester? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    info "Démarrage du container sur le port 8080..."
    
    # Variables d'environnement minimales
    docker run -d \
        --name interventio-api-test \
        -p 8080:8080 \
        -e APP_ENV=local \
        -e APP_DEBUG=true \
        -e APP_KEY=base64:$(openssl rand -base64 32) \
        -e DB_CONNECTION=sqlite \
        -e DB_DATABASE=/tmp/database.sqlite \
        interventio-api:test
    
    success "Container démarré (ID: interventio-api-test)"
    
    # Attendre que le container soit prêt
    info "Attente du démarrage de l'application..."
    sleep 10
    
    # Tester le health check
    if curl -f http://localhost:8080/health > /dev/null 2>&1; then
        success "Health check OK: http://localhost:8080/health"
    else
        warning "Health check échoué"
    fi
    
    # Afficher les logs
    echo ""
    info "Dernières lignes des logs:"
    docker logs --tail 20 interventio-api-test
    
    # Informations
    echo ""
    success "Container en cours d'exécution!"
    info "URL: http://localhost:8080"
    info "Health: http://localhost:8080/health"
    info "Swagger: http://localhost:8080/api/documentation"
    info "Logs: docker logs -f interventio-api-test"
    info "Stop: docker stop interventio-api-test"
    info "Remove: docker rm interventio-api-test"
    
    # Proposer d'arrêter
    echo ""
    read -p "Voulez-vous arrêter le container maintenant? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker stop interventio-api-test
        docker rm interventio-api-test
        success "Container arrêté et supprimé"
    fi
fi

echo ""
success "Test terminé!"
info "Pour nettoyer l'image: docker rmi interventio-api:test"

