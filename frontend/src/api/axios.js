import axios from 'axios'

// Default to "/api" so Vite dev proxy catches requests; override via VITE_API_BASE_URL for prod
const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'

const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json'
  },
  withCredentials: true // nécessaire si on utilise des cookies HttpOnly
})

// Intercepteur pour gérer les erreurs (la gestion du 401 est faite dans AuthContext)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Ne pas rediriger ici, laisser AuthContext gérer le flux de navigation
    return Promise.reject(error)
  }
)

export default api
