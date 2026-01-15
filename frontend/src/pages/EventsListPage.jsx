import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import Layout from '../components/Layout'
import { eventService } from '../api/events'
import './EventsListPage.css'

function EventsListPage() {
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [filterType, setFilterType] = useState('all') // all, upcoming, past, today
  const [sortBy, setSortBy] = useState('date') // date, title, calendar
  const [showModal, setShowModal] = useState(false)
  const [selectedEvent, setSelectedEvent] = useState(null)

  const eventTypes = [
    { value: 'reunion', label: 'Réunion', icon: '👥', color: '#667eea' },
    { value: 'rdv', label: 'Rendez-vous', icon: '📞', color: '#4facfe' },
    { value: 'tache', label: 'Tâche', icon: '✅', color: '#43e97b' },
    { value: 'personnel', label: 'Personnel', icon: '👤', color: '#fa709a' },
    { value: 'autre', label: 'Autre', icon: '📌', color: '#fee140' }
  ]

  useEffect(() => {
    loadEvents()
  }, [])

  const loadEvents = async () => {
    try {
      setLoading(true)
      const response = await eventService.getAll()
      setEvents(response.data || [])
      setError(null)
    } catch (err) {
      setError('Erreur lors du chargement des événements')
      console.error(err)
    } finally {
      setLoading(false)
    }
  }

  const getEventType = (type) => {
    return eventTypes.find(t => t.value === type) || { label: type, icon: '📌', color: '#999' }
  }

  const filterEvents = () => {
    let filtered = events

    // Filtrer par recherche
    if (searchTerm) {
      filtered = filtered.filter(evt =>
        evt.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        evt.description?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        evt.calendar?.name.toLowerCase().includes(searchTerm.toLowerCase())
      )
    }

    // Filtrer par type de date
    const now = new Date()
    const today = new Date(now)
    today.setHours(0, 0, 0, 0)
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    switch (filterType) {
      case 'today':
        filtered = filtered.filter(evt => {
          const evtDate = new Date(evt.start)
          return evtDate >= today && evtDate < tomorrow
        })
        break
      case 'upcoming':
        filtered = filtered.filter(evt => {
          const evtDate = new Date(evt.start)
          return evtDate >= tomorrow
        })
        break
      case 'past':
        filtered = filtered.filter(evt => {
          const evtDate = new Date(evt.start)
          return evtDate < today
        })
        break
      default:
        // all - no additional filter
        break
    }

    // Trier
    filtered.sort((a, b) => {
      switch (sortBy) {
        case 'title':
          return a.title.localeCompare(b.title)
        case 'calendar':
          return (a.calendar?.name || '').localeCompare(b.calendar?.name || '')
        case 'date':
        default:
          return new Date(a.start) - new Date(b.start)
      }
    })

    return filtered
  }

  const handleDeleteEvent = async (id) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet événement?')) {
      try {
        await eventService.delete(id)
        await loadEvents()
        setShowModal(false)
        setSelectedEvent(null)
      } catch (err) {
        setError('Erreur lors de la suppression')
        console.error(err)
      }
    }
  }

  const formatDate = (dateStr) => {
    const date = new Date(dateStr)
    return new Intl.DateTimeFormat('fr-FR', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date)
  }

  const formatTime = (dateStr) => {
    const date = new Date(dateStr)
    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  }

  const filteredEvents = filterEvents()

  return (
    <Layout>
      <div className="events-list-page">
        <div className="page-header">
          <h2>🎯 Tous les événements</h2>
          <Link to="/event/new" className="btn-primary">
            ➕ Nouvel événement
          </Link>
        </div>

        <div className="controls-section">
          <div className="search-bar">
            <input 
              type="text" 
              placeholder="🔍 Rechercher un événement..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>

          <div className="filters-row">
            <div className="filter-group">
              <label>Afficher:</label>
              <select value={filterType} onChange={(e) => setFilterType(e.target.value)}>
                <option value="all">Tous les événements</option>
                <option value="today">Aujourd'hui</option>
                <option value="upcoming">À venir</option>
                <option value="past">Passés</option>
              </select>
            </div>

            <div className="filter-group">
              <label>Trier par:</label>
              <select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
                <option value="date">Date</option>
                <option value="title">Titre</option>
                <option value="calendar">Agenda</option>
              </select>
            </div>
          </div>
        </div>

        {error && <div className="error-message">{error}</div>}

        {loading ? (
          <div className="loading">Chargement des événements...</div>
        ) : filteredEvents.length === 0 ? (
          <div className="empty-state">
            <p>📭 Aucun événement trouvé</p>
            <Link to="/event/new" className="btn-primary">Créer un événement</Link>
          </div>
        ) : (
          <div className="events-container">
            <div className="events-stats">
              <span className="stat">📊 Total: {filteredEvents.length}</span>
              <span className="stat">🎯 Affichés: {filteredEvents.length}</span>
            </div>

            <div className="events-list">
              {filteredEvents.map((event) => {
                const eventType = getEventType(event.type)
                const startDate = new Date(event.start)
                const endDate = new Date(event.end)
                
                return (
                  <div 
                    key={event.id} 
                    className="event-card"
                    onClick={() => {
                      setSelectedEvent(event)
                      setShowModal(true)
                    }}
                  >
                    <div className="event-type-badge" style={{ backgroundColor: eventType.color }}>
                      {eventType.icon}
                    </div>

                    <div className="event-content">
                      <div className="event-title">{event.title}</div>
                      
                      <div className="event-meta">
                        <span className="event-time">
                          🕐 {formatTime(event.start)} - {formatTime(event.end)}
                        </span>
                        <span className="event-date">
                          📅 {formatDate(event.start)}
                        </span>
                      </div>

                      {event.description && (
                        <div className="event-description">{event.description}</div>
                      )}

                      {event.location && (
                        <div className="event-location">
                          📍 {event.location}
                        </div>
                      )}

                      <div className="event-calendar">
                        <span style={{ 
                          display: 'inline-block',
                          width: '12px',
                          height: '12px',
                          borderRadius: '2px',
                          backgroundColor: event.calendar?.color || '#999',
                          marginRight: '6px'
                        }}></span>
                        {event.calendar?.name || 'Sans agenda'}
                      </div>
                    </div>

                    <div className="event-actions">
                      <Link 
                        to={`/event/${event.id}`} 
                        className="btn-small btn-edit"
                        onClick={(e) => e.stopPropagation()}
                      >
                        ✏️
                      </Link>
                      <button 
                        className="btn-small btn-delete"
                        onClick={(e) => {
                          e.stopPropagation()
                          handleDeleteEvent(event.id)
                        }}
                      >
                        🗑️
                      </button>
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        )}

        {/* Modal Détails */}
        {showModal && selectedEvent && (
          <div className="modal-overlay" onClick={() => setShowModal(false)}>
            <div className="modal-content" onClick={(e) => e.stopPropagation()}>
              <div className="modal-header">
                <h3>{selectedEvent.title}</h3>
                <button className="btn-close" onClick={() => setShowModal(false)}>✕</button>
              </div>

              <div className="modal-body">
                <div className="detail-row">
                  <label>Type:</label>
                  <span>
                    <span style={{ marginRight: '8px' }}>
                      {getEventType(selectedEvent.type).icon}
                    </span>
                    {getEventType(selectedEvent.type).label}
                  </span>
                </div>

                <div className="detail-row">
                  <label>Date et heure:</label>
                  <span>
                    {formatDate(selectedEvent.start)} - {formatTime(selectedEvent.end)}
                  </span>
                </div>

                {selectedEvent.location && (
                  <div className="detail-row">
                    <label>Lieu:</label>
                    <span>{selectedEvent.location}</span>
                  </div>
                )}

                <div className="detail-row">
                  <label>Agenda:</label>
                  <span>
                    <span style={{ 
                      display: 'inline-block',
                      width: '12px',
                      height: '12px',
                      borderRadius: '2px',
                      backgroundColor: selectedEvent.calendar?.color || '#999',
                      marginRight: '6px'
                    }}></span>
                    {selectedEvent.calendar?.name || 'Sans agenda'}
                  </span>
                </div>

                {selectedEvent.description && (
                  <div className="detail-row full-width">
                    <label>Description:</label>
                    <p>{selectedEvent.description}</p>
                  </div>
                )}
              </div>

              <div className="modal-footer">
                <Link 
                  to={`/event/${selectedEvent.id}`} 
                  className="btn-secondary"
                  onClick={() => setShowModal(false)}
                >
                  ✏️ Modifier
                </Link>
                <button 
                  className="btn-danger"
                  onClick={() => handleDeleteEvent(selectedEvent.id)}
                >
                  🗑️ Supprimer
                </button>
                <button 
                  className="btn-secondary"
                  onClick={() => setShowModal(false)}
                >
                  Fermer
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </Layout>
  )
}

export default EventsListPage
