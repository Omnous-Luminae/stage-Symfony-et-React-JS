import { useEffect, useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import Layout from '../components/Layout'
import { eventService } from '../api/events'
import { useNotification } from '../context/NotificationContext'
import './EventAlertsPage.css'

function EventAlertsPage() {
  const { eventId } = useParams()
  const navigate = useNavigate()
  const { showSuccess, showError } = useNotification()
  const [event, setEvent] = useState(null)
  const [loading, setLoading] = useState(true)
  const [alerts, setAlerts] = useState([])
  const [newAlert, setNewAlert] = useState('15min')

  const alertOptions = [
    { value: '5min', label: '5 minutes avant', minutesBefore: 5 },
    { value: '15min', label: '15 minutes avant', minutesBefore: 15 },
    { value: '30min', label: '30 minutes avant', minutesBefore: 30 },
    { value: '1h', label: '1 heure avant', minutesBefore: 60 },
    { value: '1d', label: '1 jour avant', minutesBefore: 1440 },
    { value: 'custom', label: 'Personnalisé', minutesBefore: null }
  ]

  useEffect(() => {
    loadEvent()
  }, [eventId])

  const loadEvent = async () => {
    try {
      setLoading(true)
      // Récupérer l'événement
      const response = await eventService.getById(eventId)
      setEvent(response.data)
      // Pour cette implémentation, les alertes sont stockées localement
      const savedAlerts = JSON.parse(localStorage.getItem(`event-alerts-${eventId}`) || '[]')
      setAlerts(savedAlerts)
    } catch (err) {
      showError('Erreur lors du chargement de l\'événement')
      console.error(err)
    } finally {
      setLoading(false)
    }
  }

  const handleAddAlert = () => {
    if (newAlert && !alerts.includes(newAlert)) {
      const updatedAlerts = [...alerts, newAlert]
      setAlerts(updatedAlerts)
      localStorage.setItem(`event-alerts-${eventId}`, JSON.stringify(updatedAlerts))
      showSuccess('Alerte ajoutée')
    }
  }

  const handleRemoveAlert = (alertValue) => {
    const updatedAlerts = alerts.filter(a => a !== alertValue)
    setAlerts(updatedAlerts)
    localStorage.setItem(`event-alerts-${eventId}`, JSON.stringify(updatedAlerts))
    showSuccess('Alerte supprimée')
  }

  const getAlertLabel = (value) => {
    const option = alertOptions.find(opt => opt.value === value)
    return option ? option.label : value
  }

  const formatDateTime = (dateStr) => {
    const date = new Date(dateStr)
    return new Intl.DateTimeFormat('fr-FR', {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date)
  }

  if (loading) {
    return (
      <Layout>
        <div className="event-alerts-page">
          <div className="loading">Chargement...</div>
        </div>
      </Layout>
    )
  }

  if (!event) {
    return (
      <Layout>
        <div className="event-alerts-page">
          <div className="error-message">Événement non trouvé</div>
          <Link to="/events" className="btn-primary">Retour aux événements</Link>
        </div>
      </Layout>
    )
  }

  return (
    <Layout>
      <div className="event-alerts-page">
        <div className="page-header">
          <div>
            <h2>🔔 Alertes pour: {event.title}</h2>
            <p className="event-datetime">{formatDateTime(event.start)}</p>
          </div>
          <Link to={`/event/${eventId}`} className="btn-secondary">
            ← Retour à l'événement
          </Link>
        </div>

        <div className="alerts-container">
          <div className="add-alert-card">
            <h3>Ajouter une alerte</h3>
            <div className="add-alert-form">
              <select 
                value={newAlert} 
                onChange={(e) => setNewAlert(e.target.value)}
                className="alert-select"
              >
                {alertOptions.map(option => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
              <button 
                className="btn-primary"
                onClick={handleAddAlert}
              >
                ➕ Ajouter
              </button>
            </div>
          </div>

          <div className="alerts-list-card">
            <h3>Alertes actives ({alerts.length})</h3>
            {alerts.length === 0 ? (
              <p className="empty-alerts">Aucune alerte configurée</p>
            ) : (
              <div className="alerts-list">
                {alerts.map((alert) => (
                  <div key={alert} className="alert-item">
                    <div className="alert-info">
                      <span className="alert-bell">🔔</span>
                      <span className="alert-label">{getAlertLabel(alert)}</span>
                    </div>
                    <button 
                      className="btn-remove"
                      onClick={() => handleRemoveAlert(alert)}
                    >
                      ✕
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="alert-info-card">
            <h4>ℹ️ Informations sur les alertes</h4>
            <ul>
              <li>Les alertes vous notifieront avant l'événement</li>
              <li>Vous pouvez configurer plusieurs alertes pour le même événement</li>
              <li>Les alertes sont sauvegardées localement</li>
              <li>Vous serez averti dans les notifications de l'application</li>
            </ul>
          </div>

          <div className="coming-soon-card">
            <h4>🚀 Fonctionnalités à venir</h4>
            <ul>
              <li>Notifications par email</li>
              <li>Notifications push sur mobile</li>
              <li>Alertes personnalisées par jour/heure</li>
              <li>Synchronisation avec les calendriers externes</li>
            </ul>
          </div>
        </div>
      </div>
    </Layout>
  )
}

export default EventAlertsPage
