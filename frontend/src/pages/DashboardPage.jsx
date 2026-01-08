import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import Layout from '../components/Layout'
import { eventService } from '../api/events'
import './DashboardPage.css'

function DashboardPage() {
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [todayEvents, setTodayEvents] = useState([])
  const [upcomingEvents, setUpcomingEvents] = useState([])
  const [weekStats, setWeekStats] = useState([])

  useEffect(() => {
    loadDashboardData()
  }, [])

  const loadDashboardData = async () => {
    try {
      setLoading(true)
      const response = await eventService.getAll()
      const allEvents = response.data

      // Événements d'aujourd'hui
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)

      const todayEvts = allEvents.filter(evt => {
        const evtDate = new Date(evt.start)
        return evtDate >= today && evtDate < tomorrow
      })

      // Événements à venir (prochains 7 jours)
      const nextWeek = new Date(today)
      nextWeek.setDate(nextWeek.getDate() + 7)

      const upcoming = allEvents
        .filter(evt => {
          const evtDate = new Date(evt.start)
          return evtDate >= tomorrow && evtDate < nextWeek
        })
        .sort((a, b) => new Date(a.start) - new Date(b.start))
        .slice(0, 5)

      // Statistiques de la semaine
      const stats = []
      for (let i = 0; i < 5; i++) {
        const day = new Date(today)
        day.setDate(day.getDate() + i)
        const dayEnd = new Date(day)
        dayEnd.setDate(dayEnd.getDate() + 1)

        const dayEvents = allEvents.filter(evt => {
          const evtDate = new Date(evt.start)
          return evtDate >= day && evtDate < dayEnd
        })

        stats.push({
          date: day,
          count: dayEvents.length,
          events: dayEvents
        })
      }

      setEvents(allEvents)
      setTodayEvents(todayEvts)
      setUpcomingEvents(upcoming)
      setWeekStats(stats)
      setLoading(false)
    } catch (err) {
      console.error('Erreur chargement dashboard:', err)
      setLoading(false)
    }
  }

  const formatTime = (dateStr) => {
    return new Date(dateStr).toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  const formatDate = (date) => {
    return date.toLocaleDateString('fr-FR', {
      weekday: 'short',
      day: '2-digit'
    })
  }

  const getEventTypeIcon = (type) => {
    const icons = {
      course: '📚',
      meeting: '👥',
      exam: '📝',
      training: '🎓',
      other: '📌'
    }
    return icons[type] || '📌'
  }

  if (loading) {
    return (
      <Layout>
        <div className="loading">Chargement du tableau de bord...</div>
      </Layout>
    )
  }

  return (
    <Layout>
      <div className="dashboard">
        <h2 className="dashboard-title">📊 Tableau de Bord</h2>

        <div className="dashboard-grid">
          {/* Événements d'aujourd'hui */}
          <div className="dashboard-card">
            <div className="card-header">
              <h3>📅 Événements Aujourd'hui</h3>
            </div>
            <div className="card-body">
              {todayEvents.length === 0 ? (
                <p className="no-events">Aucun événement aujourd'hui</p>
              ) : (
                <div className="event-list">
                  {todayEvents.map(event => (
                    <div key={event.id} className="event-item">
                      <span className="event-icon">{getEventTypeIcon(event.extendedProps?.type)}</span>
                      <div className="event-info">
                        <div className="event-time">{formatTime(event.start)} - {formatTime(event.end)}</div>
                        <div className="event-title">{event.title}</div>
                        {event.extendedProps?.location && (
                          <div className="event-location">📍 {event.extendedProps.location}</div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
              <Link to="/calendar" className="card-link">Voir tout ({events.length})</Link>
            </div>
          </div>

          {/* Mes Agendas */}
          <div className="dashboard-card">
            <div className="card-header">
              <h3>📚 Mes Agendas</h3>
            </div>
            <div className="card-body">
              <div className="agenda-list">
                <div className="agenda-item">
                  <span className="agenda-color" style={{ background: '#667eea' }}></span>
                  <div className="agenda-info">
                    <div className="agenda-name">Mon Agenda</div>
                    <div className="agenda-meta">Personnel</div>
                  </div>
                </div>
              </div>
              <Link to="/agendas" className="card-link">+ Nouvel agenda</Link>
              <Link to="/agendas" className="card-link">Gérer</Link>
            </div>
          </div>

          {/* Alertes/Notifications */}
          <div className="dashboard-card">
            <div className="card-header">
              <h3>🔔 Alertes</h3>
            </div>
            <div className="card-body">
              <div className="alert-list">
                <div className="alert-item">
                  <span className="alert-icon">📅</span>
                  <div className="alert-text">
                    {upcomingEvents.length > 0 
                      ? `${upcomingEvents.length} événement${upcomingEvents.length > 1 ? 's' : ''} à venir cette semaine`
                      : 'Aucun événement à venir'}
                  </div>
                </div>
              </div>
              <Link to="/calendar" className="card-link">Voir tout</Link>
            </div>
          </div>
        </div>

        {/* Aperçu de la semaine */}
        <div className="week-overview">
          <h3>📅 Aperçu de la semaine</h3>
          <div className="week-grid">
            {weekStats.map((day, index) => (
              <div key={index} className="week-day">
                <div className="week-day-header">
                  <div className="week-day-name">{formatDate(day.date)}</div>
                  <div className="week-day-count">{day.count} évén.</div>
                </div>
                <div className="week-day-indicators">
                  {day.events.slice(0, 3).map((evt, i) => (
                    <span key={i} className="event-dot" style={{ background: evt.backgroundColor || '#667eea' }}>
                      {getEventTypeIcon(evt.extendedProps?.type)}
                    </span>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Prochains événements */}
        {upcomingEvents.length > 0 && (
          <div className="upcoming-section">
            <h3>📆 Prochains événements</h3>
            <div className="upcoming-list">
              {upcomingEvents.map(event => (
                <div key={event.id} className="upcoming-item">
                  <div className="upcoming-date">
                    {new Date(event.start).toLocaleDateString('fr-FR', {
                      weekday: 'short',
                      day: 'numeric',
                      month: 'short'
                    })}
                  </div>
                  <div className="upcoming-details">
                    <div className="upcoming-title">
                      {getEventTypeIcon(event.extendedProps?.type)} {event.title}
                    </div>
                    <div className="upcoming-time">
                      {formatTime(event.start)} - {formatTime(event.end)}
                      {event.extendedProps?.location && ` • ${event.extendedProps.location}`}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Bouton d'action principal */}
        <div className="dashboard-actions">
          <Link to="/calendar" className="btn-primary">
            ➕ Nouvel Événement
          </Link>
        </div>

        {/* Légende et astuces */}
        <div className="dashboard-footer">
          <div className="legend">
            <span className="legend-item">📚 Cours</span>
            <span className="legend-item">👥 Réunion</span>
            <span className="legend-item">📝 Examen</span>
            <span className="legend-item">🎓 Formation</span>
          </div>
          <div className="tips">
            <p>💡 <strong>Astuce:</strong> Cliquez sur le calendrier pour créer un événement rapidement</p>
            <p>✅ <strong>Statut:</strong> {events.length} événement{events.length > 1 ? 's' : ''} au total</p>
          </div>
        </div>
      </div>
    </Layout>
  )
}

export default DashboardPage
