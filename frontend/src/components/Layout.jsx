import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useState, useEffect, useRef } from 'react'
import { useAuth } from '../auth/AuthContext'
import api from '../api/axios'
import './Layout.css'

function Layout({ children }) {
  const { user, logout, isAuthenticated } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()
  const [isAdmin, setIsAdmin] = useState(false)
  const [showUserMenu, setShowUserMenu] = useState(false)
  const menuRef = useRef(null)

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

  // Fermer le menu si on clique en dehors
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (menuRef.current && !menuRef.current.contains(event.target)) {
        setShowUserMenu(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleLogout = async () => {
    if (!isAuthenticated) {
      navigate('/login')
      return
    }
    setShowUserMenu(false)
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
            <div className="header-user-section">
              {/* Menu utilisateur avec clic */}
              <div className="user-menu" ref={menuRef}>
                <button 
                  className="user-menu-trigger"
                  onClick={() => setShowUserMenu(!showUserMenu)}
                >
                  <span className="user-avatar">
                    {user?.firstName?.charAt(0)}{user?.lastName?.charAt(0)}
                  </span>
                  <span className="user-name-text">{user?.firstName} {user?.lastName}</span>
                  <span className={`dropdown-arrow ${showUserMenu ? 'open' : ''}`}>▼</span>
                </button>
                {showUserMenu && (
                  <div className="user-dropdown show">
                    <div className="dropdown-header">
                      <span className="dropdown-user-name">{user?.firstName} {user?.lastName}</span>
                      <span className="dropdown-user-email">{user?.email}</span>
                    </div>
                    <div className="dropdown-divider"></div>
                    <Link to="/settings" onClick={() => setShowUserMenu(false)}>
                      <span className="dropdown-icon">👤</span> Mon Profil
                    </Link>
                    <Link to="/settings" onClick={() => setShowUserMenu(false)}>
                      <span className="dropdown-icon">⚙️</span> Paramètres
                    </Link>
                    <div className="dropdown-divider"></div>
                    <button onClick={handleLogout} className="logout-button">
                      <span className="dropdown-icon">🚪</span> Déconnexion
                    </button>
                  </div>
                )}
              </div>
              
              {/* Bouton de déconnexion rapide toujours visible */}
              <button 
                className="btn-quick-logout" 
                onClick={handleLogout}
                title="Se déconnecter"
              >
                🚪
              </button>
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
