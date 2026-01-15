import { useNotification } from '../context/NotificationContext'
import './NotificationCenter.css'

function NotificationCenter() {
  const { notifications, removeNotification } = useNotification()

  return (
    <div className="notification-center">
      {notifications.map((notif) => (
        <div 
          key={notif.id} 
          className={`notification notification-${notif.type}`}
        >
          <div className="notification-content">
            <span className="notification-icon">
              {notif.type === 'success' && '✅'}
              {notif.type === 'error' && '❌'}
              {notif.type === 'warning' && '⚠️'}
              {notif.type === 'info' && 'ℹ️'}
            </span>
            <span className="notification-message">{notif.message}</span>
          </div>
          <button 
            className="notification-close"
            onClick={() => removeNotification(notif.id)}
          >
            ✕
          </button>
        </div>
      ))}
    </div>
  )
}

export default NotificationCenter
