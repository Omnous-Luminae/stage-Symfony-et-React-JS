import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useState, useEffect } from 'react'
import { useAuth } from '../auth/AuthContext'
import api from '../api/axios'
import './Layout.css'

function Layout({ children }) {
  const { user, logout, isAuthenticated } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()
  const [isAdmin, setIsAdmin] = useState(false)

  // Check if user is admin
  useEffect(() => {
    if (isAuthenticated) {
      api.get('/admin/check')
        .then(response => setIsAdmin(response.data.isAdmin))
        .catch(() => setIsAdmin(false))
    } else {
      setIsAdmin(false)
    }
  }, [isAuthenticated])

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
        </div>
        <div className="header-right">
          {isAuthenticated ? (
            <div className="user-menu">
              <span className="user-name">👤 {user?.firstName} {user?.lastName}</span>
              <div className="user-dropdown">
                <Link to="/settings">Mon Profil</Link>
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
          {isAuthenticated && (
            <>
              <Link 
                to="/" 
                className={isActive('/') || isActive('/dashboard') ? 'nav-link active' : 'nav-link'}
              >
                📊 Tableau de Bord
              </Link>
              <Link 
                to="/calendar" 
                className={isActive('/calendar') ? 'nav-link active' : 'nav-link'}
              >
                📅 Calendrier
              </Link>
              {isAdmin && (
                <Link 
                  to="/admin" 
                  className={location.pathname.startsWith('/admin') ? 'nav-link active' : 'nav-link'}
                >
                  ⚙️ Administration
                </Link>
              )}
            </>
          )}
        </div>
        
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
