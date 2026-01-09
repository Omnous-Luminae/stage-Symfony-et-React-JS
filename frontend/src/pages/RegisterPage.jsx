import { useEffect, useMemo, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'
import { authService } from '../api/auth'
import './login.css'

function RegisterPage() {
  const { register, user, authError } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    password: '',
    confirm: '',
    remember: true
  })
  const [emailStatus, setEmailStatus] = useState('idle') // idle|checking|available|taken
  const [emailMsg, setEmailMsg] = useState('')

  useEffect(() => {
    if (user) {
      const to = location.state?.from?.pathname || '/dashboard'
      navigate(to, { replace: true })
    }
  }, [user, navigate, location])

  const strength = useMemo(() => evaluatePassword(formData.password), [formData.password])

  const onEmailBlur = async () => {
    if (!formData.email) return
    setEmailStatus('checking')
    setEmailMsg('')
    try {
      const res = await authService.checkEmail(formData.email)
      if (res.data.available) {
        setEmailStatus('available')
        setEmailMsg('Email disponible')
      } else {
        setEmailStatus('taken')
        setEmailMsg('Email déjà utilisé')
      }
    } catch (e) {
      setEmailStatus('idle')
      setEmailMsg('Vérification email impossible')
    }
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    if (formData.password !== formData.confirm) {
      alert('Les mots de passe ne correspondent pas')
      return
    }
    const ok = evaluatePassword(formData.password).ok
    if (!ok) {
      alert('Mot de passe trop faible. Ajoutez majuscules, minuscules, chiffres et caractères spéciaux (12+).')
      return
    }
    if (emailStatus === 'taken') {
      alert('Cet email est déjà utilisé')
      return
    }
    register({
      email: formData.email,
      firstName: formData.firstName,
      lastName: formData.lastName,
      password: formData.password
    })
  }

  return (
    <div className="login-page">
      <div className="login-card">
        <div className="login-header">
          <div className="login-logo">👥</div>
          <div>
            <p className="login-subtitle">NOUVEAU COMPTE</p>
            <h1 className="login-title">Inscription</h1>
          </div>
        </div>

        <form className="login-form" onSubmit={handleSubmit}>
          <div className="login-field">
            <label>Prénom</label>
            <div className="login-input">
              <span className="login-icon">🙂</span>
              <input
                type="text"
                required
                placeholder="Jean"
                value={formData.firstName}
                onChange={(e) => setFormData({ ...formData, firstName: e.target.value })}
              />
            </div>
          </div>

          <div className="login-field">
            <label>Nom</label>
            <div className="login-input">
              <span className="login-icon">👤</span>
              <input
                type="text"
                required
                placeholder="Dupont"
                value={formData.lastName}
                onChange={(e) => setFormData({ ...formData, lastName: e.target.value })}
              />
            </div>
          </div>

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
                onBlur={onEmailBlur}
              />
            </div>
            {!!emailMsg && (
              <div className={`login-hint ${emailStatus === 'taken' ? 'error' : 'ok'}`}>
                {emailStatus === 'checking' ? 'Vérification…' : emailMsg}
              </div>
            )}
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
            <PasswordStrength strength={strength} />
          </div>

          <div className="login-field">
            <label>Confirmation</label>
            <div className="login-input">
              <span className="login-icon">✅</span>
              <input
                type="password"
                required
                placeholder="••••••••"
                value={formData.confirm}
                onChange={(e) => setFormData({ ...formData, confirm: e.target.value })}
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
            <Link className="login-link" to="/login">Déjà un compte ?</Link>
          </div>

          {authError && <div className="login-error">{authError}</div>}

          <button type="submit" className="login-button" disabled={!strength.ok || emailStatus==='taken' || emailStatus==='checking'}>
            Créer mon compte
          </button>
        </form>
      </div>
    </div>
  )
}

function evaluatePassword(pwd) {
  const score = {
    length: pwd.length >= 12,
    upper: /[A-Z]/.test(pwd),
    lower: /[a-z]/.test(pwd),
    digit: /[0-9]/.test(pwd),
    special: /[^A-Za-z0-9]/.test(pwd)
  }
  const passed = Object.values(score).filter(Boolean).length
  return {
    ok: passed === 5,
    passed,
    score
  }
}

function PasswordStrength({ strength }) {
  const steps = [
    { key: 'length', label: '12+ caractères' },
    { key: 'upper', label: 'Majuscule' },
    { key: 'lower', label: 'Minuscule' },
    { key: 'digit', label: 'Chiffre' },
    { key: 'special', label: 'Spécial' }
  ]

  return (
    <div className="pwd-strength">
      <div className="pwd-meter">
        {steps.map((s) => (
          <span key={s.key} className={strength.score[s.key] ? 'on' : ''} />
        ))}
      </div>
      <div className="pwd-hints">
        {steps.map((s) => (
          <span key={s.key} className={strength.score[s.key] ? 'ok' : 'ko'}>
            {strength.score[s.key] ? '✓' : '•'} {s.label}
          </span>
        ))}
      </div>
    </div>
  )
}

export default RegisterPage
