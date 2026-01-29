import { useState, useEffect, useCallback } from 'react'

const DEFAULT_PREFERENCES = {
  notifications: true,
  darkMode: false,
  showPastEvents: true,
  defaultView: 'month',
  weekStartsOn: 'monday'
}

const STORAGE_KEY = 'userPreferences'

export function usePreferences() {
  const [preferences, setPreferences] = useState(() => {
    // Initialisation avec les valeurs du localStorage
    try {
      const saved = localStorage.getItem(STORAGE_KEY)
      if (saved) {
        return { ...DEFAULT_PREFERENCES, ...JSON.parse(saved) }
      }
    } catch (e) {
      console.error('Erreur lecture préférences:', e)
    }
    return DEFAULT_PREFERENCES
  })

  // Appliquer le mode sombre au chargement
  useEffect(() => {
    if (preferences.darkMode) {
      document.body.classList.add('dark-mode')
    } else {
      document.body.classList.remove('dark-mode')
    }
  }, [preferences.darkMode])

  // Sauvegarder les préférences
  const updatePreference = useCallback((key, value) => {
    setPreferences(prev => {
      const newPrefs = { ...prev, [key]: value }
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(newPrefs))
      } catch (e) {
        console.error('Erreur sauvegarde préférences:', e)
      }
      return newPrefs
    })
  }, [])

  // Réinitialiser aux valeurs par défaut
  const resetPreferences = useCallback(() => {
    setPreferences(DEFAULT_PREFERENCES)
    localStorage.setItem(STORAGE_KEY, JSON.stringify(DEFAULT_PREFERENCES))
    document.body.classList.remove('dark-mode')
  }, [])

  return {
    preferences,
    updatePreference,
    resetPreferences
  }
}

export default usePreferences
