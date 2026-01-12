import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'
import './Layout.css'

function Layout({ children }) {
  const { user, logout, isAuthenticated } = useAuth()
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
        <Link 
          to="/calendar" 
          className={isActive('/calendar') ? 'nav-link active' : 'nav-link'}
        >
          📅 Calendrier
        </Link>
        {isAuthenticated && (
          <>
            <Link 
              to="/dashboard" 
              className={isActive('/dashboard') ? 'nav-link active' : 'nav-link'}
            >
              🏠 Tableau de Bord
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
      </nav>

      <main className="layout-main">
        {children}
      </main>
    </div>
  )
}

export default Layout
