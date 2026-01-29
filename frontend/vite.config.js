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
        changeOrigin: true,
        // Préserver les cookies de session PHP
        cookieDomainRewrite: '',
        // Sécurise la transmission des cookies
        secure: false,
        // ensure proper path pass-through to PHP server
        rewrite: (path) => path,
      },
    },
  },
})
