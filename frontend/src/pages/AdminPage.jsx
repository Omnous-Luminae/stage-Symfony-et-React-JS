import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'
import { calendarService } from '../api/events'
import eventTypesApi from '../api/eventTypes'
import userRolesApi from '../api/userRoles'
import { useNotification } from '../context/NotificationContext'
import './AdminPage.css'

// Rôles par défaut (fallback si pas chargés depuis la BDD)
const DEFAULT_ROLES = ['Élève', 'Professeur', 'Personnel', 'Intervenant']
const STATUSES = ['Actif', 'Inactif']

function AdminPage() {
  const navigate = useNavigate()
  const { showSuccess, showError } = useNotification()
  
  // Admin state
  const [isAdmin, setIsAdmin] = useState(false)
  const [adminPermissions, setAdminPermissions] = useState({})
  const [loading, setLoading] = useState(true)
  
  // Data state
  const [stats, setStats] = useState(null)
  const [users, setUsers] = useState([])
  const [admins, setAdmins] = useState([])
  
  // Logs state - separated by category
  const [userLogs, setUserLogs] = useState([])
  const [userLogsPagination, setUserLogsPagination] = useState({ page: 1, totalPages: 1, total: 0 })
  const [calendarLogs, setCalendarLogs] = useState([])
  const [calendarLogsPagination, setCalendarLogsPagination] = useState({ page: 1, totalPages: 1, total: 0 })
  
  const [logsFilters, setLogsFilters] = useState({
    action: '',
    dateFrom: '',
    dateTo: ''
  })
  // UI state
  const [activeTab, setActiveTab] = useState('dashboard')
  const [searchTerm, setSearchTerm] = useState('')
  const [filterRole, setFilterRole] = useState('')
  const [filterStatus, setFilterStatus] = useState('')
  
  // Modal state
  const [showUserModal, setShowUserModal] = useState(false)
  const [showPromoteModal, setShowPromoteModal] = useState(false)
  const [editingUser, setEditingUser] = useState(null)
  const [userFormData, setUserFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    role: 'Intervenant',
    status: 'Actif',
    password: ''
  })
  const [promoteData, setPromoteData] = useState({
    userId: null,
    permissionLevel: 'Admin',
    canManageUsers: true,
    canManageCalendars: true,
    canManagePermissions: false,
    canViewAuditLogs: true
  })
  
  // Log details modal state
  const [showLogDetailsModal, setShowLogDetailsModal] = useState(false)
  const [selectedLog, setSelectedLog] = useState(null)
  
  // General calendar state
  const [generalCalendar, setGeneralCalendar] = useState(null)
  const [creatingGeneralCalendar, setCreatingGeneralCalendar] = useState(false)

  // Event types state
  const [eventTypes, setEventTypes] = useState([])
  const [showEventTypeModal, setShowEventTypeModal] = useState(false)
  const [editingEventType, setEditingEventType] = useState(null)
  const [eventTypeFormData, setEventTypeFormData] = useState({
    name: '',
    code: '',
    description: '',
    color: '#3788d8',
    icon: '',
    isActive: true,
    displayOrder: 0
  })

  // User roles state
  const [userRoles, setUserRoles] = useState([])
  const [availableRoles, setAvailableRoles] = useState(DEFAULT_ROLES)
  const [showUserRoleModal, setShowUserRoleModal] = useState(false)
  const [editingUserRole, setEditingUserRole] = useState(null)
  const [userRoleFormData, setUserRoleFormData] = useState({
    name: '',
    code: '',
    description: '',
    color: '#6366f1',
    icon: '',
    isActive: true,
    displayOrder: 0,
    canCreateEvents: true,
    canCreatePublicEvents: false,
    canShareCalendars: true
  })

  // Check admin status
  useEffect(() => {
    const checkAdmin = async () => {
      try {
        const response = await api.get('/admin/check')
        if (response.data.isAdmin) {
          setIsAdmin(true)
          setAdminPermissions(response.data.permissions || {})
        } else {
          navigate('/')
          showError("Accès réservé aux administrateurs")
        }
      } catch (error) {
        navigate('/')
        showError("Erreur de vérification des droits")
      } finally {
        setLoading(false)
      }
    }
    checkAdmin()
  }, [navigate, showError])

  // Load data
  const loadStats = useCallback(async () => {
    try {
      const response = await api.get('/admin/stats')
      setStats(response.data)
    } catch (error) {
      console.error('Error loading stats:', error)
    }
  }, [])

  const loadUsers = useCallback(async () => {
    try {
      const response = await api.get('/admin/users')
      setUsers(response.data)
    } catch (error) {
      console.error('Error loading users:', error)
    }
  }, [])

  const loadAdmins = useCallback(async () => {
    try {
      const response = await api.get('/admin/administrators')
      setAdmins(response.data)
    } catch (error) {
      console.error('Error loading admins:', error)
    }
  }, [])

  // Load general calendar status
  const loadGeneralCalendar = useCallback(async () => {
    try {
      const response = await calendarService.getGeneralCalendar()
      setGeneralCalendar(response.data.calendar)
    } catch (error) {
      if (error.response?.status === 404) {
        setGeneralCalendar(null)
      } else {
        console.error('Error loading general calendar:', error)
      }
    }
  }, [])

  // Create general calendar
  const handleCreateGeneralCalendar = async () => {
    setCreatingGeneralCalendar(true)
    try {
      const response = await calendarService.initGeneralCalendar()
      setGeneralCalendar(response.data.calendar)
      showSuccess('Calendrier général créé avec succès !')
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la création du calendrier général')
    } finally {
      setCreatingGeneralCalendar(false)
    }
  }

  // Load event types
  const loadEventTypes = useCallback(async () => {
    try {
      // Essayer d'abord l'API admin (avec infos complètes)
      const data = await eventTypesApi.getAllAdmin()
      setEventTypes(data)
    } catch (error) {
      console.error('Error loading event types (admin):', error)
      // Fallback sur l'API publique
      try {
        const data = await eventTypesApi.getAll()
        // Ajouter les champs manquants avec des valeurs par défaut
        const enrichedData = data.map(type => ({
          ...type,
          isActive: true,
          eventsCount: 0
        }))
        setEventTypes(enrichedData)
      } catch (fallbackError) {
        console.error('Error loading event types (public):', fallbackError)
      }
    }
  }, [])

  // Load user roles
  const loadUserRoles = useCallback(async () => {
    try {
      // Essayer d'abord l'API admin (avec infos complètes)
      const data = await userRolesApi.getAllAdmin()
      setUserRoles(data)
      // Mettre à jour les rôles disponibles pour le filtre utilisateur
      setAvailableRoles(data.filter(r => r.isActive).map(r => r.name))
    } catch (error) {
      console.error('Error loading user roles (admin):', error)
      // Fallback sur l'API publique
      try {
        const data = await userRolesApi.getAll()
        const enrichedData = data.map(role => ({
          ...role,
          isActive: true,
          usersCount: 0
        }))
        setUserRoles(enrichedData)
        setAvailableRoles(data.map(r => r.name))
      } catch (fallbackError) {
        console.error('Error loading user roles (public):', fallbackError)
        // Utiliser les rôles par défaut
        setAvailableRoles(DEFAULT_ROLES)
      }
    }
  }, [])

  // Load user logs (user + administrator entities)
  const loadUserLogs = useCallback(async (page = 1) => {
    try {
      const params = new URLSearchParams({ page, limit: 20 })
      params.append('entityType', 'user,administrator')
      if (logsFilters.action) params.append('action', logsFilters.action)
      if (logsFilters.dateFrom) params.append('dateFrom', logsFilters.dateFrom)
      if (logsFilters.dateTo) params.append('dateTo', logsFilters.dateTo)
      
      const response = await api.get(`/admin/logs?${params.toString()}`)
      setUserLogs(response.data.data)
      setUserLogsPagination(response.data.pagination)
    } catch (error) {
      console.error('Error loading user logs:', error)
    }
  }, [logsFilters])

  // Load calendar/event logs
  const loadCalendarLogs = useCallback(async (page = 1) => {
    try {
      const params = new URLSearchParams({ page, limit: 20 })
      params.append('entityType', 'calendar,event')
      if (logsFilters.action) params.append('action', logsFilters.action)
      if (logsFilters.dateFrom) params.append('dateFrom', logsFilters.dateFrom)
      if (logsFilters.dateTo) params.append('dateTo', logsFilters.dateTo)
      
      const response = await api.get(`/admin/logs?${params.toString()}`)
      setCalendarLogs(response.data.data)
      setCalendarLogsPagination(response.data.pagination)
    } catch (error) {
      console.error('Error loading calendar logs:', error)
    }
  }, [logsFilters])

  // Effet pour recharger les logs quand les filtres changent
  useEffect(() => {
    if (isAdmin && adminPermissions.canViewAuditLogs) {
      loadUserLogs(1)
      loadCalendarLogs(1)
    }
  }, [logsFilters, isAdmin, adminPermissions.canViewAuditLogs]) // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => {
    if (isAdmin) {
      loadStats()
      loadUsers()
      loadAdmins()
      loadGeneralCalendar()
      loadEventTypes()
      loadUserRoles()
    }
  }, [isAdmin, loadStats, loadUsers, loadAdmins, loadGeneralCalendar, loadEventTypes, loadUserRoles])

  // Filtered users
  const filteredUsers = users.filter(user => {
    const matchesSearch = 
      user.firstName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.lastName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      user.email.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesRole = !filterRole || user.role === filterRole
    const matchesStatus = !filterStatus || user.status === filterStatus
    return matchesSearch && matchesRole && matchesStatus
  })

  // User CRUD
  const openCreateUserModal = () => {
    setEditingUser(null)
    setUserFormData({
      firstName: '',
      lastName: '',
      email: '',
      role: 'Intervenant',
      status: 'Actif',
      password: ''
    })
    setShowUserModal(true)
  }

  const openEditUserModal = (user) => {
    setEditingUser(user)
    setUserFormData({
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      role: user.role,
      status: user.status,
      password: ''
    })
    setShowUserModal(true)
  }

  const handleUserSubmit = async (e) => {
    e.preventDefault()
    try {
      if (editingUser) {
        await api.put(`/admin/users/${editingUser.id}`, userFormData)
        showSuccess('Utilisateur mis à jour')
      } else {
        await api.post('/admin/users', userFormData)
        showSuccess('Utilisateur créé')
      }
      setShowUserModal(false)
      loadUsers()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la sauvegarde')
    }
  }

  const handleDeleteUser = async (user) => {
    if (!confirm(`Supprimer ${user.firstName} ${user.lastName} ?`)) return
    try {
      await api.delete(`/admin/users/${user.id}`)
      showSuccess('Utilisateur supprimé')
      loadUsers()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la suppression')
    }
  }

  // Admin promotion
  const openPromoteModal = (user) => {
    setPromoteData({
      userId: user.id,
      userName: `${user.firstName} ${user.lastName}`,
      permissionLevel: 'Admin',
      canManageUsers: true,
      canManageCalendars: true,
      canManagePermissions: false,
      canViewAuditLogs: true
    })
    setShowPromoteModal(true)
  }

  const handlePromote = async (e) => {
    e.preventDefault()
    try {
      await api.post(`/admin/users/${promoteData.userId}/promote`, promoteData)
      showSuccess('Utilisateur promu administrateur')
      setShowPromoteModal(false)
      loadUsers()
      loadAdmins()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la promotion')
    }
  }

  const handleDemote = async (userId) => {
    if (!confirm('Rétrograder cet administrateur ?')) return
    try {
      await api.post(`/admin/users/${userId}/demote`)
      showSuccess('Administrateur rétrogradé')
      loadUsers()
      loadAdmins()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la rétrogradation')
    }
  }

  const toggleUserStatus = async (user) => {
    const newStatus = user.status === 'Actif' ? 'Inactif' : 'Actif'
    try {
      await api.put(`/admin/users/${user.id}`, { status: newStatus })
      showSuccess(`Utilisateur ${newStatus === 'Actif' ? 'activé' : 'désactivé'}`)
      loadUsers()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur')
    }
  }

  // Undo action from logs
  const handleUndoAction = async (log, logType) => {
    const actionLabels = {
      create: 'la création',
      update: 'la modification',
      delete: 'la suppression'
    }
    const actionLabel = actionLabels[log.action] || log.action
    
    if (!confirm(`Annuler ${actionLabel} de ${log.entityTypeLabel} #${log.entityId} ?`)) return
    
    try {
      const response = await api.post(`/admin/logs/${log.id}/undo`)
      showSuccess(response.data.message || 'Action annulée avec succès')
      
      // Refresh the appropriate table
      if (logType === 'user') {
        loadUserLogs(userLogsPagination.page)
      } else {
        loadCalendarLogs(calendarLogsPagination.page)
      }
      // Refresh stats as entities may have changed
      loadStats()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de l\'annulation')
    }
  }

  // Check if an action can be undone
  const canUndo = (log) => {
    const undoableActions = ['create', 'update', 'delete', 'promote', 'demote', 'permission_change']
    const undoableEntities = ['user', 'calendar', 'event', 'administrator']
    return undoableActions.includes(log.action) && undoableEntities.includes(log.entityType)
  }

  // Open log details modal
  const openLogDetails = (log) => {
    setSelectedLog(log)
    setShowLogDetailsModal(true)
  }

  // Event Type management
  const openEventTypeModal = (eventType = null) => {
    if (eventType) {
      setEditingEventType(eventType)
      setEventTypeFormData({
        name: eventType.name,
        code: eventType.code,
        description: eventType.description || '',
        color: eventType.color || '#3788d8',
        icon: eventType.icon || '',
        isActive: eventType.isActive,
        displayOrder: eventType.displayOrder || 0
      })
    } else {
      setEditingEventType(null)
      setEventTypeFormData({
        name: '',
        code: '',
        description: '',
        color: '#3788d8',
        icon: '',
        isActive: true,
        displayOrder: eventTypes.length + 1
      })
    }
    setShowEventTypeModal(true)
  }

  const handleEventTypeSubmit = async (e) => {
    e.preventDefault()
    try {
      if (editingEventType) {
        await eventTypesApi.update(editingEventType.id, eventTypeFormData)
        showSuccess('Type d\'événement mis à jour')
      } else {
        await eventTypesApi.create(eventTypeFormData)
        showSuccess('Type d\'événement créé')
      }
      setShowEventTypeModal(false)
      loadEventTypes()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la sauvegarde')
    }
  }

  const handleDeleteEventType = async (eventType) => {
    if (!confirm(`Supprimer le type "${eventType.name}" ? Cette action est irréversible.`)) return
    try {
      await eventTypesApi.delete(eventType.id)
      showSuccess('Type d\'événement supprimé')
      loadEventTypes()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la suppression')
    }
  }

  const handleToggleEventType = async (eventType) => {
    try {
      await eventTypesApi.toggleActive(eventType.id)
      showSuccess(eventType.isActive ? 'Type désactivé' : 'Type activé')
      loadEventTypes()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur')
    }
  }

  // User Role management
  const openUserRoleModal = (role = null) => {
    if (role) {
      setEditingUserRole(role)
      setUserRoleFormData({
        name: role.name,
        code: role.code,
        description: role.description || '',
        color: role.color || '#6366f1',
        icon: role.icon || '',
        isActive: role.isActive,
        displayOrder: role.displayOrder || 0,
        canCreateEvents: role.canCreateEvents ?? true,
        canCreatePublicEvents: role.canCreatePublicEvents ?? false,
        canShareCalendars: role.canShareCalendars ?? true
      })
    } else {
      setEditingUserRole(null)
      setUserRoleFormData({
        name: '',
        code: '',
        description: '',
        color: '#6366f1',
        icon: '',
        isActive: true,
        displayOrder: userRoles.length + 1,
        canCreateEvents: true,
        canCreatePublicEvents: false,
        canShareCalendars: true
      })
    }
    setShowUserRoleModal(true)
  }

  const handleUserRoleSubmit = async (e) => {
    e.preventDefault()
    try {
      if (editingUserRole) {
        await userRolesApi.update(editingUserRole.id, userRoleFormData)
        showSuccess('Rôle mis à jour')
      } else {
        await userRolesApi.create(userRoleFormData)
        showSuccess('Rôle créé')
      }
      setShowUserRoleModal(false)
      loadUserRoles()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la sauvegarde')
    }
  }

  const handleDeleteUserRole = async (role) => {
    if (role.isSystem) {
      showError('Les rôles système ne peuvent pas être supprimés')
      return
    }
    if (!confirm(`Supprimer le rôle "${role.name}" ? Cette action est irréversible.`)) return
    try {
      await userRolesApi.delete(role.id)
      showSuccess('Rôle supprimé')
      loadUserRoles()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur lors de la suppression')
    }
  }

  const handleToggleUserRole = async (role) => {
    if (role.isSystem) {
      showError('Les rôles système ne peuvent pas être désactivés')
      return
    }
    try {
      await userRolesApi.update(role.id, { isActive: !role.isActive })
      showSuccess(role.isActive ? 'Rôle désactivé' : 'Rôle activé')
      loadUserRoles()
    } catch (error) {
      showError(error.response?.data?.error || 'Erreur')
    }
  }

  // Generate code from name
  const generateCodeFromName = (name) => {
    return name
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '') // Remove accents
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '')
  }
  if (loading) {
    return (
      <div className="admin-page">
        <div className="admin-loading">
          <div className="loading-spinner"></div>
          <p>Vérification des droits...</p>
        </div>
      </div>
    )
  }

  if (!isAdmin) {
    return null
  }

  return (
    <div className="admin-page">
      <header className="admin-header">
        <div className="admin-header-content">
          <div className="admin-header-top">
            <div>
              <h1>🛡️ Administration</h1>
              <p>Gérez les utilisateurs et les paramètres du système</p>
            </div>
            <button 
              className="btn-back-calendar"
              onClick={() => navigate('/calendar')}
            >
              📅 Retour au calendrier
            </button>
          </div>
        </div>
      </header>

      <div className="admin-container">
        {/* Sidebar Tabs */}
        <aside className="admin-sidebar">
          <nav className="admin-nav">
            <button
              className={`admin-nav-item ${activeTab === 'dashboard' ? 'active' : ''}`}
              onClick={() => setActiveTab('dashboard')}
            >
              📊 Tableau de bord
            </button>
            {adminPermissions.canManageUsers && (
              <button
                className={`admin-nav-item ${activeTab === 'users' ? 'active' : ''}`}
                onClick={() => setActiveTab('users')}
              >
                👥 Utilisateurs
              </button>
            )}
            {adminPermissions.canManagePermissions && (
              <button
                className={`admin-nav-item ${activeTab === 'admins' ? 'active' : ''}`}
                onClick={() => setActiveTab('admins')}
              >
                🔐 Administrateurs
              </button>
            )}
            {adminPermissions.canViewAuditLogs && (
              <button
                className={`admin-nav-item ${activeTab === 'logs' ? 'active' : ''}`}
                onClick={() => setActiveTab('logs')}
              >
                📋 Journaux d'audit
              </button>
            )}
            <button
              className={`admin-nav-item ${activeTab === 'eventTypes' ? 'active' : ''}`}
              onClick={() => setActiveTab('eventTypes')}
            >
              🏷️ Types d'événements
            </button>
            {adminPermissions.canManageUsers && (
              <button
                className={`admin-nav-item ${activeTab === 'userRoles' ? 'active' : ''}`}
                onClick={() => setActiveTab('userRoles')}
              >
                🎭 Rôles utilisateur
              </button>
            )}
          </nav>
        </aside>

        {/* Main Content */}
        <main className="admin-main">
          {/* Dashboard Tab */}
          {activeTab === 'dashboard' && stats && (
            <div className="admin-dashboard">
              <h2>📊 Vue d'ensemble</h2>
              
              <div className="stats-grid">
                <div className="stat-card">
                  <div className="stat-icon">👥</div>
                  <div className="stat-content">
                    <span className="stat-value">{stats.totalUsers}</span>
                    <span className="stat-label">Utilisateurs</span>
                  </div>
                </div>
                <div className="stat-card">
                  <div className="stat-icon">📅</div>
                  <div className="stat-content">
                    <span className="stat-value">{stats.totalCalendars}</span>
                    <span className="stat-label">Calendriers</span>
                  </div>
                </div>
                <div className="stat-card">
                  <div className="stat-icon">📌</div>
                  <div className="stat-content">
                    <span className="stat-value">{stats.totalEvents}</span>
                    <span className="stat-label">Événements</span>
                  </div>
                </div>
                <div className="stat-card">
                  <div className="stat-icon">🛡️</div>
                  <div className="stat-content">
                    <span className="stat-value">{stats.totalAdmins}</span>
                    <span className="stat-label">Administrateurs</span>
                  </div>
                </div>
              </div>

              <div className="stats-details">
                <div className="stats-section">
                  <h3>État des utilisateurs</h3>
                  <div className="status-bars">
                    <div className="status-bar">
                      <span className="status-label">Actifs</span>
                      <div className="bar-container">
                        <div 
                          className="bar bar-active" 
                          style={{ width: `${(stats.activeUsers / stats.totalUsers) * 100}%` }}
                        ></div>
                      </div>
                      <span className="status-count">{stats.activeUsers}</span>
                    </div>
                    <div className="status-bar">
                      <span className="status-label">Inactifs</span>
                      <div className="bar-container">
                        <div 
                          className="bar bar-inactive" 
                          style={{ width: `${(stats.inactiveUsers / stats.totalUsers) * 100}%` }}
                        ></div>
                      </div>
                      <span className="status-count">{stats.inactiveUsers}</span>
                    </div>
                  </div>
                </div>

                <div className="stats-section">
                  <h3>Répartition par rôle</h3>
                  <div className="role-list">
                    {stats.usersByRole?.map(item => (
                      <div key={item.role} className="role-item">
                        <span className="role-name">{item.role}</span>
                        <span className="role-count">{item.count}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {/* Section Calendrier Général */}
              <div className="general-calendar-section">
                <h3>📢 Calendrier Général</h3>
                <p className="section-description">
                  Le calendrier général est visible par tous les utilisateurs. 
                  Seuls les administrateurs peuvent y créer des événements.
                </p>
                
                {generalCalendar ? (
                  <div className="general-calendar-info">
                    <div className="calendar-badge" style={{ borderLeftColor: generalCalendar.color }}>
                      <span className="calendar-icon">📅</span>
                      <div className="calendar-details">
                        <span className="calendar-name">{generalCalendar.name}</span>
                        <span className="calendar-desc">{generalCalendar.description}</span>
                      </div>
                      <span className="calendar-status active">✅ Actif</span>
                    </div>
                    <p className="calendar-hint">
                      💡 Pour ajouter des événements, allez sur le calendrier et sélectionnez "📢 Calendrier Général" dans la liste.
                    </p>
                  </div>
                ) : (
                  <div className="general-calendar-create">
                    <p className="no-calendar-message">
                      ⚠️ Le calendrier général n'a pas encore été créé.
                    </p>
                    <button 
                      className="btn-action primary"
                      onClick={handleCreateGeneralCalendar}
                      disabled={creatingGeneralCalendar}
                    >
                      {creatingGeneralCalendar ? '⏳ Création...' : '➕ Créer le calendrier général'}
                    </button>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Users Tab */}
          {activeTab === 'users' && adminPermissions.canManageUsers && (
            <div className="admin-users">
              <div className="users-header">
                <h2>👥 Gestion des utilisateurs</h2>
                <button className="btn-action primary" onClick={openCreateUserModal}>
                  ➕ Nouvel utilisateur
                </button>
              </div>

              <div className="users-filters">
                <input
                  type="text"
                  placeholder="🔍 Rechercher..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="search-input"
                />
                <select 
                  value={filterRole} 
                  onChange={(e) => setFilterRole(e.target.value)}
                  className="filter-select"
                >
                  <option value="">Tous les rôles</option>
                  {availableRoles.map(role => (
                    <option key={role} value={role}>{role}</option>
                  ))}
                </select>
                <select 
                  value={filterStatus} 
                  onChange={(e) => setFilterStatus(e.target.value)}
                  className="filter-select"
                >
                  <option value="">Tous les statuts</option>
                  {STATUSES.map(status => (
                    <option key={status} value={status}>{status}</option>
                  ))}
                </select>
              </div>

              <div className="users-table-container">
                <table className="users-table">
                  <thead>
                    <tr>
                      <th>Nom</th>
                      <th>Email</th>
                      <th>Rôle</th>
                      <th>Statut</th>
                      <th>Admin</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredUsers.map(user => (
                      <tr key={user.id}>
                        <td>
                          <div className="user-name">
                            <span className="user-avatar">
                              {user.firstName[0]}{user.lastName[0]}
                            </span>
                            {user.firstName} {user.lastName}
                          </div>
                        </td>
                        <td>{user.email}</td>
                        <td>
                          {user.isAdmin ? (
                            <span className={`role-badge role-${user.adminLevel === 'Super_Admin' ? 'super-admin' : 'admin'}`}>
                              {user.adminLevel === 'Super_Admin' ? '👑 Super-Admin' : '🛡️ Admin'}
                            </span>
                          ) : (
                            <span className={`role-badge role-${user.role.toLowerCase()}`}>
                              {user.role}
                            </span>
                          )}
                        </td>
                        <td>
                          <span className={`status-badge status-${user.status.toLowerCase()}`}>
                            {user.status}
                          </span>
                        </td>
                        <td>
                          {user.isAdmin ? (
                            <span className="admin-badge" title={user.adminLevel === 'Super_Admin' ? 'Super Admin' : 'Admin'}>
                              ✅
                            </span>
                          ) : '-'}
                        </td>
                        <td>
                          <div className="action-buttons">
                            <button 
                              className="btn-icon" 
                              title="Modifier"
                              onClick={() => openEditUserModal(user)}
                            >
                              ✏️
                            </button>
                            <button 
                              className="btn-icon" 
                              title={user.status === 'Actif' ? 'Désactiver' : 'Activer'}
                              onClick={() => toggleUserStatus(user)}
                            >
                              {user.status === 'Actif' ? '🔒' : '🔓'}
                            </button>
                            {!user.isAdmin && adminPermissions.canManagePermissions && (
                              <button 
                                className="btn-icon" 
                                title="Promouvoir admin"
                                onClick={() => openPromoteModal(user)}
                              >
                                ⬆️
                              </button>
                            )}
                            {user.isAdmin && user.adminLevel !== 'Super_Admin' && adminPermissions.canManagePermissions && (
                              <button 
                                className="btn-icon" 
                                title="Rétrograder"
                                onClick={() => handleDemote(user.id)}
                              >
                                ⬇️
                              </button>
                            )}
                            <button 
                              className="btn-icon danger" 
                              title="Supprimer"
                              onClick={() => handleDeleteUser(user)}
                            >
                              🗑️
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {filteredUsers.length === 0 && (
                  <div className="no-results">Aucun utilisateur trouvé</div>
                )}
              </div>
            </div>
          )}

          {/* Admins Tab */}
          {activeTab === 'admins' && adminPermissions.canManagePermissions && (
            <div className="admin-administrators">
              <h2>🔐 Administrateurs</h2>
              
              <div className="admins-grid">
                {admins.map(admin => (
                  <div key={admin.id} className="admin-card">
                    <div className="admin-card-header">
                      <span className="admin-avatar">
                        {admin.user.firstName[0]}{admin.user.lastName[0]}
                      </span>
                      <div className="admin-info">
                        <h3>{admin.user.firstName} {admin.user.lastName}</h3>
                        <p>{admin.user.email}</p>
                      </div>
                      <span className={`level-badge level-${admin.permissionLevel.toLowerCase().replace('_', '-')}`}>
                        {admin.permissionLevel === 'Super_Admin' ? '👑 Super Admin' : '🛡️ Admin'}
                      </span>
                    </div>
                    <div className="admin-card-permissions">
                      <h4>Permissions</h4>
                      <ul>
                        <li className={admin.canManageUsers ? 'enabled' : 'disabled'}>
                          {admin.canManageUsers ? '✅' : '❌'} Gérer les utilisateurs
                        </li>
                        <li className={admin.canManageCalendars ? 'enabled' : 'disabled'}>
                          {admin.canManageCalendars ? '✅' : '❌'} Gérer les calendriers
                        </li>
                        <li className={admin.canManagePermissions ? 'enabled' : 'disabled'}>
                          {admin.canManagePermissions ? '✅' : '❌'} Gérer les permissions
                        </li>
                        <li className={admin.canViewAuditLogs ? 'enabled' : 'disabled'}>
                          {admin.canViewAuditLogs ? '✅' : '❌'} Voir les logs
                        </li>
                      </ul>
                    </div>
                    {admin.lastLogin && (
                      <div className="admin-card-footer">
                        Dernière connexion: {new Date(admin.lastLogin).toLocaleString('fr-FR')}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Logs Tab */}
          {activeTab === 'logs' && adminPermissions.canViewAuditLogs && (
            <div className="admin-logs">
              <h2>📋 Journaux d'audit</h2>
              
              {/* Common Filters */}
              <div className="logs-filters">
                <select
                  value={logsFilters.action}
                  onChange={(e) => setLogsFilters({...logsFilters, action: e.target.value})}
                  className="filter-select"
                >
                  <option value="">Toutes les actions</option>
                  <option value="create">Création</option>
                  <option value="update">Modification</option>
                  <option value="delete">Suppression</option>
                  <option value="promote">Promotion</option>
                  <option value="demote">Rétrogradation</option>
                  <option value="permission_change">Changement permissions</option>
                  <option value="undo">Annulation</option>
                </select>
                <input
                  type="date"
                  value={logsFilters.dateFrom}
                  onChange={(e) => setLogsFilters({...logsFilters, dateFrom: e.target.value})}
                  className="filter-date"
                  placeholder="Date début"
                />
                <input
                  type="date"
                  value={logsFilters.dateTo}
                  onChange={(e) => setLogsFilters({...logsFilters, dateTo: e.target.value})}
                  className="filter-date"
                  placeholder="Date fin"
                />
                <button 
                  className="btn-action secondary"
                  onClick={() => setLogsFilters({ action: '', dateFrom: '', dateTo: '' })}
                >
                  🔄 Réinitialiser
                </button>
              </div>

              {/* User Logs Section */}
              <div className="logs-section">
                <h3>👤 Logs Utilisateurs & Administrateurs</h3>
                <div className="logs-stats">
                  <span>📊 {userLogsPagination.total} entrées</span>
                </div>

                <div className="logs-table-container">
                  <table className="logs-table">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Administrateur</th>
                        <th>Action</th>
                        <th>Type</th>
                        <th>ID</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {userLogs.map(log => (
                        <tr key={log.id} className={`log-row action-${log.action}`}>
                          <td className="log-date">
                            {new Date(log.createdAt).toLocaleString('fr-FR', {
                              day: '2-digit',
                              month: '2-digit',
                              year: 'numeric',
                              hour: '2-digit',
                              minute: '2-digit'
                            })}
                          </td>
                          <td>
                            <span className={`log-performer ${log.isAdminAction ? 'is-admin' : 'is-user'}`}>
                              {log.performerName || 'Système'}
                              {log.isAdminAction && <span className="admin-badge" title="Action admin">👑</span>}
                            </span>
                          </td>
                          <td>
                            <span className={`action-badge action-${log.action}`}>
                              {log.actionLabel}
                            </span>
                          </td>
                          <td>
                            <span className="entity-badge">
                              {log.entityTypeLabel}
                            </span>
                          </td>
                          <td className="log-entity-id">
                            {log.entityId || '-'}
                          </td>
                          <td>
                            <div className="log-actions">
                              <button 
                                className="btn-icon"
                                title="Voir les détails"
                                onClick={() => openLogDetails(log)}
                              >
                                🔍
                              </button>
                              {canUndo(log) && (
                                <button 
                                  className="btn-icon undo"
                                  title="Annuler cette action"
                                  onClick={() => handleUndoAction(log, 'user')}
                                >
                                  ↩️
                                </button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  {userLogs.length === 0 && (
                    <div className="no-results">Aucun log utilisateur trouvé</div>
                  )}
                </div>

                {userLogsPagination.totalPages > 1 && (
                  <div className="logs-pagination">
                    <button
                      className="btn-action secondary"
                      disabled={userLogsPagination.page <= 1}
                      onClick={() => loadUserLogs(userLogsPagination.page - 1)}
                    >
                      ← Précédent
                    </button>
                    <span className="pagination-info">
                      Page {userLogsPagination.page} sur {userLogsPagination.totalPages}
                    </span>
                    <button
                      className="btn-action secondary"
                      disabled={userLogsPagination.page >= userLogsPagination.totalPages}
                      onClick={() => loadUserLogs(userLogsPagination.page + 1)}
                    >
                      Suivant →
                    </button>
                  </div>
                )}
              </div>

              {/* Calendar/Event Logs Section */}
              <div className="logs-section">
                <h3>📅 Logs Calendriers & Événements</h3>
                <div className="logs-stats">
                  <span>📊 {calendarLogsPagination.total} entrées</span>
                </div>

                <div className="logs-table-container">
                  <table className="logs-table">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Administrateur</th>
                        <th>Action</th>
                        <th>Type</th>
                        <th>ID</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {calendarLogs.map(log => (
                        <tr key={log.id} className={`log-row action-${log.action}`}>
                          <td className="log-date">
                            {new Date(log.createdAt).toLocaleString('fr-FR', {
                              day: '2-digit',
                              month: '2-digit',
                              year: 'numeric',
                              hour: '2-digit',
                              minute: '2-digit'
                            })}
                          </td>
                          <td>
                            <span className={`log-performer ${log.isAdminAction ? 'is-admin' : 'is-user'}`}>
                              {log.performerName || 'Système'}
                              {log.isAdminAction && <span className="admin-badge" title="Action admin">👑</span>}
                            </span>
                          </td>
                          <td>
                            <span className={`action-badge action-${log.action}`}>
                              {log.actionLabel}
                            </span>
                          </td>
                          <td>
                            <span className="entity-badge">
                              {log.entityTypeLabel}
                            </span>
                          </td>
                          <td className="log-entity-id">
                            {log.entityId || '-'}
                          </td>
                          <td>
                            <div className="log-actions">
                              <button 
                                className="btn-icon"
                                title="Voir les détails"
                                onClick={() => openLogDetails(log)}
                              >
                                🔍
                              </button>
                              {canUndo(log) && (
                                <button 
                                  className="btn-icon undo"
                                  title="Annuler cette action"
                                  onClick={() => handleUndoAction(log, 'calendar')}
                                >
                                  ↩️
                                </button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  {calendarLogs.length === 0 && (
                    <div className="no-results">Aucun log calendrier/événement trouvé</div>
                  )}
                </div>

                {calendarLogsPagination.totalPages > 1 && (
                  <div className="logs-pagination">
                    <button
                      className="btn-action secondary"
                      disabled={calendarLogsPagination.page <= 1}
                      onClick={() => loadCalendarLogs(calendarLogsPagination.page - 1)}
                    >
                      ← Précédent
                    </button>
                    <span className="pagination-info">
                      Page {calendarLogsPagination.page} sur {calendarLogsPagination.totalPages}
                    </span>
                    <button
                      className="btn-action secondary"
                      disabled={calendarLogsPagination.page >= calendarLogsPagination.totalPages}
                      onClick={() => loadCalendarLogs(calendarLogsPagination.page + 1)}
                    >
                      Suivant →
                    </button>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Event Types Tab */}
          {activeTab === 'eventTypes' && (
            <div className="admin-event-types">
              <div className="section-header">
                <h2>🏷️ Types d'événements</h2>
                <button 
                  className="btn-action primary"
                  onClick={() => openEventTypeModal()}
                >
                  ➕ Nouveau type
                </button>
              </div>

              <p className="section-description">
                Gérez les types d'événements disponibles dans l'application. Chaque type peut avoir une couleur et une icône personnalisées.
              </p>

              <div className="event-types-grid">
                {eventTypes.map(eventType => (
                  <div 
                    key={eventType.id} 
                    className={`event-type-card ${!eventType.isActive ? 'inactive' : ''}`}
                    style={{ borderLeftColor: eventType.color }}
                  >
                    <div className="event-type-header">
                      <div className="event-type-info">
                        <span className="event-type-icon">{eventType.icon || '📌'}</span>
                        <div>
                          <h3>{eventType.name}</h3>
                          <code className="event-type-code">{eventType.code}</code>
                        </div>
                      </div>
                      <div 
                        className="event-type-color" 
                        style={{ backgroundColor: eventType.color }}
                        title={eventType.color}
                      />
                    </div>
                    
                    {eventType.description && (
                      <p className="event-type-description">{eventType.description}</p>
                    )}

                    <div className="event-type-meta">
                      <span className={`status-badge ${eventType.isActive ? 'active' : 'inactive'}`}>
                        {eventType.isActive ? '✅ Actif' : '⏸️ Inactif'}
                      </span>
                      <span className="event-type-count">
                        {eventType.eventsCount || 0} événement(s)
                      </span>
                    </div>

                    <div className="event-type-actions">
                      <button 
                        className="btn-icon"
                        title="Modifier"
                        onClick={() => openEventTypeModal(eventType)}
                      >
                        ✏️
                      </button>
                      <button 
                        className="btn-icon"
                        title={eventType.isActive ? 'Désactiver' : 'Activer'}
                        onClick={() => handleToggleEventType(eventType)}
                      >
                        {eventType.isActive ? '⏸️' : '▶️'}
                      </button>
                      <button 
                        className="btn-icon danger"
                        title="Supprimer"
                        onClick={() => handleDeleteEventType(eventType)}
                        disabled={eventType.eventsCount > 0}
                      >
                        🗑️
                      </button>
                    </div>
                  </div>
                ))}
              </div>

              {eventTypes.length === 0 && (
                <div className="no-results">
                  Aucun type d'événement configuré. Cliquez sur "Nouveau type" pour en créer un.
                </div>
              )}
            </div>
          )}

          {/* User Roles Tab */}
          {activeTab === 'userRoles' && (
            <div className="admin-user-roles">
              <div className="section-header">
                <h2>🎭 Rôles utilisateur</h2>
                <button 
                  className="btn-action primary"
                  onClick={() => openUserRoleModal()}
                >
                  ➕ Nouveau rôle
                </button>
              </div>

              <p className="section-description">
                Gérez les rôles disponibles pour les utilisateurs. Chaque rôle définit les permissions de base de l'utilisateur.
              </p>

              <div className="user-roles-grid">
                {userRoles.map(role => (
                  <div 
                    key={role.id} 
                    className={`user-role-card ${!role.isActive ? 'inactive' : ''} ${role.isSystem ? 'system' : ''}`}
                    style={{ borderLeftColor: role.color }}
                  >
                    <div className="user-role-header">
                      <div className="user-role-info">
                        <span className="user-role-icon">{role.icon || '👤'}</span>
                        <div>
                          <h3>{role.name}</h3>
                          <code className="user-role-code">{role.code}</code>
                        </div>
                      </div>
                      <div 
                        className="user-role-color" 
                        style={{ backgroundColor: role.color }}
                        title={role.color}
                      />
                    </div>
                    
                    {role.description && (
                      <p className="user-role-description">{role.description}</p>
                    )}

                    <div className="user-role-permissions">
                      <span className={`permission-badge ${role.canCreateEvents ? 'active' : 'inactive'}`}>
                        {role.canCreateEvents ? '✅' : '❌'} Créer événements
                      </span>
                      <span className={`permission-badge ${role.canCreatePublicEvents ? 'active' : 'inactive'}`}>
                        {role.canCreatePublicEvents ? '✅' : '❌'} Événements publics
                      </span>
                      <span className={`permission-badge ${role.canShareCalendars ? 'active' : 'inactive'}`}>
                        {role.canShareCalendars ? '✅' : '❌'} Partager calendriers
                      </span>
                    </div>

                    <div className="user-role-meta">
                      <span className={`status-badge ${role.isActive ? 'active' : 'inactive'}`}>
                        {role.isActive ? '✅ Actif' : '⏸️ Inactif'}
                      </span>
                      {role.isSystem && (
                        <span className="system-badge" title="Rôle système - non modifiable">
                          🔒 Système
                        </span>
                      )}
                      <span className="user-role-count">
                        {role.usersCount || 0} utilisateur(s)
                      </span>
                    </div>

                    <div className="user-role-actions">
                      <button 
                        className="btn-icon"
                        title={role.isSystem ? "Les rôles système ne sont pas modifiables" : "Modifier"}
                        onClick={() => openUserRoleModal(role)}
                        disabled={role.isSystem}
                      >
                        ✏️
                      </button>
                      <button 
                        className="btn-icon"
                        title={role.isSystem ? "Les rôles système ne peuvent pas être désactivés" : (role.isActive ? 'Désactiver' : 'Activer')}
                        onClick={() => handleToggleUserRole(role)}
                        disabled={role.isSystem}
                      >
                        {role.isActive ? '⏸️' : '▶️'}
                      </button>
                      <button 
                        className="btn-icon danger"
                        title={role.isSystem ? "Les rôles système ne peuvent pas être supprimés" : "Supprimer"}
                        onClick={() => handleDeleteUserRole(role)}
                        disabled={role.isSystem || role.usersCount > 0}
                      >
                        🗑️
                      </button>
                    </div>
                  </div>
                ))}
              </div>

              {userRoles.length === 0 && (
                <div className="no-results">
                  Aucun rôle configuré. Cliquez sur "Nouveau rôle" pour en créer un.
                </div>
              )}
            </div>
          )}
        </main>
      </div>

      {/* User Modal */}
      {showUserModal && (
        <div className="modal-overlay" onClick={() => setShowUserModal(false)}>
          <div className="modal" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{editingUser ? '✏️ Modifier' : '➕ Nouvel'} utilisateur</h2>
              <button className="modal-close" onClick={() => setShowUserModal(false)}>×</button>
            </div>
            <form onSubmit={handleUserSubmit}>
              <div className="modal-body">
                <div className="form-row">
                  <div className="form-group">
                    <label>Prénom *</label>
                    <input
                      type="text"
                      value={userFormData.firstName}
                      onChange={(e) => setUserFormData({...userFormData, firstName: e.target.value})}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label>Nom *</label>
                    <input
                      type="text"
                      value={userFormData.lastName}
                      onChange={(e) => setUserFormData({...userFormData, lastName: e.target.value})}
                      required
                    />
                  </div>
                </div>
                <div className="form-group">
                  <label>Email *</label>
                  <input
                    type="email"
                    value={userFormData.email}
                    onChange={(e) => setUserFormData({...userFormData, email: e.target.value})}
                    required
                  />
                </div>
                <div className="form-row">
                  <div className="form-group">
                    <label>Rôle</label>
                    <select
                      value={userFormData.role}
                      onChange={(e) => setUserFormData({...userFormData, role: e.target.value})}
                    >
                      {availableRoles.map(role => (
                        <option key={role} value={role}>{role}</option>
                      ))}
                    </select>
                  </div>
                  <div className="form-group">
                    <label>Statut</label>
                    <select
                      value={userFormData.status}
                      onChange={(e) => setUserFormData({...userFormData, status: e.target.value})}
                    >
                      {STATUSES.map(status => (
                        <option key={status} value={status}>{status}</option>
                      ))}
                    </select>
                  </div>
                </div>
                <div className="form-group">
                  <label>{editingUser ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'}</label>
                  <input
                    type="password"
                    value={userFormData.password}
                    onChange={(e) => setUserFormData({...userFormData, password: e.target.value})}
                    placeholder={editingUser ? '••••••••' : 'Mot de passe initial'}
                  />
                </div>
              </div>
              <div className="modal-actions">
                <button type="button" className="btn-cancel" onClick={() => setShowUserModal(false)}>
                  Annuler
                </button>
                <button type="submit" className="btn-submit">
                  {editingUser ? 'Enregistrer' : 'Créer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Promote Modal */}
      {showPromoteModal && (
        <div className="modal-overlay" onClick={() => setShowPromoteModal(false)}>
          <div className="modal" onClick={e => e.stopPropagation()}>
            <div className="modal-header promote-header">
              <h2>⬆️ Promouvoir administrateur</h2>
              <button className="modal-close" onClick={() => setShowPromoteModal(false)}>×</button>
            </div>
            <form onSubmit={handlePromote}>
              <div className="modal-body">
                <p className="promote-user-name">
                  Promouvoir <strong>{promoteData.userName}</strong> en administrateur
                </p>
                
                <div className="form-group">
                  <label>Niveau de permission</label>
                  <select
                    value={promoteData.permissionLevel}
                    onChange={(e) => setPromoteData({...promoteData, permissionLevel: e.target.value})}
                  >
                    <option value="Admin">🛡️ Admin</option>
                    <option value="Super_Admin">👑 Super Admin</option>
                  </select>
                </div>

                <div className="permissions-checkboxes">
                  <h4>Permissions</h4>
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={promoteData.canManageUsers}
                      onChange={(e) => setPromoteData({...promoteData, canManageUsers: e.target.checked})}
                    />
                    Gérer les utilisateurs
                  </label>
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={promoteData.canManageCalendars}
                      onChange={(e) => setPromoteData({...promoteData, canManageCalendars: e.target.checked})}
                    />
                    Gérer les calendriers
                  </label>
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={promoteData.canManagePermissions}
                      onChange={(e) => setPromoteData({...promoteData, canManagePermissions: e.target.checked})}
                    />
                    Gérer les permissions admin
                  </label>
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={promoteData.canViewAuditLogs}
                      onChange={(e) => setPromoteData({...promoteData, canViewAuditLogs: e.target.checked})}
                    />
                    Voir les logs d'audit
                  </label>
                </div>
              </div>
              <div className="modal-actions">
                <button type="button" className="btn-cancel" onClick={() => setShowPromoteModal(false)}>
                  Annuler
                </button>
                <button type="submit" className="btn-submit">
                  Promouvoir
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Log Details Modal */}
      {showLogDetailsModal && selectedLog && (
        <div className="modal-overlay" onClick={() => setShowLogDetailsModal(false)}>
          <div className="modal log-details-modal" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>📋 Détails du log</h2>
              <button className="modal-close" onClick={() => setShowLogDetailsModal(false)}>×</button>
            </div>
            <div className="log-details-content">
              {/* Log Info */}
              <div className="log-details-section">
                <h3>Informations générales</h3>
                <div className="log-details-grid">
                  <div className="log-detail-item">
                    <span className="log-detail-label">Date</span>
                    <span className="log-detail-value">
                      {new Date(selectedLog.createdAt).toLocaleString('fr-FR', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                      })}
                    </span>
                  </div>
                  <div className="log-detail-item">
                    <span className="log-detail-label">Effectué par</span>
                    <span className="log-detail-value">
                      {selectedLog.performerName || 'Système'}
                      {selectedLog.isAdminAction && (
                        <span className="admin-badge" title="Action admin">👑</span>
                      )}
                    </span>
                  </div>
                  {selectedLog.user && (
                    <div className="log-detail-item">
                      <span className="log-detail-label">Email utilisateur</span>
                      <span className="log-detail-value">{selectedLog.user.email}</span>
                    </div>
                  )}
                  <div className="log-detail-item">
                    <span className="log-detail-label">Action</span>
                    <span className={`action-badge action-${selectedLog.action}`}>
                      {selectedLog.actionLabel}
                    </span>
                  </div>
                  <div className="log-detail-item">
                    <span className="log-detail-label">Type d'entité</span>
                    <span className="entity-badge">{selectedLog.entityTypeLabel}</span>
                  </div>
                  <div className="log-detail-item">
                    <span className="log-detail-label">ID Entité</span>
                    <span className="log-detail-value">{selectedLog.entityId || '-'}</span>
                  </div>
                  {selectedLog.ipAddress && (
                    <div className="log-detail-item">
                      <span className="log-detail-label">Adresse IP</span>
                      <span className="log-detail-value">{selectedLog.ipAddress}</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Old Value */}
              {selectedLog.oldValue && (
                <div className="log-details-section">
                  <h3>🔴 Valeurs avant modification</h3>
                  <div className="log-json-container">
                    <pre className="log-json old-value">
                      {JSON.stringify(selectedLog.oldValue, null, 2)}
                    </pre>
                  </div>
                </div>
              )}

              {/* New Value */}
              {selectedLog.newValue && (
                <div className="log-details-section">
                  <h3>🟢 Valeurs après modification</h3>
                  <div className="log-json-container">
                    <pre className="log-json new-value">
                      {JSON.stringify(selectedLog.newValue, null, 2)}
                    </pre>
                  </div>
                </div>
              )}

              {/* No details available */}
              {!selectedLog.oldValue && !selectedLog.newValue && (
                <div className="log-details-section">
                  <p className="no-details">Aucun détail supplémentaire disponible pour cette action.</p>
                </div>
              )}
            </div>
            <div className="modal-actions">
              {canUndo(selectedLog) && (
                <button 
                  type="button" 
                  className="btn-undo-action"
                  onClick={() => {
                    const logType = ['user', 'administrator'].includes(selectedLog.entityType) ? 'user' : 'calendar'
                    handleUndoAction(selectedLog, logType)
                    setShowLogDetailsModal(false)
                  }}
                >
                  ↩️ Annuler cette action
                </button>
              )}
              <button type="button" className="btn-cancel" onClick={() => setShowLogDetailsModal(false)}>
                Fermer
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Event Type Modal */}
      {showEventTypeModal && (
        <div className="modal-overlay" onClick={() => setShowEventTypeModal(false)}>
          <div className="modal" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{editingEventType ? '✏️ Modifier' : '➕ Nouveau'} type d'événement</h2>
              <button className="modal-close" onClick={() => setShowEventTypeModal(false)}>×</button>
            </div>
            <form onSubmit={handleEventTypeSubmit}>
              <div className="modal-body">
                <div className="form-row">
                  <div className="form-group">
                    <label>Nom *</label>
                    <input
                      type="text"
                      value={eventTypeFormData.name}
                      onChange={(e) => {
                        const name = e.target.value
                        setEventTypeFormData({
                          ...eventTypeFormData, 
                          name,
                          // Auto-generate code if creating new
                          code: editingEventType ? eventTypeFormData.code : generateCodeFromName(name)
                        })
                      }}
                      placeholder="Ex: Cours, Réunion..."
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label>Code *</label>
                    <input
                      type="text"
                      value={eventTypeFormData.code}
                      onChange={(e) => setEventTypeFormData({...eventTypeFormData, code: e.target.value.toLowerCase().replace(/[^a-z0-9_]/g, '')})}
                      placeholder="Ex: course, meeting..."
                      pattern="[a-z0-9_]+"
                      title="Lettres minuscules, chiffres et underscores uniquement"
                      required
                    />
                    <small>Identifiant technique unique (lettres minuscules, chiffres, _)</small>
                  </div>
                </div>

                <div className="form-group">
                  <label>Description</label>
                  <input
                    type="text"
                    value={eventTypeFormData.description}
                    onChange={(e) => setEventTypeFormData({...eventTypeFormData, description: e.target.value})}
                    placeholder="Brève description du type..."
                  />
                </div>

                <div className="form-row">
                  <div className="form-group">
                    <label>Couleur</label>
                    <div className="color-input-group">
                      <input
                        type="color"
                        value={eventTypeFormData.color}
                        onChange={(e) => setEventTypeFormData({...eventTypeFormData, color: e.target.value})}
                      />
                      <input
                        type="text"
                        value={eventTypeFormData.color}
                        onChange={(e) => setEventTypeFormData({...eventTypeFormData, color: e.target.value})}
                        pattern="#[0-9a-fA-F]{6}"
                        placeholder="#3788d8"
                      />
                    </div>
                  </div>
                  <div className="form-group">
                    <label>Icône (emoji)</label>
                    <input
                      type="text"
                      value={eventTypeFormData.icon}
                      onChange={(e) => setEventTypeFormData({...eventTypeFormData, icon: e.target.value})}
                      placeholder="📚"
                      maxLength={2}
                    />
                    <small>Un emoji pour représenter ce type</small>
                  </div>
                </div>

                <div className="form-row">
                  <div className="form-group">
                    <label>Ordre d'affichage</label>
                    <input
                      type="number"
                      value={eventTypeFormData.displayOrder}
                      onChange={(e) => setEventTypeFormData({...eventTypeFormData, displayOrder: parseInt(e.target.value) || 0})}
                      min="0"
                    />
                  </div>
                  <div className="form-group">
                    <label className="checkbox-label">
                      <input
                        type="checkbox"
                        checked={eventTypeFormData.isActive}
                        onChange={(e) => setEventTypeFormData({...eventTypeFormData, isActive: e.target.checked})}
                      />
                      Type actif
                    </label>
                    <small>Les types inactifs ne peuvent pas être utilisés pour de nouveaux événements</small>
                  </div>
                </div>

                {/* Preview */}
                <div className="event-type-preview">
                  <label>Aperçu</label>
                  <div 
                    className="preview-badge"
                    style={{ 
                      backgroundColor: eventTypeFormData.color + '20',
                      borderLeft: `4px solid ${eventTypeFormData.color}`
                    }}
                  >
                    <span className="preview-icon">{eventTypeFormData.icon || '📌'}</span>
                    <span className="preview-name">{eventTypeFormData.name || 'Nom du type'}</span>
                  </div>
                </div>
              </div>
              <div className="modal-actions">
                <button type="button" className="btn-cancel" onClick={() => setShowEventTypeModal(false)}>
                  Annuler
                </button>
                <button type="submit" className="btn-submit">
                  {editingEventType ? 'Mettre à jour' : 'Créer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* User Role Modal */}
      {showUserRoleModal && (
        <div className="modal-overlay" onClick={() => setShowUserRoleModal(false)}>
          <div className="modal modal-large" onClick={e => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{editingUserRole ? '✏️ Modifier' : '➕ Nouveau'} rôle utilisateur</h2>
              <button className="modal-close" onClick={() => setShowUserRoleModal(false)}>×</button>
            </div>
            <form onSubmit={handleUserRoleSubmit}>
              <div className="modal-body">
                <div className="form-row">
                  <div className="form-group">
                    <label>Nom *</label>
                    <input
                      type="text"
                      value={userRoleFormData.name}
                      onChange={(e) => {
                        const name = e.target.value
                        setUserRoleFormData({
                          ...userRoleFormData, 
                          name,
                          // Auto-generate code if creating new
                          code: editingUserRole ? userRoleFormData.code : name.toUpperCase().replace(/[^A-Z0-9]/g, '_')
                        })
                      }}
                      placeholder="Ex: Élève, Professeur..."
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label>Code *</label>
                    <input
                      type="text"
                      value={userRoleFormData.code}
                      onChange={(e) => setUserRoleFormData({...userRoleFormData, code: e.target.value.toUpperCase().replace(/[^A-Z0-9_]/g, '')})}
                      placeholder="Ex: ELEVE, PROFESSEUR..."
                      pattern="[A-Z0-9_]+"
                      title="Lettres majuscules, chiffres et underscores uniquement"
                      required
                    />
                    <small>Identifiant technique unique</small>
                  </div>
                </div>

                <div className="form-group">
                  <label>Description</label>
                  <input
                    type="text"
                    value={userRoleFormData.description}
                    onChange={(e) => setUserRoleFormData({...userRoleFormData, description: e.target.value})}
                    placeholder="Brève description du rôle..."
                  />
                </div>

                <div className="form-row">
                  <div className="form-group">
                    <label>Couleur</label>
                    <div className="color-input-group">
                      <input
                        type="color"
                        value={userRoleFormData.color}
                        onChange={(e) => setUserRoleFormData({...userRoleFormData, color: e.target.value})}
                      />
                      <input
                        type="text"
                        value={userRoleFormData.color}
                        onChange={(e) => setUserRoleFormData({...userRoleFormData, color: e.target.value})}
                        pattern="#[0-9a-fA-F]{6}"
                        placeholder="#6366f1"
                      />
                    </div>
                  </div>
                  <div className="form-group">
                    <label>Icône (emoji)</label>
                    <input
                      type="text"
                      value={userRoleFormData.icon}
                      onChange={(e) => setUserRoleFormData({...userRoleFormData, icon: e.target.value})}
                      placeholder="👨‍🎓"
                      maxLength={4}
                    />
                    <small>Un emoji pour représenter ce rôle</small>
                  </div>
                </div>

                <div className="form-section">
                  <h4>🔐 Permissions du rôle</h4>
                  <div className="permissions-grid">
                    <label className="checkbox-label">
                      <input
                        type="checkbox"
                        checked={userRoleFormData.canCreateEvents}
                        onChange={(e) => setUserRoleFormData({...userRoleFormData, canCreateEvents: e.target.checked})}
                      />
                      <div className="permission-content">
                        <span>Peut créer des événements</span>
                        <small>Les utilisateurs avec ce rôle peuvent créer des événements dans leurs calendriers</small>
                      </div>
                    </label>
                    <label className="checkbox-label">
                      <input
                        type="checkbox"
                        checked={userRoleFormData.canCreatePublicEvents}
                        onChange={(e) => setUserRoleFormData({...userRoleFormData, canCreatePublicEvents: e.target.checked})}
                      />
                      <div className="permission-content">
                        <span>Peut créer des événements publics</span>
                        <small>Les utilisateurs peuvent ajouter des événements au calendrier général</small>
                      </div>
                    </label>
                    <label className="checkbox-label">
                      <input
                        type="checkbox"
                        checked={userRoleFormData.canShareCalendars}
                        onChange={(e) => setUserRoleFormData({...userRoleFormData, canShareCalendars: e.target.checked})}
                      />
                      <div className="permission-content">
                        <span>Peut partager des calendriers</span>
                        <small>Les utilisateurs peuvent partager leurs calendriers avec d'autres</small>
                      </div>
                    </label>
                  </div>
                </div>

                <div className="form-row">
                  <div className="form-group">
                    <label>Ordre d'affichage</label>
                    <input
                      type="number"
                      value={userRoleFormData.displayOrder}
                      onChange={(e) => setUserRoleFormData({...userRoleFormData, displayOrder: parseInt(e.target.value) || 0})}
                      min="0"
                    />
                  </div>
                  <div className="form-group">
                    <label className="checkbox-label">
                      <input
                        type="checkbox"
                        checked={userRoleFormData.isActive}
                        onChange={(e) => setUserRoleFormData({...userRoleFormData, isActive: e.target.checked})}
                      />
                      Rôle actif
                    </label>
                    <small>Les rôles inactifs ne peuvent pas être assignés à de nouveaux utilisateurs</small>
                  </div>
                </div>

                {/* Preview */}
                <div className="user-role-preview">
                  <label>Aperçu</label>
                  <div 
                    className="preview-role-badge"
                    style={{ 
                      backgroundColor: userRoleFormData.color + '20',
                      borderLeft: `4px solid ${userRoleFormData.color}`
                    }}
                  >
                    <span className="preview-icon">{userRoleFormData.icon || '👤'}</span>
                    <span className="preview-name">{userRoleFormData.name || 'Nom du rôle'}</span>
                  </div>
                </div>
              </div>
              <div className="modal-actions">
                <button type="button" className="btn-cancel" onClick={() => setShowUserRoleModal(false)}>
                  Annuler
                </button>
                <button type="submit" className="btn-submit">
                  {editingUserRole ? 'Mettre à jour' : 'Créer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}

export default AdminPage

