import { useEffect, useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import Layout from '../components/Layout'
import { useCalendar } from '../context/CalendarContext'
import { eventService } from '../api/events'
import './EventDetailsPage.css'

function EventDetailsPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { activeCalendar } = useCalendar()
  const [event, setEvent] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [isEditing, setIsEditing] = useState(!id) // En mode création si pas d'ID

  const typeColors = {
    course: { name: 'Cours', color: '#3788d8' },
    meeting: { name: 'Réunion', color: '#4caf50' },
    exam: { name: 'Examen', color: '#f44336' },
    training: { name: 'Formation', color: '#ff9800' },
    other: { name: 'Autre', color: '#9c27b0' }
  }

  useEffect(() => {
    if (id) {
      loadEvent()
    } else {
      setLoading(false)
      setEvent({
        title: '',
        startDate: '',
        endDate: '',
        location: '',
        type: 'other',
        description: '',
        calendarId: activeCalendar?.id || ''
      })
    }
  }, [id, activeCalendar])

  const loadEvent = async () => {
    try {
      setLoading(true)
      // TODO: Implémenter le chargement d'un événement spécifique
      // Pour maintenant, on crée un événement vide
      setEvent({
        title: 'Exemple d\'événement',
        startDate: new Date().toISOString().split('T')[0] + 'T10:00',
        endDate: new Date().toISOString().split('T')[0] + 'T11:00',
        location: 'Salle 101',
        type: 'course',
        description: 'Description de l\'événement'
      })
    } catch (err) {
      setError('Erreur lors du chargement de l\'événement')
      console.error(err)
    } finally {
      setLoading(false)
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    try {
      const payload = {
        title: event.title,
        start: event.startDate,
        end: event.endDate,
        type: event.type,
        location: event.location,
        description: event.description,
        calendarId: activeCalendar?.id || ''
      }
      console.log('📤 Envoi événement:', payload)
      console.log('📤 CalendarId:', activeCalendar?.id)
      
      if (id) {
        await eventService.update(id, payload)
      } else {
        await eventService.create(payload)
      }
      navigate('/calendar')
    } catch (err) {
      setError('Erreur lors de la sauvegarde')
      console.error(err)
    }
  }

  const handleDelete = async () => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
      try {
        await eventService.delete(id)
        navigate('/calendar')
      } catch (err) {
        setError('Erreur lors de la suppression')
        console.error(err)
      }
    }
  }

  if (loading) return <Layout><div className="loading">Chargement...</div></Layout>

  return (
    <Layout>
      <div className="event-details-page">
        <div className="event-header">
          <Link to="/calendar" className="back-link">← Retour au calendrier</Link>
          <h1>{id ? 'Modifier l\'événement' : 'Créer un événement'}</h1>
        </div>

        {error && <div className="error-message">{error}</div>}

        <form onSubmit={handleSubmit} className="event-form">
          <div className="form-section">
            <h2>Informations générales</h2>

            <div className="form-group">
              <label>Titre de l'événement *</label>
              <input
                type="text"
                required
                value={event?.title || ''}
                onChange={(e) => setEvent({ ...event, title: e.target.value })}
                placeholder="Ex: Réunion importante"
                disabled={!isEditing && id}
              />
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Date de début *</label>
                <input
                  type="datetime-local"
                  required
                  value={event?.startDate || ''}
                  onChange={(e) => setEvent({ ...event, startDate: e.target.value })}
                  disabled={!isEditing && id}
                />
              </div>
              <div className="form-group">
                <label>Date de fin *</label>
                <input
                  type="datetime-local"
                  required
                  value={event?.endDate || ''}
                  onChange={(e) => setEvent({ ...event, endDate: e.target.value })}
                  disabled={!isEditing && id}
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Type d'événement</label>
                <select
                  value={event?.type || 'other'}
                  onChange={(e) => setEvent({ ...event, type: e.target.value })}
                  disabled={!isEditing && id}
                >
                  {Object.entries(typeColors).map(([key, value]) => (
                    <option key={key} value={key}>{value.name}</option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Lieu</label>
                <input
                  type="text"
                  value={event?.location || ''}
                  onChange={(e) => setEvent({ ...event, location: e.target.value })}
                  placeholder="Ex: Salle 101"
                  disabled={!isEditing && id}
                />
              </div>
            </div>

            <div className="form-group">
              <label>Description</label>
              <textarea
                value={event?.description || ''}
                onChange={(e) => setEvent({ ...event, description: e.target.value })}
                placeholder="Détails supplémentaires..."
                rows="5"
                disabled={!isEditing && id}
              />
            </div>

            {activeCalendar && (
              <div className="calendar-info" style={{ 
                padding: '12px', 
                background: '#f0f7ff', 
                borderLeft: `4px solid ${activeCalendar.color}`,
                borderRadius: '4px',
                marginTop: '16px'
              }}>
                <strong>📅 Agenda sélectionné:</strong> {activeCalendar.name}
                <input type="hidden" value={activeCalendar.id} />
              </div>
            )}
          </div>

          <div className="form-actions">
            <Link to="/events" className="btn-secondary">Annuler</Link>
            {id && !isEditing ? (
              <>
                <Link to={`/event/${id}/alerts`} className="btn-secondary">
                  🔔 Alertes
                </Link>
                <button type="button" className="btn-secondary" onClick={() => setIsEditing(true)}>
                  ✏️ Modifier
                </button>
                <button type="button" className="btn-danger" onClick={handleDelete}>
                  🗑️ Supprimer
                </button>
              </>
            ) : (
              <button type="submit" className="btn-primary">
                {id ? 'Sauvegarder' : 'Créer l\'événement'}
              </button>
            )}
          </div>
        </form>
      </div>
    </Layout>
  )
}

export default EventDetailsPage
