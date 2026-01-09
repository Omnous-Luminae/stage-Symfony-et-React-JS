import { useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../api/axios'
import './login.css'

function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [success, setSuccess] = useState(false)
  const [devMode, setDevMode] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setMessage('')
    setError('')
    setLoading(true)

    try {
      const response = await api.post('/auth/forgot-password', { email })
      setMessage(response.data.message)
      setSuccess(true)
    } catch (err) {
      setError(err.response?.data?.error || 'Une erreur est survenue. Veuillez réessayer.')
    } finally {
      setLoading(false)
    }
  }

  const handleDevReset = async (e) => {
    e.preventDefault()
    setMessage('')
    setError('')

    if (newPassword !== confirmPassword) {
      setError('Les mots de passe ne correspondent pas')
      return
    }

    setLoading(true)

    try {
      const response = await api.post('/auth/reset-password-dev', { 
        email, 
        newPassword 
      })
      setMessage(response.data.message)
      setSuccess(true)
      setNewPassword('')
      setConfirmPassword('')
    } catch (err) {
      setError(err.response?.data?.error || 'Une erreur est survenue. Veuillez réessayer.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="login-page">
      <div className="login-card">
        <div className="login-header">
          <div className="login-logo">🔒</div>
          <div>
            <p className="login-subtitle">RÉINITIALISATION</p>
            <h1 className="login-title">Mot de passe oublié</h1>
          </div>
        </div>

        {!success ? (
          <>
            {!devMode ? (
              <form className="login-form" onSubmit={handleSubmit}>
                <p style={{ marginBottom: '20px', color: '#666', fontSize: '0.95rem' }}>
                  Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
                </p>

                <div className="login-field">
                  <label>Email</label>
                  <div className="login-input">
                    <span className="login-icon">✉️</span>
                    <input
                      type="email"
                      required
                      placeholder="prenom.nom@lycee.fr"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      disabled={loading}
                    />
                  </div>
                </div>

                {error && (
                  <div className="login-error">{error}</div>
                )}

                <button 
                  type="submit" 
                  className="login-button"
                  disabled={loading}
                >
                  {loading ? 'Envoi en cours...' : 'Envoyer le lien'}
                </button>

                <div style={{ 
                  marginTop: '20px', 
                  paddingTop: '20px',
                  borderTop: '1px solid #e0e0e0',
                  textAlign: 'center'
                }}>
                  <button
                    type="button"
                    onClick={() => setDevMode(true)}
                    style={{
                      background: '#ff9800',
                      color: 'white',
                      border: 'none',
                      padding: '10px 20px',
                      borderRadius: '6px',
                      cursor: 'pointer',
                      fontWeight: '600',
                      fontSize: '0.9rem'
                    }}
                  >
                    🔧 Modifier MDP (Mode test)
                  </button>
                </div>

                <div style={{ marginTop: '20px', textAlign: 'center' }}>
                  <Link className="login-link" to="/login">
                    ← Retour à la connexion
                  </Link>
                </div>
              </form>
            ) : (
              <form className="login-form" onSubmit={handleDevReset}>
                <div style={{ 
                  padding: '10px 15px', 
                  background: '#fff3e0', 
                  borderLeft: '4px solid #ff9800',
                  marginBottom: '20px',
                  fontSize: '0.9rem'
                }}>
                  ⚠️ <strong>Mode développement</strong> : Changez votre mot de passe directement
                </div>

                <div className="login-field">
                  <label>Email</label>
                  <div className="login-input">
                    <span className="login-icon">✉️</span>
                    <input
                      type="email"
                      required
                      placeholder="prenom.nom@lycee.fr"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      disabled={loading}
                    />
                  </div>
                </div>

                <div className="login-field">
                  <label>Nouveau mot de passe</label>
                  <div className="login-input">
                    <span className="login-icon">🔒</span>
                    <input
                      type="password"
                      required
                      placeholder="••••••••••••"
                      value={newPassword}
                      onChange={(e) => setNewPassword(e.target.value)}
                      disabled={loading}
                    />
                  </div>
                  <small style={{ color: '#666', fontSize: '0.85rem', display: 'block', marginTop: '5px' }}>
                    12+ caractères, majuscules, minuscules, chiffres, spéciaux
                  </small>
                </div>

                <div className="login-field">
                  <label>Confirmer le mot de passe</label>
                  <div className="login-input">
                    <span className="login-icon">🔒</span>
                    <input
                      type="password"
                      required
                      placeholder="••••••••••••"
                      value={confirmPassword}
                      onChange={(e) => setConfirmPassword(e.target.value)}
                      disabled={loading}
                    />
                  </div>
                </div>

                {error && (
                  <div className="login-error">{error}</div>
                )}

                <button 
                  type="submit" 
                  className="login-button"
                  disabled={loading}
                >
                  {loading ? 'Modification en cours...' : '✓ Modifier le mot de passe'}
                </button>

                <div style={{ marginTop: '15px', textAlign: 'center' }}>
                  <button
                    type="button"
                    onClick={() => {
                      setDevMode(false)
                      setNewPassword('')
                      setConfirmPassword('')
                      setError('')
                    }}
                    className="login-link"
                    style={{ background: 'none', border: 'none', cursor: 'pointer' }}
                  >
                    ← Retour au mode normal
                  </button>
                </div>
              </form>
            )}
          </>
        ) : (
          <div className="login-form" style={{ textAlign: 'center' }}>
            <div style={{
              padding: '20px',
              background: '#d4edda',
              border: '1px solid #c3e6cb',
              borderRadius: '8px',
              marginBottom: '20px'
            }}>
              <p style={{ margin: 0, color: '#155724', fontSize: '1rem' }}>
                ✅ {message}
              </p>
            </div>

            {devMode ? (
              <p style={{ color: '#666', marginBottom: '20px' }}>
                Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
              </p>
            ) : (
              <p style={{ color: '#666', marginBottom: '20px' }}>
                Vérifiez votre boîte email et suivez les instructions pour réinitialiser votre mot de passe.
              </p>
            )}

            <Link to="/login" className="login-button" style={{ textDecoration: 'none', display: 'inline-block' }}>
              Retour à la connexion
            </Link>

            <div style={{ marginTop: '20px' }}>
              <button
                onClick={() => {
                  setSuccess(false)
                  setEmail('')
                  setNewPassword('')
                  setConfirmPassword('')
                  setMessage('')
                  setDevMode(false)
                }}
                className="login-link"
                style={{ background: 'none', border: 'none', cursor: 'pointer' }}
              >
                Modifier un autre mot de passe
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

export default ForgotPasswordPage
