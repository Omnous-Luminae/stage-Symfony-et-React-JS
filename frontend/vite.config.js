import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    host: true,
    proxy: {
      '/api': {
        // Dans Docker, on utilise le nom du service nginx qui expose le backend
        target: 'http://nginx:80',
        changeOrigin: true,
        // ensure proper path pass-through to PHP server
        rewrite: (path) => path,
      },
    },
  },
})
