import { useEffect, useState, useCallback, useMemo } from 'react'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import Layout from '../components/Layout'
import { eventService, calendarService } from '../api/events'
import { useAuth } from '../auth/AuthContext'
import { useNotification } from '../context/NotificationContext'
import { usePreferences } from '../hooks/usePreferences'
import './CalendarPage.css'

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
  description: '',
  isRecurring: false,
  recurrenceType: 'weekly',
  recurrenceInterval: 1,
  recurrenceEndDate: '',
  recurrenceDays: []
}

const calendarColors = ['#667eea', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#fee140', '#ff9a56', '#00d2d3', '#54a0ff']

function toLocalInputValue(value) {
  if (!value) return ''
  const date = new Date(value)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

function formatDateForInput(dateStr) {
  if (!dateStr) return ''
  // Si la date contient déjà une heure (format ISO avec T), la conserver
  if (dateStr.includes('T') && dateStr.length > 11) {
    return dateStr.slice(0, 16) // Format YYYY-MM-DDTHH:mm
  }
  return dateStr.split('T')[0] + 'T08:00'
}

function formatEndDateForInput(dateStr, isAllDay = false) {
  if (!dateStr) return ''
  
  // Pour les sélections "allDay" dans la vue mois, FullCalendar renvoie 
  // le jour APRÈS la fin (ex: sélection 15-17 → end = 18)
  // On doit soustraire 1 jour UNIQUEMENT dans ce cas
  if (isAllDay) {
    const date = new Date(dateStr)
    date.setDate(date.getDate() - 1)
    return date.toISOString().split('T')[0] + 'T17:00'
  }
  
  // Pour les sélections avec heure (vue semaine/jour), garder la date telle quelle
  if (dateStr.includes('T') && dateStr.length > 11) {
    return dateStr.slice(0, 16)
  }
  
  // Date seule sans heure - ajouter une heure par défaut
  return dateStr.split('T')[0] + 'T09:00'
}

function mapApiEvent(event) {
  const type = event.extendedProps?.type ?? event.type ?? 'other'
  const location = event.extendedProps?.location ?? event.location ?? ''
  const description = event.extendedProps?.description ?? event.description ?? ''
  const calendarId = event.extendedProps?.calendarId ?? event.calendarId ?? null
  const calendarName = event.extendedProps?.calendarName ?? event.calendarName ?? 'Événement général'
  const color = event.backgroundColor || typeColors[type] || '#667eea'
  const isRecurring = event.extendedProps?.isRecurring ?? false
  const recurrence = event.extendedProps?.recurrence ?? null
  const parentEventId = event.extendedProps?.parentEventId ?? null

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
      calendarId,
      calendarName,
      color,
      isRecurring,
      recurrence,
      parentEventId
    }
  }
}

function CalendarPage() {
  const { user, isAuthenticated } = useAuth()
  const { showSuccess, showError } = useNotification()
  const { preferences } = usePreferences()
  
  // Convertir les préférences en paramètres FullCalendar
  const calendarSettings = useMemo(() => {
    // Mapping de la vue par défaut
    const viewMapping = {
      'month': 'dayGridMonth',
      'week': 'timeGridWeek',
      'day': 'timeGridDay'
    }
    
    // Mapping du premier jour de la semaine (0 = dimanche, 1 = lundi)
    const firstDayMapping = {
      'sunday': 0,
      'monday': 1
    }
    
    return {
      initialView: viewMapping[preferences.defaultView] || 'dayGridMonth',
      firstDay: firstDayMapping[preferences.weekStartsOn] || 1
    }
  }, [preferences.defaultView, preferences.weekStartsOn])
  
  // États pour les agendas
  const [calendars, setCalendars] = useState([])
  const [activeCalendar, setActiveCalendar] = useState(null)
  const [loadingCalendars, setLoadingCalendars] = useState(true)
  
  // États pour les événements
  const [events, setEvents] = useState([])
  const [filterType, setFilterType] = useState(null)
  
  // États pour les modales
  const [showNewCalendarModal, setShowNewCalendarModal] = useState(false)
  const [showEditCalendarModal, setShowEditCalendarModal] = useState(false)
  const [showShareModal, setShowShareModal] = useState(false)
  const [showEventModal, setShowEventModal] = useState(false)
  const [showDetailsModal, setShowDetailsModal] = useState(false)
  const [showEditEventModal, setShowEditEventModal] = useState(false)
  const [showDeleteConfirmModal, setShowDeleteConfirmModal] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState({ type: null, id: null, name: '', isRecurring: false, parentEventId: null })
  
  // État pour le formulaire d'agenda
  const [calendarFormData, setCalendarFormData] = useState({
    name: '',
    description: '',
    color: '#667eea'
  })
  
  // État pour le partage
  const [shareEmail, setShareEmail] = useState('')
  const [sharePermission, setSharePermission] = useState('Consultation')
  const [sharedUsers, setSharedUsers] = useState([])
  
  // État pour le formulaire d'événement
  const [formData, setFormData] = useState(defaultFormData)
  const [selectedEvent, setSelectedEvent] = useState(null)

  // Charger les agendas
  const loadCalendars = useCallback(async () => {
    try {
      setLoadingCalendars(true)
      const response = await calendarService.getAll()
      setCalendars(response.data || [])
      
      // Sélectionner automatiquement le premier agenda si aucun n'est actif
      if (!activeCalendar && response.data?.length > 0) {
        setActiveCalendar(response.data[0])
      }
    } catch (err) {
      console.error('Erreur chargement agendas:', err)
      showError('Impossible de charger les agendas')
    } finally {
      setLoadingCalendars(false)
    }
  }, [activeCalendar, showError])

  // Charger les événements
  const loadEvents = useCallback(async () => {
    try {
      const response = await eventService.getAll()
      const fetched = (response?.data || []).map(mapApiEvent)
      setEvents(fetched)
    } catch (err) {
      console.error('Erreur chargement événements:', err)
      showError('Impossible de charger les événements')
    }
  }, [showError])

  useEffect(() => {
    loadCalendars()
    loadEvents()
  }, [])

  // Filtrer les événements par agenda actif et type
  let filteredEvents = events
  if (activeCalendar) {
    filteredEvents = filteredEvents.filter(evt => {
      const eventCalendarId = evt.extendedProps?.calendarId
      return eventCalendarId && Number(eventCalendarId) === Number(activeCalendar.id)
    })
  }
  if (filterType) {
    filteredEvents = filteredEvents.filter(evt => (evt.extendedProps?.type || evt.type) === filterType)
  }

  // Gestion des agendas
  const handleCreateCalendar = async (e) => {
    e.preventDefault()
    try {
      const response = await calendarService.create(calendarFormData)
      setCalendars(prev => [...prev, response.data])
      setActiveCalendar(response.data)
      setShowNewCalendarModal(false)
      setCalendarFormData({ name: '', description: '', color: '#667eea' })
      showSuccess('Agenda créé avec succès')
    } catch (err) {
      console.error('Erreur création agenda:', err)
      showError(err?.response?.data?.error || 'Erreur lors de la création')
    }
  }

  const handleUpdateCalendar = async (e) => {
    e.preventDefault()
    if (!activeCalendar) return
    try {
      const response = await calendarService.update(activeCalendar.id, calendarFormData)
      setCalendars(prev => prev.map(c => c.id === activeCalendar.id ? response.data : c))
      setActiveCalendar(response.data)
      setShowEditCalendarModal(false)
      showSuccess('Agenda modifié avec succès')
    } catch (err) {
      console.error('Erreur modification agenda:', err)
      showError('Erreur lors de la modification')
    }
  }

  const handleDeleteCalendar = async (calendarId) => {
    const calendar = calendars.find(c => c.id === calendarId)
    setDeleteTarget({ type: 'calendar', id: calendarId, name: calendar?.name || 'cet agenda' })
    setShowDeleteConfirmModal(true)
  }

  const confirmDeleteCalendar = async () => {
    const calendarId = deleteTarget.id
    try {
      await calendarService.delete(calendarId)
      setCalendars(prev => prev.filter(c => c.id !== calendarId))
      if (activeCalendar?.id === calendarId) {
        const remaining = calendars.filter(c => c.id !== calendarId)
        setActiveCalendar(remaining[0] || null)
      }
      setShowDeleteConfirmModal(false)
      setDeleteTarget({ type: null, id: null, name: '' })
      showSuccess('Agenda supprimé')
    } catch (err) {
      console.error('Erreur suppression agenda:', err)
      showError('Erreur lors de la suppression')
    }
  }

  const openEditCalendarModal = () => {
    if (!activeCalendar) return
    setCalendarFormData({
      name: activeCalendar.name,
      description: activeCalendar.description || '',
      color: activeCalendar.color || '#667eea'
    })
    setShowEditCalendarModal(true)
  }

  // Gestion du partage
  const handleAddShare = async () => {
    if (!shareEmail || !activeCalendar) return
    try {
      // Appel API pour partager l'agenda
      await calendarService.share(activeCalendar.id, {
        email: shareEmail,
        permission: sharePermission
      })
      setSharedUsers(prev => [...prev, { email: shareEmail, permission: sharePermission }])
      setShareEmail('')
      showSuccess(`Agenda partagé avec ${shareEmail}`)
    } catch (err) {
      console.error('Erreur partage:', err)
      showError(err?.response?.data?.error || 'Erreur lors du partage')
    }
  }

  const handleRemoveShare = async (email) => {
    if (!activeCalendar) return
    try {
      await calendarService.removeShare(activeCalendar.id, email)
      setSharedUsers(prev => prev.filter(u => u.email !== email))
      showSuccess('Partage supprimé')
    } catch (err) {
      console.error('Erreur suppression partage:', err)
      showError('Erreur lors de la suppression du partage')
    }
  }

  // Gestion des événements
  const handleDateClick = (info) => {
    if (!activeCalendar) {
      showError('Sélectionnez un agenda avant de créer un événement')
      return
    }
    const start = `${info.dateStr}T08:00`
    const end = `${info.dateStr}T09:00`
    setFormData({ ...defaultFormData, startDate: start, endDate: end })
    setShowEventModal(true)
  }

  const handleSelectRange = (info) => {
    if (!activeCalendar) {
      showError('Sélectionnez un agenda avant de créer un événement')
      return
    }
    const startDate = formatDateForInput(info.startStr)
    // Passer info.allDay pour savoir si c'est une sélection de journée entière
    const endDate = formatEndDateForInput(info.endStr, info.allDay)
    setFormData({ ...defaultFormData, startDate, endDate })
    setShowEventModal(true)
    info.jsEvent?.preventDefault()
  }

  const handleEventClick = (clickInfo) => {
    setSelectedEvent(clickInfo.event)
    setShowDetailsModal(true)
  }

  const handleCreateEvent = async (e) => {
    e.preventDefault()
    if (!activeCalendar) return
    try {
      const payload = {
        title: formData.title,
        start: formData.startDate,
        end: formData.endDate,
        type: formData.type,
        location: formData.location,
        description: formData.description,
        calendarId: activeCalendar.id,
        // Données de récurrence
        isRecurring: formData.isRecurring,
        recurrenceType: formData.recurrenceType,
        recurrenceInterval: formData.recurrenceInterval,
        recurrenceDays: formData.recurrenceDays,
        recurrenceEndDate: formData.recurrenceEndDate
      }
      const response = await eventService.create(payload)
      const newEvent = mapApiEvent(response.data)
      setEvents(prev => [...prev, newEvent])
      setFormData(defaultFormData)
      setShowEventModal(false)
      
      // Message personnalisé si récurrent
      if (formData.isRecurring && formData.recurrenceEndDate) {
        showSuccess('Événement récurrent créé avec ses occurrences')
      } else {
        showSuccess('Événement créé')
      }
      
      // Recharger les événements pour voir les occurrences générées
      if (formData.isRecurring && formData.recurrenceEndDate) {
        loadEvents()
      }
    } catch (err) {
      console.error('Erreur création événement:', err)
      showError('Erreur lors de la création')
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
    setShowEditEventModal(true)
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
      setShowEditEventModal(false)
      setSelectedEvent(null)
      showSuccess('Événement modifié')
    } catch (err) {
      console.error('Erreur modification événement:', err)
      showError('Erreur lors de la modification')
    }
  }

  const handleDeleteEvent = async () => {
    if (!selectedEvent) return
    const isRecurring = selectedEvent.extendedProps?.isRecurring || false
    const parentEventId = selectedEvent.extendedProps?.parentEventId || null
    // Si c'est une occurrence (a un parent), on utilise l'ID du parent pour supprimer la série
    setDeleteTarget({ 
      type: 'event', 
      id: selectedEvent.id, 
      name: selectedEvent.title,
      isRecurring,
      parentEventId,
      // Si c'est le parent ou une occurrence, on peut supprimer la série
      isPartOfSeries: isRecurring || parentEventId !== null
    })
    setShowDeleteConfirmModal(true)
  }

  // Supprimer uniquement cette occurrence
  const confirmDeleteSingleEvent = async () => {
    try {
      await eventService.delete(deleteTarget.id)
      setEvents(prev => prev.filter(e => e.id !== Number.parseInt(deleteTarget.id)))
      setShowDetailsModal(false)
      setSelectedEvent(null)
      setShowDeleteConfirmModal(false)
      setDeleteTarget({ type: null, id: null, name: '', isRecurring: false, parentEventId: null })
      showSuccess('Occurrence supprimée')
    } catch (err) {
      console.error('Erreur suppression événement:', err)
      showError('Erreur lors de la suppression')
    }
  }

  // Supprimer toute la série (parent + occurrences)
  const confirmDeleteEventSeries = async () => {
    try {
      // Si c'est une occurrence, on supprime via le parent
      // Si c'est le parent, on le supprime directement (le backend supprimera les enfants en cascade)
      const idToDelete = deleteTarget.parentEventId || deleteTarget.id
      await eventService.delete(idToDelete, { deleteSeries: true })
      
      // Recharger tous les événements pour refléter la suppression en cascade
      await loadEvents()
      
      setShowDetailsModal(false)
      setSelectedEvent(null)
      setShowDeleteConfirmModal(false)
      setDeleteTarget({ type: null, id: null, name: '', isRecurring: false, parentEventId: null })
      showSuccess('Série d\'événements supprimée')
    } catch (err) {
      console.error('Erreur suppression série:', err)
      showError('Erreur lors de la suppression de la série')
    }
  }

  const confirmDeleteEvent = async () => {
    try {
      await eventService.delete(deleteTarget.id)
      setEvents(prev => prev.filter(e => e.id !== Number.parseInt(deleteTarget.id)))
      setShowDetailsModal(false)
      setSelectedEvent(null)
      setShowDeleteConfirmModal(false)
      setDeleteTarget({ type: null, id: null, name: '', isRecurring: false, parentEventId: null })
      showSuccess('Événement supprimé')
    } catch (err) {
      console.error('Erreur suppression événement:', err)
      showError('Erreur lors de la suppression')
    }
  }

  const confirmDelete = async () => {
    if (deleteTarget.type === 'calendar') {
      await confirmDeleteCalendar()
    } else if (deleteTarget.type === 'event') {
      await confirmDeleteEvent()
    }
  }

  const cancelDelete = () => {
    setShowDeleteConfirmModal(false)
    setDeleteTarget({ type: null, id: null, name: '', isRecurring: false, parentEventId: null })
  }

  const handleEventDrop = async (info) => {
    try {
      const payload = {
        title: info.event.title,
        start: info.event.startStr,
        end: info.event.endStr || info.event.startStr,
        type: info.event.extendedProps?.type || 'other',
        location: info.event.extendedProps?.location || '',
        description: info.event.extendedProps?.description || ''
      }
      const response = await eventService.update(info.event.id, payload)
      const updated = mapApiEvent(response.data)
      setEvents(prev => prev.map(e => Number(e.id) === Number(updated.id) ? updated : e))
    } catch (err) {
      console.error('Erreur déplacement:', err)
      info.revert()
      showError('Impossible de déplacer l\'événement')
    }
  }

  const handleEventResize = async (info) => {
    try {
      const payload = {
        title: info.event.title,
        start: info.event.startStr,
        end: info.event.endStr || info.event.startStr,
        type: info.event.extendedProps?.type || 'other',
        location: info.event.extendedProps?.location || '',
        description: info.event.extendedProps?.description || ''
      }
      const response = await eventService.update(info.event.id, payload)
      const updated = mapApiEvent(response.data)
      setEvents(prev => prev.map(e => Number(e.id) === Number(updated.id) ? updated : e))
    } catch (err) {
      console.error('Erreur redimensionnement:', err)
      info.revert()
      showError('Impossible de modifier la durée')
    }
  }

  const getTypeInfo = (type) => {
    const types = {
      course: { icon: '📚', label: 'Cours', color: '#3788d8' },
      meeting: { icon: '👥', label: 'Réunion', color: '#4caf50' },
      exam: { icon: '📝', label: 'Examen', color: '#f44336' },
      training: { icon: '🎓', label: 'Formation', color: '#ff9800' },
      other: { icon: '📌', label: 'Autre', color: '#9c27b0' }
    }
    return types[type] || types.other
  }

  return (
    <Layout>
      <div className="calendar-layout">
        {/* Sidebar des agendas */}
        <aside className="calendar-sidebar">
          <div className="sidebar-header">
            <h3>📚 Mes Agendas</h3>
            <button className="btn-new-calendar" onClick={() => setShowNewCalendarModal(true)}>
              ➕ Nouvel agenda
            </button>
          </div>
          
          <div className="calendars-list">
            {loadingCalendars ? (
              <div className="empty-calendars">Chargement...</div>
            ) : calendars.length === 0 ? (
              <div className="empty-calendars">
                <p>Aucun agenda</p>
                <button className="btn-action primary" onClick={() => setShowNewCalendarModal(true)}>
                  Créer mon premier agenda
                </button>
              </div>
            ) : (
              <>
                {/* Agendas personnels (dont l'utilisateur est propriétaire) */}
                <div className="calendar-section">
                  <h4>📌 Agendas personnels</h4>
                  {calendars.filter(c => c.type === 'personal' || c.isOwner).length === 0 ? (
                    <div className="empty-section">Aucun agenda personnel</div>
                  ) : (
                    calendars.filter(c => c.type === 'personal' || c.isOwner).map(calendar => (
                      <div
                        key={calendar.id}
                        className={`calendar-item ${activeCalendar?.id === calendar.id ? 'active' : ''}`}
                        onClick={() => setActiveCalendar(calendar)}
                      >
                        <div className="calendar-color-dot" style={{ background: calendar.color }} />
                        <div className="calendar-item-info">
                          <div className="calendar-item-name">{calendar.name}</div>
                          <div className="calendar-item-meta">
                            {events.filter(e => e.extendedProps?.calendarId === calendar.id).length} événement(s)
                          </div>
                        </div>
                        <div className="calendar-item-actions">
                          <button 
                            className="btn-icon" 
                            title="Partager"
                            onClick={(e) => { e.stopPropagation(); setActiveCalendar(calendar); setShowShareModal(true); }}
                          >
                            📤
                          </button>
                          <button 
                            className="btn-icon danger" 
                            title="Supprimer"
                            onClick={(e) => { e.stopPropagation(); handleDeleteCalendar(calendar.id); }}
                          >
                            🗑️
                          </button>
                        </div>
                      </div>
                    ))
                  )}
                </div>

                {/* Agendas partagés avec moi */}
                <div className="calendar-section">
                  <h4>👥 Agendas partagés</h4>
                  {calendars.filter(c => c.type === 'shared' && !c.isOwner).length === 0 ? (
                    <div className="empty-section">Aucun agenda partagé avec vous</div>
                  ) : (
                    calendars.filter(c => c.type === 'shared' && !c.isOwner).map(calendar => (
                      <div
                        key={calendar.id}
                        className={`calendar-item shared ${activeCalendar?.id === calendar.id ? 'active' : ''}`}
                        onClick={() => setActiveCalendar(calendar)}
                      >
                        <div className="calendar-color-dot" style={{ background: calendar.color }} />
                        <div className="calendar-item-info">
                          <div className="calendar-item-name">{calendar.name}</div>
                          <div className="calendar-item-meta">
                            Par {calendar.ownerName || 'Inconnu'} • {calendar.permission || 'Consultation'}
                          </div>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </>
            )}
          </div>
        </aside>

        {/* Contenu principal */}
        <main className="calendar-main">
          {activeCalendar ? (
            <>
              <div className="calendar-main-header">
                <div className="active-calendar-info">
                  <div className="calendar-color-dot" style={{ background: activeCalendar.color }} />
                  <div>
                    <h2>{activeCalendar.name}</h2>
                    {activeCalendar.description && <span>{activeCalendar.description}</span>}
                  </div>
                </div>
                <div className="calendar-actions">
                  <button className="btn-action secondary" onClick={openEditCalendarModal}>
                    ✏️ Modifier
                  </button>
                  <button className="btn-action secondary" onClick={() => setShowShareModal(true)}>
                    📤 Partager
                  </button>
                  <button className="btn-action primary" onClick={() => {
                    const now = new Date()
                    const start = now.toISOString().slice(0, 16)
                    const end = new Date(now.getTime() + 3600000).toISOString().slice(0, 16)
                    setFormData({ ...defaultFormData, startDate: start, endDate: end })
                    setShowEventModal(true)
                  }}>
                    ➕ Nouvel événement
                  </button>
                </div>
              </div>

              <div className="filters-bar">
                {[
                  { key: 'course', label: 'Cours', icon: '📚' },
                  { key: 'meeting', label: 'Réunion', icon: '👥' },
                  { key: 'exam', label: 'Examen', icon: '📝' },
                  { key: 'training', label: 'Formation', icon: '🎓' },
                  { key: 'other', label: 'Autre', icon: '📌' }
                ].map(item => (
                  <button
                    key={item.key}
                    className={`filter-btn ${filterType === item.key ? 'active' : ''}`}
                    onClick={() => setFilterType(filterType === item.key ? null : item.key)}
                  >
                    {item.icon} {item.label}
                  </button>
                ))}
                <button className="filter-reset" onClick={() => setFilterType(null)}>
                  🔄 Tous
                </button>
              </div>

              <FullCalendar
                key={`${calendarSettings.initialView}-${calendarSettings.firstDay}`}
                plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
                initialView={calendarSettings.initialView}
                firstDay={calendarSettings.firstDay}
                headerToolbar={{
                  left: 'prev,next today',
                  center: 'title',
                  right: 'dayGridMonth,timeGridWeek,timeGridDay'
                }}
                events={filteredEvents}
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

              <div className="calendar-tips">
                <p>💡 <strong>Astuce:</strong> Cliquez sur une date pour créer un événement</p>
                <p>✅ <strong>Statut:</strong> {filteredEvents.length} événement(s) affiché(s)</p>
              </div>
            </>
          ) : (
            <div className="no-calendar-selected">
              <div className="icon">📅</div>
              <h3>Aucun agenda sélectionné</h3>
              <p>Sélectionnez un agenda dans la barre latérale ou créez-en un nouveau</p>
              <button className="btn-action primary" onClick={() => setShowNewCalendarModal(true)} style={{ marginTop: '20px' }}>
                ➕ Créer un agenda
              </button>
            </div>
          )}
        </main>
      </div>

      {/* Modal: Nouvel agenda */}
      {showNewCalendarModal && (
        <div className="modal-overlay" onClick={() => setShowNewCalendarModal(false)}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>📚 Nouvel agenda</h2>
              <p>Créez un nouvel agenda pour organiser vos événements</p>
            </div>
            <form onSubmit={handleCreateCalendar}>
              <div className="modal-body">
                <div className="form-group">
                  <label>Nom de l'agenda *</label>
                  <input
                    type="text"
                    required
                    value={calendarFormData.name}
                    onChange={e => setCalendarFormData({ ...calendarFormData, name: e.target.value })}
                    placeholder="Ex: Cours BTS SIO"
                  />
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea
                    value={calendarFormData.description}
                    onChange={e => setCalendarFormData({ ...calendarFormData, description: e.target.value })}
                    placeholder="Description optionnelle"
                    rows={3}
                  />
                </div>
                <div className="form-group">
                  <label>Couleur</label>
                  <div className="color-picker">
                    {calendarColors.map(color => (
                      <button
                        key={color}
                        type="button"
                        className={`color-option ${calendarFormData.color === color ? 'active' : ''}`}
                        style={{ background: color }}
                        onClick={() => setCalendarFormData({ ...calendarFormData, color })}
                      />
                    ))}
                  </div>
                </div>
              </div>
              <div className="modal-body">
                <div className="modal-actions">
                  <button type="button" className="btn-cancel" onClick={() => setShowNewCalendarModal(false)}>
                    Annuler
                  </button>
                  <button type="submit" className="btn-submit">
                    ✓ Créer
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Modifier agenda */}
      {showEditCalendarModal && (
        <div className="modal-overlay" onClick={() => setShowEditCalendarModal(false)}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>✏️ Modifier l'agenda</h2>
            </div>
            <form onSubmit={handleUpdateCalendar}>
              <div className="modal-body">
                <div className="form-group">
                  <label>Nom de l'agenda *</label>
                  <input
                    type="text"
                    required
                    value={calendarFormData.name}
                    onChange={e => setCalendarFormData({ ...calendarFormData, name: e.target.value })}
                  />
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea
                    value={calendarFormData.description}
                    onChange={e => setCalendarFormData({ ...calendarFormData, description: e.target.value })}
                    rows={3}
                  />
                </div>
                <div className="form-group">
                  <label>Couleur</label>
                  <div className="color-picker">
                    {calendarColors.map(color => (
                      <button
                        key={color}
                        type="button"
                        className={`color-option ${calendarFormData.color === color ? 'active' : ''}`}
                        style={{ background: color }}
                        onClick={() => setCalendarFormData({ ...calendarFormData, color })}
                      />
                    ))}
                  </div>
                </div>
              </div>
              <div className="modal-body">
                <div className="modal-actions">
                  <button type="button" className="btn-cancel" onClick={() => setShowEditCalendarModal(false)}>
                    Annuler
                  </button>
                  <button type="submit" className="btn-submit">
                    💾 Enregistrer
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Partager agenda */}
      {showShareModal && activeCalendar && (
        <div className="modal-overlay" onClick={() => setShowShareModal(false)}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>📤 Partager "{activeCalendar.name}"</h2>
              <p>Invitez des personnes à accéder à cet agenda</p>
            </div>
            <div className="modal-body">
              <div className="share-section">
                <h4>Ajouter une personne</h4>
                <div className="share-input-group">
                  <input
                    type="email"
                    placeholder="Email de la personne"
                    value={shareEmail}
                    onChange={e => setShareEmail(e.target.value)}
                  />
                  <select value={sharePermission} onChange={e => setSharePermission(e.target.value)}>
                    <option value="Consultation">Lecture</option>
                    <option value="Modification">Modification</option>
                    <option value="Administration">Admin</option>
                  </select>
                  <button type="button" className="btn-add-share" onClick={handleAddShare}>
                    ➕
                  </button>
                </div>
              </div>

              {sharedUsers.length > 0 && (
                <div className="share-section">
                  <h4>Personnes ayant accès</h4>
                  <div className="shared-users-list">
                    {sharedUsers.map((user, index) => (
                      <div key={index} className="shared-user-item">
                        <div className="shared-user-info">
                          <div className="shared-user-avatar">
                            {user.email.charAt(0).toUpperCase()}
                          </div>
                          <div className="shared-user-details">
                            <div className="shared-user-email">{user.email}</div>
                            <div className="shared-user-permission">{user.permission}</div>
                          </div>
                        </div>
                        <button 
                          className="btn-remove-share" 
                          onClick={() => handleRemoveShare(user.email)}
                          title="Retirer l'accès"
                        >
                          ✕
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <div className="modal-actions">
                <button type="button" className="btn-cancel" onClick={() => setShowShareModal(false)}>
                  Fermer
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Nouvel événement */}
      {showEventModal && (
        <div className="modal-overlay" onClick={() => setShowEventModal(false)}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>📝 Nouvel événement</h2>
              <p>Agenda: {activeCalendar?.name}</p>
            </div>
            <form onSubmit={handleCreateEvent}>
              <div className="modal-body">
                <div className="form-group">
                  <label>Titre *</label>
                  <input
                    type="text"
                    required
                    value={formData.title}
                    onChange={e => setFormData({ ...formData, title: e.target.value })}
                    placeholder="Titre de l'événement"
                  />
                </div>
                <div className="form-group">
                  <label>Type</label>
                  <div className="color-picker">
                    {['course', 'meeting', 'exam', 'training', 'other'].map(type => {
                      const info = getTypeInfo(type)
                      return (
                        <button
                          key={type}
                          type="button"
                          className={`filter-btn ${formData.type === type ? 'active' : ''}`}
                          style={{ 
                            borderColor: formData.type === type ? info.color : '#e0e0e0',
                            background: formData.type === type ? `${info.color}15` : 'white'
                          }}
                          onClick={() => setFormData({ ...formData, type })}
                        >
                          {info.icon} {info.label}
                        </button>
                      )
                    })}
                  </div>
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
                  <div className="form-group">
                    <label>Début *</label>
                    <input
                      type="datetime-local"
                      required
                      value={formData.startDate}
                      onChange={e => setFormData({ ...formData, startDate: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label>Fin *</label>
                    <input
                      type="datetime-local"
                      required
                      value={formData.endDate}
                      onChange={e => setFormData({ ...formData, endDate: e.target.value })}
                    />
                  </div>
                </div>
                <div className="form-group">
                  <label>Lieu</label>
                  <input
                    type="text"
                    value={formData.location}
                    onChange={e => setFormData({ ...formData, location: e.target.value })}
                    placeholder="Lieu de l'événement"
                  />
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea
                    value={formData.description}
                    onChange={e => setFormData({ ...formData, description: e.target.value })}
                    rows={3}
                    placeholder="Description optionnelle"
                  />
                </div>

                {/* Section récurrence */}
                <div className="form-group recurrence-section">
                  <label className="recurrence-checkbox">
                    <input
                      type="checkbox"
                      checked={formData.isRecurring}
                      onChange={e => {
                        const isRecurring = e.target.checked
                        let recurrenceEndDate = formData.recurrenceEndDate
                        
                        // Si on active la récurrence et qu'il n'y a pas de date de fin, en définir une par défaut (1 mois après)
                        if (isRecurring && !recurrenceEndDate && formData.startDate) {
                          const startDate = new Date(formData.startDate)
                          startDate.setMonth(startDate.getMonth() + 1)
                          recurrenceEndDate = startDate.toISOString().split('T')[0]
                        }
                        
                        setFormData({ 
                          ...formData, 
                          isRecurring,
                          recurrenceEndDate
                        })
                      }}
                    />
                    <span>🔄 Événement récurrent</span>
                  </label>

                  {formData.isRecurring && (
                    <div className="recurrence-options">
                      <div className="recurrence-row">
                        <div className="form-group" style={{ flex: 1, marginBottom: 0 }}>
                          <label>Type de récurrence</label>
                          <select
                            value={formData.recurrenceType}
                            onChange={e => setFormData({ ...formData, recurrenceType: e.target.value })}
                            className="recurrence-select"
                          >
                            <option value="daily">📅 Quotidien</option>
                            <option value="weekly">📆 Hebdomadaire</option>
                            <option value="biweekly">📆 Toutes les 2 semaines</option>
                            <option value="monthly">🗓️ Mensuel</option>
                            <option value="yearly">🎂 Annuel</option>
                          </select>
                        </div>
                        <div className="form-group" style={{ flex: 1, marginBottom: 0 }}>
                          <label>Répéter tous les</label>
                          <div className="interval-input">
                            <input
                              type="number"
                              min="1"
                              max="30"
                              value={formData.recurrenceInterval}
                              onChange={e => setFormData({ ...formData, recurrenceInterval: parseInt(e.target.value) || 1 })}
                            />
                            <span>
                              {formData.recurrenceType === 'daily' && 'jour(s)'}
                              {formData.recurrenceType === 'weekly' && 'semaine(s)'}
                              {formData.recurrenceType === 'biweekly' && 'période(s)'}
                              {formData.recurrenceType === 'monthly' && 'mois'}
                              {formData.recurrenceType === 'yearly' && 'an(s)'}
                            </span>
                          </div>
                        </div>
                      </div>

                      {(formData.recurrenceType === 'weekly' || formData.recurrenceType === 'biweekly') && (
                        <div className="form-group" style={{ marginBottom: 0, marginTop: '15px' }}>
                          <label>Jours de la semaine</label>
                          <div className="weekday-picker">
                            {[
                              { key: 'mon', label: 'L' },
                              { key: 'tue', label: 'M' },
                              { key: 'wed', label: 'M' },
                              { key: 'thu', label: 'J' },
                              { key: 'fri', label: 'V' },
                              { key: 'sat', label: 'S' },
                              { key: 'sun', label: 'D' }
                            ].map(day => (
                              <button
                                key={day.key}
                                type="button"
                                className={`weekday-btn ${formData.recurrenceDays.includes(day.key) ? 'active' : ''}`}
                                onClick={() => {
                                  const days = formData.recurrenceDays.includes(day.key)
                                    ? formData.recurrenceDays.filter(d => d !== day.key)
                                    : [...formData.recurrenceDays, day.key]
                                  setFormData({ ...formData, recurrenceDays: days })
                                }}
                              >
                                {day.label}
                              </button>
                            ))}
                          </div>
                        </div>
                      )}

                      <div className="form-group" style={{ marginBottom: 0, marginTop: '15px' }}>
                        <label>Jusqu'au</label>
                        <input
                          type="date"
                          value={formData.recurrenceEndDate}
                          onChange={e => setFormData({ ...formData, recurrenceEndDate: e.target.value })}
                          className="recurrence-end-date"
                        />
                      </div>
                    </div>
                  )}
                </div>
              </div>
              <div className="modal-body">
                <div className="modal-actions">
                  <button type="button" className="btn-cancel" onClick={() => setShowEventModal(false)}>
                    Annuler
                  </button>
                  <button type="submit" className="btn-submit">
                    ✓ Créer
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Détails événement */}
      {showDetailsModal && selectedEvent && (
        <div className="modal-overlay" onClick={() => setShowDetailsModal(false)}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <div className="modal-header" style={{ background: selectedEvent.backgroundColor || '#667eea' }}>
              <h2>{selectedEvent.title}</h2>
            </div>
            <div className="modal-body">
              <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px', padding: '12px', background: '#f8f9fa', borderRadius: '8px' }}>
                  <span style={{ fontSize: '1.3em' }}>🕐</span>
                  <div>
                    <div style={{ fontWeight: '600', color: '#333' }}>Horaires</div>
                    <div style={{ color: '#666', fontSize: '0.9em' }}>
                      {selectedEvent.start?.toLocaleString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })}
                      {selectedEvent.end && ` - ${selectedEvent.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`}
                    </div>
                  </div>
                </div>
                
                {selectedEvent.extendedProps?.type && (
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px', padding: '12px', background: '#f8f9fa', borderRadius: '8px' }}>
                    <span style={{ fontSize: '1.3em' }}>{getTypeInfo(selectedEvent.extendedProps.type).icon}</span>
                    <div>
                      <div style={{ fontWeight: '600', color: '#333' }}>Type</div>
                      <div style={{ color: '#666', fontSize: '0.9em' }}>{getTypeInfo(selectedEvent.extendedProps.type).label}</div>
                    </div>
                  </div>
                )}

                {selectedEvent.extendedProps?.location && (
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px', padding: '12px', background: '#f8f9fa', borderRadius: '8px' }}>
                    <span style={{ fontSize: '1.3em' }}>📍</span>
                    <div>
                      <div style={{ fontWeight: '600', color: '#333' }}>Lieu</div>
                      <div style={{ color: '#666', fontSize: '0.9em' }}>{selectedEvent.extendedProps.location}</div>
                    </div>
                  </div>
                )}

                {selectedEvent.extendedProps?.description && (
                  <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', padding: '12px', background: '#f8f9fa', borderRadius: '8px' }}>
                    <span style={{ fontSize: '1.3em' }}>💬</span>
                    <div>
                      <div style={{ fontWeight: '600', color: '#333' }}>Description</div>
                      <div style={{ color: '#666', fontSize: '0.9em', lineHeight: '1.5' }}>{selectedEvent.extendedProps.description}</div>
                    </div>
                  </div>
                )}
              </div>

              <div className="modal-actions" style={{ marginTop: '25px' }}>
                <button type="button" className="btn-cancel" onClick={() => setShowDetailsModal(false)}>
                  Fermer
                </button>
                <button 
                  type="button" 
                  className="btn-submit" 
                  style={{ background: '#0d6efd' }}
                  onClick={handleEditEvent}
                >
                  ✏️ Modifier
                </button>
                <button 
                  type="button" 
                  className="btn-cancel" 
                  style={{ background: '#dc3545' }}
                  onClick={handleDeleteEvent}
                >
                  🗑️ Supprimer
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Modifier événement */}
      {showEditEventModal && (
        <div className="modal-overlay" onClick={() => setShowEditEventModal(false)}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>✏️ Modifier l'événement</h2>
            </div>
            <form onSubmit={handleUpdateEvent}>
              <div className="modal-body">
                <div className="form-group">
                  <label>Titre *</label>
                  <input
                    type="text"
                    required
                    value={formData.title}
                    onChange={e => setFormData({ ...formData, title: e.target.value })}
                  />
                </div>
                <div className="form-group">
                  <label>Type</label>
                  <div className="color-picker">
                    {['course', 'meeting', 'exam', 'training', 'other'].map(type => {
                      const info = getTypeInfo(type)
                      return (
                        <button
                          key={type}
                          type="button"
                          className={`filter-btn ${formData.type === type ? 'active' : ''}`}
                          style={{ 
                            borderColor: formData.type === type ? info.color : '#e0e0e0',
                            background: formData.type === type ? `${info.color}15` : 'white'
                          }}
                          onClick={() => setFormData({ ...formData, type })}
                        >
                          {info.icon} {info.label}
                        </button>
                      )
                    })}
                  </div>
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
                  <div className="form-group">
                    <label>Début *</label>
                    <input
                      type="datetime-local"
                      required
                      value={formData.startDate}
                      onChange={e => setFormData({ ...formData, startDate: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label>Fin *</label>
                    <input
                      type="datetime-local"
                      required
                      value={formData.endDate}
                      onChange={e => setFormData({ ...formData, endDate: e.target.value })}
                    />
                  </div>
                </div>
                <div className="form-group">
                  <label>Lieu</label>
                  <input
                    type="text"
                    value={formData.location}
                    onChange={e => setFormData({ ...formData, location: e.target.value })}
                  />
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea
                    value={formData.description}
                    onChange={e => setFormData({ ...formData, description: e.target.value })}
                    rows={3}
                  />
                </div>
              </div>
              <div className="modal-body">
                <div className="modal-actions">
                  <button type="button" className="btn-cancel" onClick={() => setShowEditEventModal(false)}>
                    Annuler
                  </button>
                  <button type="submit" className="btn-submit">
                    💾 Enregistrer
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Confirmation de suppression */}
      {showDeleteConfirmModal && (
        <div className="modal-overlay" onClick={cancelDelete}>
          <div className="modal-content delete-confirm-modal" onClick={e => e.stopPropagation()}>
            <div className="modal-header delete-header">
              <div className="delete-icon-container">
                <span className="delete-icon">🗑️</span>
              </div>
              <h2>Confirmer la suppression</h2>
            </div>
            <div className="modal-body">
              <div className="delete-message">
                <p>Êtes-vous sûr de vouloir supprimer</p>
                <p className="delete-item-name">"{deleteTarget.name}"</p>
                {deleteTarget.type === 'calendar' && (
                  <p className="delete-warning">
                    ⚠️ Tous les événements de cet agenda seront également supprimés.
                  </p>
                )}
                {deleteTarget.type === 'event' && deleteTarget.isPartOfSeries && (
                  <p className="delete-warning recurring-warning">
                    🔄 Cet événement fait partie d'une série récurrente.
                  </p>
                )}
                <p className="delete-info">Cette action est irréversible.</p>
              </div>
              <div className="modal-actions delete-actions">
                <button type="button" className="btn-cancel" onClick={cancelDelete}>
                  Annuler
                </button>
                {/* Pour les événements récurrents, afficher deux options */}
                {deleteTarget.type === 'event' && deleteTarget.isPartOfSeries ? (
                  <>
                    <button 
                      type="button" 
                      className="btn-delete-single" 
                      onClick={confirmDeleteSingleEvent}
                    >
                      📅 Cette occurrence
                    </button>
                    <button 
                      type="button" 
                      className="btn-delete-confirm" 
                      onClick={confirmDeleteEventSeries}
                    >
                      🗑️ Toute la série
                    </button>
                  </>
                ) : (
                  <button type="button" className="btn-delete-confirm" onClick={confirmDelete}>
                    🗑️ Supprimer
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </Layout>
  )
}

export default CalendarPage
