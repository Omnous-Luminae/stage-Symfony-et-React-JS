import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'
import { useCalendar } from '../context/CalendarContext'
import './Layout.css'

function Layout({ children }) {
  const { user, logout, isAuthenticated } = useAuth()
  const { activeCalendar } = useCalendar()
  const location = useLocation()
  const navigate = useNavigate()

  const handleLogout = async () => {
    if (!isAuthenticated) {
      navigate('/login')
      return
    }
    await logout()
    navigate('/login')
  }

  const isActive = (path) => location.pathname === path

  return (
    <div className="layout">
      <header className="layout-header">
        <div className="header-left">
          <h1 className="logo">📅 Agenda Partagé</h1>
          <div className="search-bar">
            <input type="text" placeholder="🔍 Rechercher..." />
          </div>
        </div>
        <div className="header-right">
          {isAuthenticated ? (
            <div className="user-menu">
              <span className="user-name">👤 {user?.firstName} {user?.lastName}</span>
              <div className="user-dropdown">
                <Link to="/profile">Mon Profil</Link>
                <button onClick={handleLogout}>Déconnexion</button>
              </div>
            </div>
          ) : (
            <Link
              to="/login"
              style={{
                padding: '8px 14px',
                background: '#667eea',
                color: 'white',
                borderRadius: '8px',
                textDecoration: 'none',
                fontWeight: 700
              }}
            >
              Se connecter
            </Link>
          )}
        </div>
      </header>

      <nav className="layout-nav">
        <div className="nav-left">
          <Link 
            to="/calendar" 
            className={isActive('/calendar') ? 'nav-link active' : 'nav-link'}
          >
            📅 Calendrier
          </Link>
          <Link 
            to="/events" 
            className={isActive('/events') ? 'nav-link active' : 'nav-link'}
          >
            🎯 Événements
          </Link>
          {isAuthenticated && (
            <>
              <Link 
                to="/home" 
                className={isActive('/home') ? 'nav-link active' : 'nav-link'}
              >
                🏠 Accueil
              </Link>
            <Link 
              to="/dashboard" 
              className={isActive('/dashboard') ? 'nav-link active' : 'nav-link'}
            >
              📊 Tableau de Bord
            </Link>
            <Link 
              to="/agendas" 
              className={isActive('/agendas') ? 'nav-link active' : 'nav-link'}
            >
              📚 Mes Agendas
            </Link>
            {user?.roles?.includes('ROLE_ADMIN') && (
              <Link 
                to="/admin/users" 
                className={isActive('/admin/users') ? 'nav-link active' : 'nav-link'}
              >
                👥 Utilisateurs
              </Link>
            )}
            </>
          )}
          <Link 
            to="/about" 
            className={isActive('/about') ? 'nav-link active' : 'nav-link'}
          >
            ℹ️ À propos
          </Link>
        </div>
        
        {activeCalendar && (
          <div className="active-calendar-badge">
            <span style={{ color: activeCalendar.color }}>●</span>
            <span className="calendar-name">{activeCalendar.name}</span>
          </div>
        )}
        
        {isAuthenticated && (
          <Link 
            to="/settings" 
            className={isActive('/settings') ? 'nav-link active' : 'nav-link'}
          >
            ⚙️ Paramètres
          </Link>
        )}
      </nav>

      <main className="layout-main">
        {children}
      </main>
    </div>
  )
}

export default Layout
