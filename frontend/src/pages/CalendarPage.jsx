import { useState, useEffect } from 'react'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import { eventService } from '../api/events'
import { useAuth } from '../auth/AuthContext'
import '../App.css'

const typeColors = {
  course: '#3788d8',
  meeting: '#4caf50',
  exam: '#f44336',
  training: '#ff9800',
  other: '#9c27b0'
}

const defaultFormData = {
  title: '',
  startDate: '',
  endDate: '',
  location: '',
  type: 'other',
  description: ''
}

function toLocalInputValue(value) {
  if (!value) return ''
  const date = new Date(value)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

function formatDateForInput(dateStr) {
  if (!dateStr) return ''
  return dateStr.split('T')[0] + 'T08:00'
}

function formatEndDateForInput(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  date.setDate(date.getDate() - 1)
  return date.toISOString().split('T')[0] + 'T09:00'
}

function mapApiEvent(event) {
  const type = event.extendedProps?.type ?? event.type ?? 'other'
  const location = event.extendedProps?.location ?? event.location ?? ''
  const description = event.extendedProps?.description ?? event.description ?? ''
  const color = event.backgroundColor || typeColors[type] || '#667eea'

  return {
    id: event.id,
    title: event.title,
    start: event.start,
    end: event.end,
    backgroundColor: color,
    borderColor: color,
    extendedProps: {
      type,
      location,
      description,
      color
    }
  }
}

function CalendarPage() {
  const { logout, user } = useAuth()
  const [events, setEvents] = useState([])
  const [error, setError] = useState(null)
  const [filterType, setFilterType] = useState(null)
  const [showModal, setShowModal] = useState(false)
  const [showDetailsModal, setShowDetailsModal] = useState(false)
  const [showEditModal, setShowEditModal] = useState(false)
  const [selectedEvent, setSelectedEvent] = useState(null)
  const [formData, setFormData] = useState(defaultFormData)

  const filteredEvents = filterType
    ? events.filter(evt => (evt.extendedProps?.type || evt.type) === filterType)
    : events
  const calendarEvents = filteredEvents.map(evt => ({ ...evt }))

  const loadEvents = async () => {
    try {
      setError(null)
      const response = await eventService.getAll()
      const fetched = (response?.data || []).map(mapApiEvent)
      setEvents(fetched)
    } catch (err) {
      console.error('Erreur lors du chargement des événements:', err)
      setError("Impossible de charger les événements. Vérifiez que le serveur Symfony est lancé.")
    }
  }

  useEffect(() => {
    loadEvents()
  }, [])

  const handleDateClick = (info) => {
    const start = `${info.dateStr}T08:00`
    const end = `${info.dateStr}T09:00`
    setFormData({ ...defaultFormData, startDate: start, endDate: end })
    setShowModal(true)
  }

  const handleSelectRange = (info) => {
    const startDate = formatDateForInput(info.startStr)
    const endDate = formatEndDateForInput(info.endStr)
    setFormData({ ...defaultFormData, startDate, endDate })
    setShowModal(true)
    info.jsEvent.preventDefault()
  }

  const handleFormSubmit = async (e) => {
    e.preventDefault()
    try {
      const payload = {
        title: formData.title,
        start: formData.startDate,
        end: formData.endDate,
        type: formData.type,
        location: formData.location,
        description: formData.description
      }

      const response = await eventService.create(payload)
      const newEvent = mapApiEvent(response.data)
      setEvents(prev => [...prev, newEvent])
      setFormData(defaultFormData)
      setShowModal(false)
    } catch (err) {
      console.error("Erreur lors de la création de l'événement:", err)
      alert("Erreur lors de la création de l'événement")
    }
  }

  const handleEventClick = (clickInfo) => {
    setSelectedEvent(clickInfo.event)
    setShowDetailsModal(true)
  }

  const persistEventChange = async (fcEvent) => {
    const type = fcEvent.extendedProps?.type || 'other'
    const location = fcEvent.extendedProps?.location || ''
    const description = fcEvent.extendedProps?.description || ''

    const payload = {
      title: fcEvent.title,
      start: fcEvent.startStr,
      end: fcEvent.endStr || fcEvent.startStr,
      type,
      location,
      description
    }

    const response = await eventService.update(fcEvent.id, payload)
    return mapApiEvent(response.data)
  }

  const handleEventDrop = async (info) => {
    try {
      const updated = await persistEventChange(info.event)
      setEvents(prev => prev.map(e => Number(e.id) === Number(updated.id) ? updated : e))
    } catch (err) {
      console.error("Erreur lors du déplacement de l'événement:", err)
      info.revert()
      alert("Impossible d'enregistrer le déplacement. Réessayez.")
    }
  }

  const handleEventResize = async (info) => {
    try {
      const updated = await persistEventChange(info.event)
      setEvents(prev => prev.map(e => Number(e.id) === Number(updated.id) ? updated : e))
    } catch (err) {
      console.error("Erreur lors du redimensionnement de l'événement:", err)
      info.revert()
      alert("Impossible d'enregistrer la nouvelle durée. Réessayez.")
    }
  }

  const handleEditEvent = () => {
    if (!selectedEvent) return
    setFormData({
      title: selectedEvent.title,
      startDate: toLocalInputValue(selectedEvent.start),
      endDate: toLocalInputValue(selectedEvent.end),
      location: selectedEvent.extendedProps?.location || '',
      type: selectedEvent.extendedProps?.type || 'other',
      description: selectedEvent.extendedProps?.description || ''
    })
    setShowDetailsModal(false)
    setShowEditModal(true)
  }

  const handleUpdateEvent = async (e) => {
    e.preventDefault()
    if (!selectedEvent) return

    try {
      const payload = {
        title: formData.title,
        start: formData.startDate,
        end: formData.endDate,
        type: formData.type,
        location: formData.location,
        description: formData.description
      }

      const response = await eventService.update(selectedEvent.id, payload)
      const updatedEvent = mapApiEvent(response.data)
      setEvents(prev => prev.map(e => Number(e.id) === Number(response.data.id) ? updatedEvent : e))
      setShowEditModal(false)
      setSelectedEvent(null)
    } catch (err) {
      console.error("Erreur lors de la mise à jour de l'événement:", err)
      alert("Erreur lors de la mise à jour de l'événement")
    }
  }

  const handleDeleteEvent = async () => {
    if (!selectedEvent) return

    try {
      await eventService.delete(selectedEvent.id)
      setEvents(prev => prev.filter(e => e.id !== Number.parseInt(selectedEvent.id)))
      setShowDetailsModal(false)
      setSelectedEvent(null)
    } catch (err) {
      console.error('Erreur lors de la suppression:', err)
      alert("Erreur lors de la suppression de l'événement")
    }
  }

  return (
    <>
      <div style={{ padding: '20px', maxWidth: '1200px', margin: '0 auto' }}>
        <div style={{
          background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          color: 'white',
          padding: '30px',
          borderRadius: '10px',
          marginBottom: '30px',
          boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '16px', flexWrap: 'wrap' }}>
            <div>
              <h1 style={{ margin: '0 0 8px 0', fontSize: '2.3em' }}>
                📅 Agenda Partagé - Lycée/BTS
              </h1>
              <p style={{ margin: 0, fontSize: '1.05em', opacity: 0.9 }}>
                Gestion d'agenda collaborative pour professeurs et personnel
              </p>
              <p style={{ margin: '8px 0 0 0', fontSize: '0.9em', opacity: 0.8 }}>
                🔗 Connecté à l'API Symfony (http://localhost:8001/api)
              </p>
              {user && (
                <p style={{ margin: '12px 0 0 0', fontSize: '0.95em', opacity: 0.95, fontWeight: '600' }}>
                  👤 Connecté en tant que : <strong>{user.firstName} {user.lastName}</strong>
                </p>
              )}
            </div>
            <button
              type="button"
              onClick={logout}
              style={{
                marginLeft: 'auto',
                padding: '12px 18px',
                background: 'rgba(255,255,255,0.15)',
                color: 'white',
                border: '2px solid rgba(255,255,255,0.35)',
                borderRadius: '10px',
                fontWeight: 700,
                cursor: 'pointer',
                backdropFilter: 'blur(4px)'
              }}
            >
              ↩ Se déconnecter
            </button>
          </div>
        </div>

        {error && (
          <div style={{
            background: '#ffebee',
            color: '#c62828',
            padding: '15px',
            borderRadius: '5px',
            marginBottom: '20px',
            border: '1px solid #ef5350'
          }}>
            ⚠️ {error}
          </div>
        )}

        <div style={{
          background: 'white',
          padding: '20px',
          borderRadius: '10px',
          boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
        }}>
          <div style={{ marginBottom: '20px', display: 'flex', gap: '10px', flexWrap: 'wrap', alignItems: 'center' }}>
            {[
              { key: 'course', label: 'Cours', color: '#e3f2fd', icon: '🔵' },
              { key: 'meeting', label: 'Réunion', color: '#e8f5e9', icon: '🟢' },
              { key: 'exam', label: 'Examen', color: '#ffebee', icon: '🔴' },
              { key: 'training', label: 'Formation', color: '#fff3e0', icon: '🟠' },
              { key: 'other', label: 'Autre', color: '#f3e5f5', icon: '🟣' }
            ].map((item) => (
              <button
                key={item.key}
                type="button"
                onClick={() => setFilterType(filterType === item.key ? null : item.key)}
                style={{
                  padding: '10px 16px',
                  background: filterType === item.key ? item.color : '#fff',
                  border: filterType === item.key ? `2px solid ${item.color}` : '2px solid #e0e0e0',
                  borderRadius: '6px',
                  fontWeight: 700,
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  boxShadow: '0 2px 6px rgba(0,0,0,0.08)'
                }}
              >
                <span>{item.icon}</span>
                <span>{item.label}</span>
              </button>
            ))}
            <button
              type="button"
              onClick={() => setFilterType(null)}
              style={{
                padding: '10px 16px',
                background: '#667eea',
                color: 'white',
                border: 'none',
                borderRadius: '8px',
                fontWeight: 700,
                cursor: 'pointer',
                boxShadow: '0 4px 10px rgba(0,0,0,0.1)'
              }}
            >
              🔄 Réinitialiser
            </button>
            <div style={{ marginLeft: 'auto' }}>
              <button
                type="button"
                onClick={loadEvents}
                style={{
                  padding: '12px 18px',
                  background: '#667eea',
                  color: 'white',
                  border: 'none',
                  borderRadius: '8px',
                  fontWeight: 700,
                  cursor: 'pointer',
                  boxShadow: '0 4px 10px rgba(0,0,0,0.1)'
                }}
              >
                🔁 Recharger
              </button>
            </div>
          </div>

          <FullCalendar
            plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
            initialView="dayGridMonth"
            headerToolbar={{
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,timeGridDay'
            }}
            events={calendarEvents}
            dateClick={handleDateClick}
            select={handleSelectRange}
            eventClick={handleEventClick}
            eventDrop={handleEventDrop}
            eventResize={handleEventResize}
            editable={true}
            selectable={true}
            selectMirror={true}
            dayMaxEvents={true}
            weekends={true}
            locale="fr"
            buttonText={{
              today: "Aujourd'hui",
              month: 'Mois',
              week: 'Semaine',
              day: 'Jour'
            }}
            height="auto"
            slotMinTime="07:00:00"
            slotMaxTime="19:00:00"
          />

          <div style={{
            marginTop: '20px',
            padding: '15px',
            background: '#f5f5f5',
            borderRadius: '5px',
            fontSize: '0.9em',
            color: '#666'
          }}>
            <p style={{ margin: '5px 0' }}>
              💡 <strong>Astuce:</strong> Cliquez sur une date pour ajouter un événement
            </p>
            <p style={{ margin: '5px 0' }}>
              👁️ <strong>Détails:</strong> Cliquez sur un événement pour voir ses détails
            </p>
            <p style={{ margin: '5px 0' }}>
              ✅ <strong>Statut:</strong> {events.length} événement{events.length > 1 ? 's' : ''} chargé{events.length > 1 ? 's' : ''} depuis Symfony
            </p>
          </div>
        </div>
      </div>

      {showModal && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0,0,0,0.5)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000
        }} onClick={() => setShowModal(false)}>
          <div style={{
            background: 'white',
            borderRadius: '15px',
            maxWidth: '650px',
            width: '90%',
            maxHeight: '90vh',
            overflowY: 'auto',
            boxShadow: '0 10px 40px rgba(0,0,0,0.3)'
          }} onClick={(e) => e.stopPropagation()}>
            <div style={{
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              color: 'white',
              padding: '25px 30px',
              borderBottom: '3px solid rgba(0,0,0,0.1)'
            }}>
              <h2 style={{ margin: 0, fontSize: '1.8em', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '10px' }}>
                <span>📝</span>
                <span>Nouvel événement</span>
              </h2>
              <p style={{ margin: '8px 0 0 0', opacity: 0.9, fontSize: '0.95em' }}>
                Remplissez les informations de l'événement
              </p>
            </div>

            <form onSubmit={handleFormSubmit} style={{ padding: '30px' }}>
              <div style={{ marginBottom: '20px' }}>
                <label style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  marginBottom: '8px',
                  fontWeight: '600',
                  color: '#333',
                  fontSize: '14px'
                }}>
                  <span>✏️</span>
                  <span>Titre *</span>
                </label>
                <input
                  type="text"
                  required
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '12px 15px',
                    border: '2px solid #e0e0e0',
                    borderRadius: '8px',
                    fontSize: '15px',
                    transition: 'border-color 0.2s',
                    outline: 'none'
                  }}
                  onFocus={(e) => (e.target.style.borderColor = '#667eea')}
                  onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                />
              </div>

              <div style={{ marginBottom: '20px' }}>
                <label style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  marginBottom: '12px',
                  fontWeight: '600',
                  color: '#333',
                  fontSize: '14px'
                }}>
                  <span>🏷️</span>
                  <span>Type d'événement</span>
                </label>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                  {[
                    { value: 'course', icon: '📚', label: 'Cours', color: '#3788d8' },
                    { value: 'meeting', icon: '👥', label: 'Réunion', color: '#4caf50' },
                    { value: 'exam', icon: '📝', label: 'Examen', color: '#f44336' },
                    { value: 'training', icon: '🎓', label: 'Formation', color: '#ff9800' }
                  ].map(type => (
                    <button
                      key={type.value}
                      type="button"
                      onClick={() => setFormData({ ...formData, type: type.value })}
                      style={{
                        padding: '12px 15px',
                        border: formData.type === type.value ? `3px solid ${type.color}` : '2px solid #e0e0e0',
                        borderRadius: '8px',
                        background: formData.type === type.value ? `${type.color}15` : '#fff',
                        cursor: 'pointer',
                        fontWeight: '600',
                        fontSize: '14px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '8px',
                        transition: 'all 0.2s',
                        color: formData.type === type.value ? type.color : '#333'
                      }}
                      onMouseOver={(e) => {
                        if (formData.type !== type.value) {
                          e.currentTarget.style.borderColor = type.color
                          e.currentTarget.style.background = `${type.color}08`
                        }
                      }}
                      onMouseOut={(e) => {
                        if (formData.type !== type.value) {
                          e.currentTarget.style.borderColor = '#e0e0e0'
                          e.currentTarget.style.background = '#fff'
                        }
                      }}
                    >
                      <span style={{ fontSize: '1.2em' }}>{type.icon}</span>
                      <span>{type.label}</span>
                    </button>
                  ))}
                </div>
                <button
                  type="button"
                  onClick={() => setFormData({ ...formData, type: 'other' })}
                  style={{
                    width: '100%',
                    marginTop: '10px',
                    padding: '12px 15px',
                    border: formData.type === 'other' ? '3px solid #9c27b0' : '2px solid #e0e0e0',
                    borderRadius: '8px',
                    background: formData.type === 'other' ? '#9c27b015' : '#fff',
                    cursor: 'pointer',
                    fontWeight: '600',
                    fontSize: '14px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '8px',
                    transition: 'all 0.2s',
                    color: formData.type === 'other' ? '#9c27b0' : '#333'
                  }}
                  onMouseOver={(e) => {
                    if (formData.type !== 'other') {
                      e.currentTarget.style.borderColor = '#9c27b0'
                      e.currentTarget.style.background = '#9c27b008'
                    }
                  }}
                  onMouseOut={(e) => {
                    if (formData.type !== 'other') {
                      e.currentTarget.style.borderColor = '#e0e0e0'
                      e.currentTarget.style.background = '#fff'
                    }
                  }}
                >
                  <span style={{ fontSize: '1.2em' }}>📌</span>
                  <span>Autre</span>
                </button>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', marginBottom: '20px' }}>
                <div>
                  <label style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    marginBottom: '8px',
                    fontWeight: '600',
                    color: '#333',
                    fontSize: '14px'
                  }}>
                    <span>🕐</span>
                    <span>Début *</span>
                  </label>
                  <input
                    type="datetime-local"
                    required
                    value={formData.startDate}
                    onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
                    style={{
                      width: '100%',
                      padding: '12px 15px',
                      border: '2px solid #e0e0e0',
                      borderRadius: '8px',
                      fontSize: '14px',
                      outline: 'none'
                    }}
                    onFocus={(e) => (e.target.style.borderColor = '#667eea')}
                    onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                  />
                </div>
                <div>
                  <label style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    marginBottom: '8px',
                    fontWeight: '600',
                    color: '#333',
                    fontSize: '14px'
                  }}>
                    <span>🕐</span>
                    <span>Fin *</span>
                  </label>
                  <input
                    type="datetime-local"
                    required
                    value={formData.endDate}
                    onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
                    style={{
                      width: '100%',
                      padding: '12px 15px',
                      border: '2px solid #e0e0e0',
                      borderRadius: '8px',
                      fontSize: '14px',
                      outline: 'none'
                    }}
                    onFocus={(e) => (e.target.style.borderColor = '#667eea')}
                    onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                  />
                </div>
              </div>

              <div style={{ marginBottom: '20px' }}>
                <label style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  marginBottom: '8px',
                  fontWeight: '600',
                  color: '#333',
                  fontSize: '14px'
                }}>
                  <span>📍</span>
                  <span>Lieu</span>
                </label>
                <input
                  type="text"
                  value={formData.location}
                  onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '12px 15px',
                    border: '2px solid #e0e0e0',
                    borderRadius: '8px',
                    fontSize: '15px',
                    outline: 'none'
                  }}
                  onFocus={(e) => (e.target.style.borderColor = '#667eea')}
                  onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                />
              </div>

              <div style={{ marginBottom: '25px' }}>
                <label style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  marginBottom: '8px',
                  fontWeight: '600',
                  color: '#333',
                  fontSize: '14px'
                }}>
                  <span>💬</span>
                  <span>Description</span>
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows="4"
                  style={{
                    width: '100%',
                    padding: '12px 15px',
                    border: '2px solid #e0e0e0',
                    borderRadius: '8px',
                    fontSize: '15px',
                    resize: 'vertical',
                    fontFamily: 'inherit',
                    outline: 'none',
                    lineHeight: '1.5'
                  }}
                  onFocus={(e) => (e.target.style.borderColor = '#667eea')}
                  onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                />
              </div>

              <div style={{
                display: 'flex',
                gap: '12px',
                justifyContent: 'flex-end',
                paddingTop: '20px',
                borderTop: '2px solid #f0f0f0'
              }}>
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  style={{
                    padding: '12px 28px',
                    background: '#6c757d',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontWeight: '600',
                    fontSize: '15px',
                    transition: 'all 0.2s'
                  }}
                  onMouseOver={(e) => {
                    e.target.style.background = '#5a6268'
                    e.target.style.transform = 'translateY(-1px)'
                    e.target.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)'
                  }}
                  onMouseOut={(e) => {
                    e.target.style.background = '#6c757d'
                    e.target.style.transform = 'translateY(0)'
                    e.target.style.boxShadow = 'none'
                  }}
                >
                  ✕ Annuler
                </button>
                <button
                  type="submit"
                  style={{
                    padding: '12px 28px',
                    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontWeight: '600',
                    fontSize: '15px',
                    transition: 'all 0.2s'
                  }}
                  onMouseOver={(e) => {
                    e.target.style.transform = 'translateY(-1px)'
                    e.target.style.boxShadow = '0 6px 20px rgba(102, 126, 234, 0.4)'
                  }}
                  onMouseOut={(e) => {
                    e.target.style.transform = 'translateY(0)'
                    e.target.style.boxShadow = 'none'
                  }}
                >
                  ✓ Créer l'événement
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {showDetailsModal && selectedEvent && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0,0,0,0.5)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000
        }} onClick={() => setShowDetailsModal(false)}>
          <div style={{
            background: 'white',
            padding: '0',
            borderRadius: '15px',
            maxWidth: '600px',
            width: '90%',
            boxShadow: '0 10px 40px rgba(0,0,0,0.3)',
            overflow: 'hidden'
          }} onClick={(e) => e.stopPropagation()}>
            <div style={{
              background: selectedEvent.backgroundColor || '#667eea',
              color: 'white',
              padding: '25px 30px',
              borderBottom: '3px solid rgba(0,0,0,0.1)'
            }}>
              <h2 style={{ margin: 0, fontSize: '1.8em', fontWeight: 'bold' }}>
                {selectedEvent.title}
              </h2>
            </div>

            <div style={{ padding: '30px' }}>
              <div style={{ marginBottom: '25px' }}>
                <div style={{
                  display: 'grid',
                  gap: '20px',
                  gridTemplateColumns: '1fr'
                }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: '15px',
                    padding: '15px',
                    background: '#f8f9fa',
                    borderRadius: '8px',
                    borderLeft: '4px solid ' + (selectedEvent.backgroundColor || '#667eea')
                  }}>
                    <span style={{ fontSize: '1.5em' }}>🕐</span>
                    <div>
                      <div style={{ fontWeight: 'bold', color: '#333', marginBottom: '5px' }}>Horaires</div>
                      <div style={{ color: '#666' }}>
                        <div>Début: {selectedEvent.start.toLocaleString('fr-FR', {
                          weekday: 'long',
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit'
                        })}</div>
                        {selectedEvent.end && (
                          <div>Fin: {selectedEvent.end.toLocaleString('fr-FR', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                          })}</div>
                        )}
                      </div>
                    </div>
                  </div>

                  {selectedEvent.extendedProps?.type && (
                    <div style={{
                      display: 'flex',
                      alignItems: 'flex-start',
                      gap: '15px',
                      padding: '15px',
                      background: '#f8f9fa',
                      borderRadius: '8px',
                      borderLeft: '4px solid ' + (selectedEvent.backgroundColor || '#667eea')
                    }}>
                      <span style={{ fontSize: '1.5em' }}>
                        {selectedEvent.extendedProps.type === 'course' ? '📚' :
                        selectedEvent.extendedProps.type === 'meeting' ? '👥' :
                        selectedEvent.extendedProps.type === 'exam' ? '📝' :
                        selectedEvent.extendedProps.type === 'training' ? '🎓' : '📌'}
                      </span>
                      <div>
                        <div style={{ fontWeight: 'bold', color: '#333', marginBottom: '5px' }}>Type</div>
                        <div style={{ color: '#666' }}>
                          {selectedEvent.extendedProps.type === 'course' ? 'Cours' :
                          selectedEvent.extendedProps.type === 'meeting' ? 'Réunion' :
                          selectedEvent.extendedProps.type === 'exam' ? 'Examen' :
                          selectedEvent.extendedProps.type === 'training' ? 'Formation' : 'Autre'}
                        </div>
                      </div>
                    </div>
                  )}

                  {selectedEvent.extendedProps?.location && (
                    <div style={{
                      display: 'flex',
                      alignItems: 'flex-start',
                      gap: '15px',
                      padding: '15px',
                      background: '#f8f9fa',
                      borderRadius: '8px',
                      borderLeft: '4px solid ' + (selectedEvent.backgroundColor || '#667eea')
                    }}>
                      <span style={{ fontSize: '1.5em' }}>📍</span>
                      <div>
                        <div style={{ fontWeight: 'bold', color: '#333', marginBottom: '5px' }}>Lieu</div>
                        <div style={{ color: '#666' }}>{selectedEvent.extendedProps.location}</div>
                      </div>
                    </div>
                  )}

                  {selectedEvent.extendedProps?.description && (
                    <div style={{
                      display: 'flex',
                      alignItems: 'flex-start',
                      gap: '15px',
                      padding: '15px',
                      background: '#f8f9fa',
                      borderRadius: '8px',
                      borderLeft: '4px solid ' + (selectedEvent.backgroundColor || '#667eea')
                    }}>
                      <span style={{ fontSize: '1.5em' }}>💬</span>
                      <div>
                        <div style={{ fontWeight: 'bold', color: '#333', marginBottom: '5px' }}>Description</div>
                        <div style={{ color: '#666', lineHeight: '1.5' }}>{selectedEvent.extendedProps.description}</div>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', paddingTop: '20px', borderTop: '1px solid #e0e0e0' }}>
                <button
                  type="button"
                  onClick={() => setShowDetailsModal(false)}
                  style={{
                    padding: '12px 24px',
                    background: '#6c757d',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontWeight: 'bold',
                    fontSize: '14px',
                    transition: 'background 0.2s'
                  }}
                  onMouseOver={(e) => (e.target.style.background = '#5a6268')}
                  onMouseOut={(e) => (e.target.style.background = '#6c757d')}
                >
                  Fermer
                </button>
                <button
                  type="button"
                  onClick={handleEditEvent}
                  style={{
                    padding: '12px 24px',
                    background: '#0d6efd',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontWeight: 'bold',
                    fontSize: '14px',
                    transition: 'background 0.2s'
                  }}
                  onMouseOver={(e) => (e.target.style.background = '#0b5ed7')}
                  onMouseOut={(e) => (e.target.style.background = '#0d6efd')}
                >
                  ✏️ Modifier
                </button>
                <button
                  type="button"
                  onClick={handleDeleteEvent}
                  style={{
                    padding: '12px 24px',
                    background: '#dc3545',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontWeight: 'bold',
                    fontSize: '14px',
                    transition: 'background 0.2s'
                  }}
                  onMouseOver={(e) => (e.target.style.background = '#c82333')}
                  onMouseOut={(e) => (e.target.style.background = '#dc3545')}
                >
                  🗑️ Supprimer
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {showEditModal && (
        <div
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            background: 'rgba(0, 0, 0, 0.6)',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            zIndex: 1000
          }}
          onClick={() => setShowEditModal(false)}
        >
          <div
            style={{
              background: 'white',
              borderRadius: '12px',
              padding: '30px',
              width: '90%',
              maxWidth: '500px',
              boxShadow: '0 10px 40px rgba(0, 0, 0, 0.3)',
              maxHeight: '90vh',
              overflowY: 'auto'
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <h2 style={{ marginTop: 0, marginBottom: '25px', color: '#333', fontSize: '24px' }}>
              Modifier l'événement
            </h2>

            <form onSubmit={handleUpdateEvent} style={{ display: 'flex', flexDirection: 'column', gap: '18px' }}>
              <div>
                <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: '#555', fontSize: '14px' }}>
                  Titre
                </label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    border: '2px solid #e0e0e0',
                    borderRadius: '6px',
                    fontSize: '14px',
                    boxSizing: 'border-box',
                    transition: 'border-color 0.2s'
                  }}
                  onFocus={(e) => (e.target.style.borderColor = '#0d6efd')}
                  onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                />
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
                <div>
                  <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: '#555', fontSize: '14px' }}>
                    Date début
                  </label>
                  <input
                    type="datetime-local"
                    value={formData.startDate}
                    onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
                    style={{
                      width: '100%',
                      padding: '10px 12px',
                      border: '2px solid #e0e0e0',
                      borderRadius: '6px',
                      fontSize: '14px',
                      boxSizing: 'border-box',
                      transition: 'border-color 0.2s'
                    }}
                    onFocus={(e) => (e.target.style.borderColor = '#0d6efd')}
                    onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                  />
                </div>

                <div>
                  <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: '#555', fontSize: '14px' }}>
                    Date fin
                  </label>
                  <input
                    type="datetime-local"
                    value={formData.endDate}
                    onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
                    style={{
                      width: '100%',
                      padding: '10px 12px',
                      border: '2px solid #e0e0e0',
                      borderRadius: '6px',
                      fontSize: '14px',
                      boxSizing: 'border-box',
                      transition: 'border-color 0.2s'
                    }}
                    onFocus={(e) => (e.target.style.borderColor = '#0d6efd')}
                    onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                  />
                </div>
              </div>

              <div>
                <label style={{ display: 'block', marginBottom: '12px', fontWeight: 'bold', color: '#555', fontSize: '14px' }}>
                  Type
                </label>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
                  {['course', 'meeting', 'exam', 'training', 'other'].map((type) => (
                    <button
                      key={type}
                      type="button"
                      onClick={() => setFormData({ ...formData, type })}
                      style={{
                        padding: '10px 12px',
                        background: formData.type === type ? '#0d6efd' : '#f0f0f0',
                        color: formData.type === type ? 'white' : '#333',
                        border: formData.type === type ? '2px solid #0d6efd' : '2px solid #e0e0e0',
                        borderRadius: '6px',
                        cursor: 'pointer',
                        fontWeight: formData.type === type ? 'bold' : 'normal',
                        fontSize: '12px',
                        transition: 'all 0.2s'
                      }}
                    >
                      {type === 'course' ? '📚 Cours' : type === 'meeting' ? '👥 Réunion' : type === 'exam' ? '📝 Examen' : type === 'training' ? '🎓 Formation' : '📌 Autre'}
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: '#555', fontSize: '14px' }}>
                  Lieu
                </label>
                <input
                  type="text"
                  value={formData.location}
                  onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    border: '2px solid #e0e0e0',
                    borderRadius: '6px',
                    fontSize: '14px',
                    boxSizing: 'border-box',
                    transition: 'border-color 0.2s'
                  }}
                  onFocus={(e) => (e.target.style.borderColor = '#0d6efd')}
                  onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                />
              </div>

              <div>
                <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: '#555', fontSize: '14px' }}>
                  Description
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows="4"
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    border: '2px solid #e0e0e0',
                    borderRadius: '6px',
                    fontSize: '14px',
                    boxSizing: 'border-box',
                    fontFamily: 'inherit',
                    transition: 'border-color 0.2s',
                    resize: 'vertical'
                  }}
                  onFocus={(e) => (e.target.style.borderColor = '#0d6efd')}
                  onBlur={(e) => (e.target.style.borderColor = '#e0e0e0')}
                />
              </div>

              <div style={{ display: 'flex', gap: '12px', marginTop: '25px' }}>
                <button
                  type="button"
                  onClick={() => setShowEditModal(false)}
                  style={{
                    flex: 1,
                    padding: '12px 24px',
                    background: '#6c757d',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontWeight: 'bold',
                    fontSize: '14px',
                    transition: 'background 0.2s'
                  }}
                  onMouseOver={(e) => (e.target.style.background = '#5a6268')}
                  onMouseOut={(e) => (e.target.style.background = '#6c757d')}
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  style={{
                    flex: 1,
                    padding: '12px 24px',
                    background: '#28a745',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontWeight: 'bold',
                    fontSize: '14px',
                    transition: 'background 0.2s'
                  }}
                  onMouseOver={(e) => (e.target.style.background = '#218838')}
                  onMouseOut={(e) => (e.target.style.background = '#28a745')}
                >
                  💾 Enregistrer
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  )
}

export default CalendarPage
