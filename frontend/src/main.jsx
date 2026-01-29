import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

// Charger les préférences au démarrage (notamment le mode sombre)
try {
  const savedPrefs = localStorage.getItem('userPreferences')
  if (savedPrefs) {
    const prefs = JSON.parse(savedPrefs)
    if (prefs.darkMode) {
      document.body.classList.add('dark-mode')
    }
  }
} catch (e) {
  console.error('Erreur chargement préférences:', e)
}

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
