import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    host: '0.0.0.0',
    proxy: {
      '/api': {
        // Dans Docker, on utilise le nom du service backend du docker-compose
        target: 'http://backend:80',
        // On réécrit le domaine des cookies vers localhost pour que le navigateur les garde
        cookieDomainRewrite: 'localhost',
        changeOrigin: true,
        // ensure proper path pass-through to PHP server
        rewrite: (path) => path,
      },
    },
  },
})
