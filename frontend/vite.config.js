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
        // Le backend Symfony tourne dans le même conteneur : on pointe sur localhost
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        // ensure proper path pass-through to PHP server
        rewrite: (path) => path,
      },
    },
  },
})
