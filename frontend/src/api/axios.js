import axios from 'axios'

// Utiliser l'URL relative - le proxy Vite se chargera du routing
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json'
  },
  withCredentials: true  // Important: envoie les cookies de session automatiquement
})

api.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(error)
)

export default api
