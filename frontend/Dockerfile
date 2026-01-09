# Dockerfile pour le frontend React avec Vite
FROM node:20-alpine AS development

# Installation des dépendances de build
RUN apk add --no-cache git

# Configuration du répertoire de travail
WORKDIR /app

# Copie des fichiers de dépendances
COPY package.json package-lock.json* ./

# Installation des dépendances
RUN npm ci

# Copie du code source
COPY . .

# Exposition du port Vite
EXPOSE 5173

# Commande de développement
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# Stage de production
FROM node:20-alpine AS build

WORKDIR /app

# Copie des fichiers de dépendances
COPY package.json package-lock.json* ./

# Installation des dépendances
RUN npm ci

# Copie du code source
COPY . .

# Build de l'application
RUN npm run build

# Stage de production avec Nginx
FROM nginx:alpine AS production

# Copie de la configuration Nginx personnalisée
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copie des fichiers buildés
COPY --from=build /app/dist /usr/share/nginx/html

# Exposition du port
EXPOSE 80

# Commande par défaut
CMD ["nginx", "-g", "daemon off;"]
