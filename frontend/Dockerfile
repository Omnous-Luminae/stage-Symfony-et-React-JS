# Frontend React Dockerfile
FROM node:20-alpine AS base

# Arguments pour les permissions
ARG USER_ID=1000
ARG GROUP_ID=1000

# Installation des dépendances système
RUN apk add --no-cache git

# Création de l'utilisateur avec les mêmes UID/GID que l'hôte
RUN if ! getent group ${GROUP_ID} > /dev/null 2>&1; then \
        addgroup -g ${GROUP_ID} appgroup 2>/dev/null || true; \
    fi \
    && if ! getent passwd ${USER_ID} > /dev/null 2>&1; then \
        adduser -D -u ${USER_ID} -G appgroup appuser 2>/dev/null || true; \
    fi

# Répertoire de travail
WORKDIR /app

# Copie des fichiers de dépendances
COPY package*.json ./

# Image de développement
FROM base AS development

# Installation des dépendances
RUN npm install

# Copie des fichiers du projet
COPY . .

# Exposition du port Vite
EXPOSE 5173

# Commande par défaut
CMD ["npm", "run", "dev", "--", "--host"]

# Image de production
FROM base AS production

# Installation des dépendances de production uniquement
RUN npm ci --only=production

# Copie des fichiers du projet
COPY . .

# Build de l'application
RUN npm run build

# Exposition du port pour preview
EXPOSE 4173

# Commande pour servir l'application buildée
CMD ["npm", "run", "preview", "--", "--host"]
