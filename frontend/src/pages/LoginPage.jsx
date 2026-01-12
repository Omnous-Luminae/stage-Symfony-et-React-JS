import { useEffect, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'
import './login.css'

function LoginPage() {
  const { login, user, authError } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [formData, setFormData] = useState({ email: '', password: '', remember: true })

  useEffect(() => {
    if (user) {
      const to = location.state?.from?.pathname || '/dashboard'
      navigate(to, { replace: true })
    }
  }, [user, navigate, location])

  const handleSubmit = (e) => {
    e.preventDefault()
    // TODO: branch to backend auth when API ready
    login({ email: formData.email, password: formData.password })
  }

  return (
    <div className="login-page">
      <div className="login-card">
        <div className="login-header">
          <div className="login-logo">📅</div>
          <div>
            <p className="login-subtitle">AGENDA PARTAGÉ</p>
            <h1 className="login-title">Lycée / BTS</h1>
          </div>
        </div>

        <form className="login-form" onSubmit={handleSubmit}>
          <div className="login-field">
            <label>Email</label>
            <div className="login-input">
              <span className="login-icon">✉️</span>
              <input
                type="email"
                required
                placeholder="prenom.nom@lycee.fr"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              />
            </div>
          </div>

          <div className="login-field">
            <label>Mot de passe</label>
            <div className="login-input">
              <span className="login-icon">🔒</span>
              <input
                type="password"
                required
                placeholder="••••••••"
                value={formData.password}
                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
              />
            </div>
          </div>

          <div className="login-row">
            <label className="login-remember">
              <input
                type="checkbox"
                checked={formData.remember}
                onChange={(e) => setFormData({ ...formData, remember: e.target.checked })}
              />
              <span>Se souvenir de moi</span>
            </label>
            <Link className="login-link" to="/forgot-password">Mot de passe oublié ?</Link>
          </div>

          {authError && <div className="login-error">{authError}</div>}

          <button type="submit" className="login-button">Se connecter</button>

          <div style={{ marginTop: '12px', textAlign: 'center', fontSize: '13px', color: '#4b5563', display: 'grid', gap: '6px' }}>
            <span>Les comptes sont créés par un administrateur.</span>
            <Link className="login-link" to="/calendar">Accéder à l'agenda sans se connecter</Link>
          </div>
        </form>
      </div>
    </div>
  )
}

export default LoginPage
