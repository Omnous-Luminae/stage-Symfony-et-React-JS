import { createContext, useContext, useState, useEffect } from 'react'

const CalendarContext = createContext()

export function CalendarProvider({ children }) {
  const [activeCalendar, setActiveCalendar] = useState(null)

  // Charger l'agenda actif depuis localStorage au démarrage
  useEffect(() => {
    const savedCalendar = localStorage.getItem('activeCalendar')
    if (savedCalendar) {
      try {
        setActiveCalendar(JSON.parse(savedCalendar))
      } catch (e) {
        console.error('Erreur au chargement du calendrier:', e)
      }
    }
  }, [])

  // Sauvegarder l'agenda actif dans localStorage
  const selectCalendar = (calendar) => {
    setActiveCalendar(calendar)
    if (calendar) {
      localStorage.setItem('activeCalendar', JSON.stringify(calendar))
    } else {
      localStorage.removeItem('activeCalendar')
    }
  }

  const clearActiveCalendar = () => {
    setActiveCalendar(null)
    localStorage.removeItem('activeCalendar')
  }

  return (
    <CalendarContext.Provider value={{ activeCalendar, selectCalendar, clearActiveCalendar }}>
      {children}
    </CalendarContext.Provider>
  )
}

export function useCalendar() {
  const context = useContext(CalendarContext)
  if (!context) {
    throw new Error('useCalendar doit être utilisé dans un CalendarProvider')
  }
  return context
}
