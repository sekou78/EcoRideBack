#!/bin/bash
CONTAINER=eco_cron

echo "🧹 Suppression du cache Symfony..."
docker exec -it $CONTAINER rm -rf var/cache/*

echo "📦 Installation des dépendances..."
docker exec -it $CONTAINER composer install --no-interaction --optimize-autoloader --no-scripts

echo "🔥 Nettoyage du cache Symfony..."
docker exec -it $CONTAINER php bin/console cache:clear --no-warmup

echo "✅ Vérification Symfony..."
docker exec -it $CONTAINER php bin/console about

# Permet de lancer en mode dev plusieurs commandes en une seule fois
