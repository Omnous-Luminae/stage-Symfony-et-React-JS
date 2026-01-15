import { useEffect, useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import Layout from '../components/Layout'
import { calendarService } from '../api/events'
import './CalendarDetailsPage.css'

function CalendarDetailsPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [calendar, setCalendar] = useState(null)
  const [permissions, setPermissions] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [isEditing, setIsEditing] = useState(false)
  const [showShareModal, setShowShareModal] = useState(false)
  const [shareEmail, setShareEmail] = useState('')

  useEffect(() => {
    loadCalendarDetails()
  }, [id])

  const loadCalendarDetails = async () => {
    try {
      setLoading(true)
      const response = await calendarService.getById(id)
      setCalendar(response.data)
      
      try {
        const permResponse = await calendarService.getPermissions(id)
        setPermissions(permResponse.data || [])
      } catch (err) {
        console.log('Pas de permissions à charger')
      }
    } catch (err) {
      setError('Erreur lors du chargement de l\'agenda')
      console.error(err)
    } finally {
      setLoading(false)
    }
  }

  const handleSave = async () => {
    try {
      await calendarService.update(id, calendar)
      setIsEditing(false)
      setError(null)
    } catch (err) {
      setError('Erreur lors de la sauvegarde')
      console.error(err)
    }
  }

  const handleShare = async (e) => {
    e.preventDefault()
    try {
      await calendarService.share(id, { email: shareEmail })
      setShareEmail('')
      setShowShareModal(false)
      await loadCalendarDetails()
    } catch (err) {
      setError(err.response?.data?.error || 'Erreur lors du partage')
      console.error(err)
    }
  }

  const handleRemovePermission = async (permissionId) => {
    if (window.confirm('Confirmer la suppression de cet accès ?')) {
      try {
        await calendarService.removePermission(id, permissionId)
        await loadCalendarDetails()
      } catch (err) {
        setError('Erreur lors de la suppression')
        console.error(err)
      }
    }
  }

  const handleDelete = async () => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cet agenda ? Cette action est irréversible.')) {
      try {
        await calendarService.delete(id)
        navigate('/agendas')
      } catch (err) {
        setError('Erreur lors de la suppression')
        console.error(err)
      }
    }
  }

  if (loading) return <Layout><div className="loading">Chargement...</div></Layout>
  if (!calendar) return <Layout><div className="error-message">Agenda non trouvé</div></Layout>

  return (
    <Layout>
      <div className="calendar-details-page">
        <div className="page-header">
          <Link to="/agendas" className="back-link">← Retour aux agendas</Link>
          <h1>{calendar.name}</h1>
        </div>

        {error && <div className="error-message">{error}</div>}

        <div className="calendar-info-card">
          <div className="calendar-header-info">
            <div className="calendar-color-display" style={{ backgroundColor: calendar.color }}></div>
            <div className="calendar-basic-info">
              <h2>{calendar.name}</h2>
              <p className="calendar-type">{calendar.type === 'personal' ? '📌 Personnel' : '👥 Partagé'}</p>
              {calendar.description && <p className="calendar-description">{calendar.description}</p>}
            </div>
          </div>

          {!isEditing && (
            <div className="info-actions">
              <button className="btn-secondary" onClick={() => setIsEditing(true)}>✏️ Modifier</button>
              <button className="btn-secondary" onClick={() => setShowShareModal(true)}>📤 Partager</button>
              <button className="btn-danger" onClick={handleDelete}>🗑️ Supprimer</button>
            </div>
          )}
        </div>

        {isEditing && (
          <div className="edit-form-card">
            <h2>Modifier l'agenda</h2>
            <div className="form-group">
              <label>Nom</label>
              <input
                type="text"
                value={calendar.name || ''}
                onChange={(e) => setCalendar({ ...calendar, name: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label>Description</label>
              <textarea
                value={calendar.description || ''}
                onChange={(e) => setCalendar({ ...calendar, description: e.target.value })}
                rows="3"
              />
            </div>

            <div className="form-group">
              <label>Couleur</label>
              <div className="color-picker">
                {['#667eea', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#fee140', '#ff9a56'].map((color) => (
                  <button
                    key={color}
                    type="button"
                    className={`color-option ${calendar.color === color ? 'active' : ''}`}
                    style={{ backgroundColor: color }}
                    onClick={() => setCalendar({ ...calendar, color })}
                  />
                ))}
              </div>
            </div>

            <div className="form-actions">
              <button className="btn-secondary" onClick={() => setIsEditing(false)}>Annuler</button>
              <button className="btn-primary" onClick={handleSave}>Sauvegarder</button>
            </div>
          </div>
        )}

        {calendar.permissions && calendar.permissions.length > 0 && (
          <div className="permissions-card">
            <h2>Accès partagés</h2>
            <div className="permissions-list">
              {calendar.permissions.map((perm) => (
                <div key={perm.id} className="permission-item">
                  <div className="permission-info">
                    <p className="permission-user">{perm.user?.firstName} {perm.user?.lastName}</p>
                    <p className="permission-email">{perm.user?.email}</p>
                    <span className="permission-role">
                      {perm.permission === 'Modification' ? '✏️ Modérateur' : 
                       perm.permission === 'Administration' ? '👑 Administrateur' : 
                       '👁️ Lecteur'}
                    </span>
                  </div>
                  <button 
                    className="btn-danger-small"
                    onClick={() => handleRemovePermission(perm.id)}
                  >
                    ✕
                  </button>
                </div>
              ))}
            </div>
          </div>
        )}

        {showShareModal && (
          <div className="modal-overlay" onClick={() => setShowShareModal(false)}>
            <div className="modal-content" onClick={(e) => e.stopPropagation()}>
              <h2>Partager l'agenda</h2>
              <form onSubmit={handleShare}>
                <div className="form-group">
                  <label>Adresse email de la personne *</label>
                  <input
                    type="email"
                    required
                    value={shareEmail}
                    onChange={(e) => setShareEmail(e.target.value)}
                    placeholder="exemple@lycee.fr"
                  />
                </div>

                <div className="form-actions">
                  <button type="button" className="btn-secondary" onClick={() => setShowShareModal(false)}>
                    Annuler
                  </button>
                  <button type="submit" className="btn-primary">
                    Partager
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </Layout>
  )
}

export default CalendarDetailsPage
