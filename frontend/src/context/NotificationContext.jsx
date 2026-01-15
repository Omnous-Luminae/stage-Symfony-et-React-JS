import { createContext, useContext, useState, useCallback } from 'react'

const NotificationContext = createContext()

export const NotificationProvider = ({ children }) => {
  const [notifications, setNotifications] = useState([])

  const addNotification = useCallback((message, type = 'info', duration = 5000) => {
    const id = Date.now()
    const notification = { id, message, type, timestamp: new Date() }
    
    setNotifications(prev => [...prev, notification])

    if (duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, duration)
    }

    return id
  }, [])

  const removeNotification = useCallback((id) => {
    setNotifications(prev => prev.filter(notif => notif.id !== id))
  }, [])

  const showSuccess = useCallback((message) => {
    return addNotification(message, 'success', 4000)
  }, [addNotification])

  const showError = useCallback((message) => {
    return addNotification(message, 'error', 5000)
  }, [addNotification])

  const showWarning = useCallback((message) => {
    return addNotification(message, 'warning', 5000)
  }, [addNotification])

  const showInfo = useCallback((message) => {
    return addNotification(message, 'info', 4000)
  }, [addNotification])

  return (
    <NotificationContext.Provider value={{
      notifications,
      addNotification,
      removeNotification,
      showSuccess,
      showError,
      showWarning,
      showInfo
    }}>
      {children}
    </NotificationContext.Provider>
  )
}

export const useNotification = () => {
  const context = useContext(NotificationContext)
  if (!context) {
    throw new Error('useNotification doit être utilisé dans NotificationProvider')
  }
  return context
}
