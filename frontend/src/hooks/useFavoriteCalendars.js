import { useState, useCallback } from 'react'

/**
 * Hook pour gérer les agendas favoris sauvegardés localement
 */
export const useFavoriteCalendars = () => {
  const [favorites, setFavorites] = useState(() => {
    const saved = localStorage.getItem('favorite-calendars')
    return saved ? JSON.parse(saved) : []
  })

  const addFavorite = useCallback((calendarId) => {
    setFavorites(prev => {
      if (prev.includes(calendarId)) return prev
      const updated = [...prev, calendarId]
      localStorage.setItem('favorite-calendars', JSON.stringify(updated))
      return updated
    })
  }, [])

  const removeFavorite = useCallback((calendarId) => {
    setFavorites(prev => {
      const updated = prev.filter(id => id !== calendarId)
      localStorage.setItem('favorite-calendars', JSON.stringify(updated))
      return updated
    })
  }, [])

  const toggleFavorite = useCallback((calendarId) => {
    if (favorites.includes(calendarId)) {
      removeFavorite(calendarId)
    } else {
      addFavorite(calendarId)
    }
  }, [favorites, addFavorite, removeFavorite])

  const isFavorite = useCallback((calendarId) => {
    return favorites.includes(calendarId)
  }, [favorites])

  return {
    favorites,
    addFavorite,
    removeFavorite,
    toggleFavorite,
    isFavorite
  }
}

export default useFavoriteCalendars
