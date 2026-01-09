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
        target: 'http://172.17.0.1:8000',
        changeOrigin: true,
        // ensure proper path pass-through to PHP server
        rewrite: (path) => path,
      },
    },
  },
})
