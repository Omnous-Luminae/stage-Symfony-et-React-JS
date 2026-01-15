import { useEffect, useCallback } from 'react'
import { useNotification } from '../context/NotificationContext'

/**
 * Hook pour vérifier et afficher les alertes d'événements
 * Vérifie les événements qui arrivent bientôt et affiche des notifications
 */
export const useEventAlerts = (events) => {
  const { showInfo, showWarning } = useNotification()

  const checkAlerts = useCallback(() => {
    if (!events || events.length === 0) return

    const now = new Date()

    events.forEach(event => {
      const eventStart = new Date(event.start)
      const alertKey = `alert-shown-${event.id}`
      const alertShown = localStorage.getItem(alertKey)

      // Vérifier les alertes sauvegardées pour cet événement
      const savedAlerts = JSON.parse(localStorage.getItem(`event-alerts-${event.id}`) || '[]')

      savedAlerts.forEach(alertValue => {
        const alertOption = {
          '5min': 5,
          '15min': 15,
          '30min': 30,
          '1h': 60,
          '1d': 1440
        }

        const minutesBefore = alertOption[alertValue]
        if (!minutesBefore) return

        const alertTime = new Date(eventStart.getTime() - minutesBefore * 60000)

        // Montrer l'alerte si on est dans la 2 minutes avant l'heure prévue
        if (now >= alertTime && now < new Date(alertTime.getTime() + 120000)) {
          if (!alertShown) {
            showWarning(`⏰ Rappel: ${event.title} dans ${minutesBefore} minutes`)
            localStorage.setItem(alertKey, 'true')
          }
        }
      })
    })
  }, [events, showWarning])

  useEffect(() => {
    // Vérifier les alertes tous les 30 secondes
    const interval = setInterval(checkAlerts, 30000)
    // Vérifier immédiatement
    checkAlerts()

    return () => clearInterval(interval)
  }, [checkAlerts])
}

export default useEventAlerts
