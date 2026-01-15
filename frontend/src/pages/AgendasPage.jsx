import { useEffect, useState } from 'react'
import Layout from '../components/Layout'
import { useAuth } from '../auth/AuthContext'
import { useCalendar } from '../context/CalendarContext'
import { calendarService } from '../api/events'
import './AgendasPage.css'

function AgendasPage() {
  const { user } = useAuth()
  const { activeCalendar, selectCalendar } = useCalendar()
  const [calendars, setCalendars] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [showNewModal, setShowNewModal] = useState(false)
  const [newCalendar, setNewCalendar] = useState({
    name: '',
    description: '',
    color: '#667eea'
  })

  const colors = ['#667eea', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#fee140', '#ff9a56']

  useEffect(() => {
    loadCalendars()
  }, [])

  const loadCalendars = async () => {
    try {
      setLoading(true)
      const response = await calendarService.getAll()
      setCalendars(response.data || [])
      setError(null)
    } catch (err) {
      setError('Erreur lors du chargement des agendas')
      console.error(err)
    } finally {
      setLoading(false)
    }
  }

  const handleDeleteCalendar = async (calendarId) => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet agenda ?')) return
    try {
      await calendarService.delete(calendarId)
      await loadCalendars()
    } catch (err) {
      setError('Erreur lors de la suppression')
      console.error(err)
    }
  }

  const handleUpdateCalendar = async (calendarId, updates) => {
    try {
      await calendarService.update(calendarId, updates)
      await loadCalendars()
    } catch (err) {
      setError('Erreur lors de la modification')
      console.error(err)
    }
  }

  const handleCreateCalendar = async (e) => {
    e.preventDefault()
    try {
      console.log('📤 Création agenda - payload:', newCalendar)
      const response = await calendarService.create(newCalendar)
      console.log('✅ Agenda créé:', response.data)
      setShowNewModal(false)
      setNewCalendar({ name: '', description: '', color: '#667eea' })
      await loadCalendars()
      console.log('📚 Agendas après création:', calendars)
    } catch (err) {
      const apiMessage = err?.response?.data?.error || err?.response?.data?.message
      setError(apiMessage || 'Erreur lors de la création de l\'agenda')
      console.error('❌ Création agenda échouée:', err?.response?.data || err)
    }
  }

  const ownedCalendars = calendars // Afficher tous les agendas pour l'instant
  const sharedCalendars = []

  const filteredOwned = ownedCalendars.filter(cal =>
    cal.name.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const filteredShared = sharedCalendars.filter(cal =>
    cal.name.toLowerCase().includes(searchTerm.toLowerCase())
  )

  return (
    <Layout>
      <div className="agendas-page">
        <div className="page-header">
          <h2>📚 Mes Agendas</h2>
          <button className="btn-primary" onClick={() => setShowNewModal(true)}>
            ➕ Nouvel Agenda
          </button>
        </div>

        <div className="search-section">
          <input 
            type="text" 
            placeholder="🔍 Rechercher un agenda..." 
            className="search-input"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        {error && <div className="error-message">{error}</div>}

        {loading ? (
          <div className="loading">Chargement des agendas...</div>
        ) : (
          <>
            <div className="agendas-section">
              <h3>📌 Agendas Personnels</h3>
              {filteredOwned.length > 0 ? (
                <div className="agenda-list">
                  {filteredOwned.map((calendar) => (
                    <div key={calendar.id} className="agenda-card">
                      <div className="agenda-color" style={{ background: calendar.color }}></div>
                      <div className="agenda-info">
                        <div className="agenda-name">{calendar.name}</div>
                        <div className="agenda-meta">
                          {calendar.description && <p>{calendar.description}</p>}
                          <span className="agenda-type">Personnel</span>
                        </div>
                      </div>
                      <div className="agenda-actions">
                        <button 
                          className={`btn-primary ${activeCalendar?.id === calendar.id ? 'active' : ''}`}
                          onClick={() => selectCalendar(calendar)}
                        >
                          {activeCalendar?.id === calendar.id ? '✅ Agenda actif' : '⭐ Utiliser'}
                        </button>
                        <button className="btn-secondary" onClick={() => alert('Modifier: ' + calendar.name)}>✏️ Modifier</button>
                        <button className="btn-secondary" onClick={() => alert('Partager: ' + calendar.name)}>📤 Partager</button>
                        <button className="btn-danger" onClick={() => handleDeleteCalendar(calendar.id)}>🗑️</button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="empty-state">
                  <p>Aucun agenda personnel</p>
                  <p className="empty-hint">Créez votre premier agenda pour commencer</p>
                </div>
              )}
            </div>

            <div className="agendas-section">
              <h3>👥 Agendas Partagés (Propriétaire)</h3>
              {filteredOwned.length > 0 ? (
                <div className="agenda-list">
                  {filteredOwned
                    .filter(cal => cal.permissions && cal.permissions.length > 0)
                    .map((calendar) => (
                      <div key={calendar.id} className="agenda-card">
                        <div className="agenda-color" style={{ background: calendar.color }}></div>
                        <div className="agenda-info">
                          <div className="agenda-name">{calendar.name}</div>
                          <div className="agenda-meta">
                            <span className="share-count">
                              👥 Partagé avec {calendar.permissions?.length || 0} personne(s)
                            </span>
                          </div>
                        </div>
                        <div className="agenda-actions">
                          <button className="btn-secondary">📤 Gérer partages</button>
                          <button className="btn-secondary">✏️ Modifier</button>
                        </div>
                      </div>
                    ))}
                </div>
              ) : (
                <div className="empty-state">
                  <p>Aucun agenda partagé</p>
                  <p className="empty-hint">Créez un agenda et partagez-le avec vos collègues</p>
                </div>
              )}
            </div>

            <div className="agendas-section">
              <h3>📖 Agendas Partagés (Accès)</h3>
              {filteredShared.length > 0 ? (
                <div className="agenda-list">
                  {filteredShared.map((calendar) => (
                    <div key={calendar.id} className="agenda-card">
                      <div className="agenda-color" style={{ background: calendar.color }}></div>
                      <div className="agenda-info">
                        <div className="agenda-name">{calendar.name}</div>
                        <div className="agenda-meta">
                          <p>Propriétaire: {calendar.owner?.firstName} {calendar.owner?.lastName}</p>
                          {calendar.description && <p>{calendar.description}</p>}
                        </div>
                      </div>
                      <div className="agenda-actions">
                        <button className="btn-secondary">👁️ Consulter</button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="empty-state">
                  <p>Aucun agenda partagé avec vous</p>
                  <p className="empty-hint">Les agendas partagés apparaîtront ici</p>
                </div>
              )}
            </div>
          </>
        )}

        {showNewModal && (
          <div className="modal-overlay" onClick={() => setShowNewModal(false)}>
            <div className="modal-content" onClick={(e) => e.stopPropagation()}>
              <h2>Créer un nouvel agenda</h2>
              <form onSubmit={handleCreateCalendar}>
                <div className="form-group">
                  <label>Nom de l'agenda *</label>
                  <input
                    type="text"
                    required
                    value={newCalendar.name}
                    onChange={(e) => setNewCalendar({ ...newCalendar, name: e.target.value })}
                    placeholder="Ex: Mon projet"
                  />
                </div>

                <div className="form-group">
                  <label>Description</label>
                  <textarea
                    value={newCalendar.description}
                    onChange={(e) => setNewCalendar({ ...newCalendar, description: e.target.value })}
                    placeholder="Description optionnelle"
                    rows="3"
                  />
                </div>

                <div className="form-group">
                  <label>Couleur</label>
                  <div className="color-picker">
                    {colors.map((color) => (
                      <button
                        key={color}
                        type="button"
                        className={`color-option ${newCalendar.color === color ? 'active' : ''}`}
                        style={{ backgroundColor: color }}
                        onClick={() => setNewCalendar({ ...newCalendar, color })}
                      />
                    ))}
                  </div>
                </div>

                <div className="form-actions">
                  <button type="button" className="btn-secondary" onClick={() => setShowNewModal(false)}>
                    Annuler
                  </button>
                  <button type="submit" className="btn-primary">
                    Créer
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

export default AgendasPage
