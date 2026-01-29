import { useState, useEffect } from 'react'
import Layout from '../components/Layout'
import { useAuth } from '../auth/AuthContext'
import { authService } from '../api/auth'
import { useNotification } from '../context/NotificationContext'
import { usePreferences } from '../hooks/usePreferences'
import './SettingsPage.css'

function SettingsPage() {
  const { user, logout } = useAuth()
  const { showSuccess, showError } = useNotification()
  const { preferences, updatePreference, resetPreferences } = usePreferences()
  const [activeTab, setActiveTab] = useState('profile')
  
  // État pour le profil (mode édition)
  const [isEditingProfile, setIsEditingProfile] = useState(false)
  const [profileData, setProfileData] = useState({
    firstName: '',
    lastName: '',
    email: ''
  })
  const [savingProfile, setSavingProfile] = useState(false)

  // État pour le mot de passe
  const [passwordData, setPasswordData] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  })
  const [savingPassword, setSavingPassword] = useState(false)
  const [passwordStrength, setPasswordStrength] = useState({ score: 0, label: '' })

  // Charger les données utilisateur au montage
  useEffect(() => {
    if (user) {
      setProfileData({
        firstName: user.firstName || '',
        lastName: user.lastName || '',
        email: user.email || ''
      })
    }
  }, [user])

  // Calcul de la force du mot de passe
  useEffect(() => {
    const pwd = passwordData.newPassword
    let score = 0
    if (pwd.length >= 8) score++
    if (pwd.length >= 12) score++
    if (/[A-Z]/.test(pwd)) score++
    if (/[a-z]/.test(pwd)) score++
    if (/[0-9]/.test(pwd)) score++
    if (/[^A-Za-z0-9]/.test(pwd)) score++

    const labels = ['Très faible', 'Faible', 'Moyen', 'Bon', 'Fort', 'Très fort']
    setPasswordStrength({
      score: Math.min(score, 5),
      label: pwd.length === 0 ? '' : labels[Math.min(score, 5)]
    })
  }, [passwordData.newPassword])

  const handleLogout = () => {
    if (window.confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
      logout()
    }
  }

  // Gestion du profil
  const handleProfileChange = (e) => {
    const { name, value } = e.target
    setProfileData(prev => ({ ...prev, [name]: value }))
  }

  const handleSaveProfile = async (e) => {
    e.preventDefault()
    setSavingProfile(true)
    try {
      const response = await authService.updateProfile(profileData)
      showSuccess('Profil mis à jour avec succès')
      setIsEditingProfile(false)
      // Mettre à jour le contexte utilisateur si nécessaire
      window.location.reload() // Rechargement simple pour mettre à jour le contexte
    } catch (err) {
      showError(err.response?.data?.error || 'Erreur lors de la mise à jour du profil')
    } finally {
      setSavingProfile(false)
    }
  }

  // Gestion du mot de passe
  const handlePasswordChange = (e) => {
    const { name, value } = e.target
    setPasswordData(prev => ({ ...prev, [name]: value }))
  }

  const handleChangePassword = async (e) => {
    e.preventDefault()
    
    if (passwordData.newPassword !== passwordData.confirmPassword) {
      showError('Les mots de passe ne correspondent pas')
      return
    }

    if (passwordData.newPassword.length < 12) {
      showError('Le mot de passe doit contenir au moins 12 caractères')
      return
    }

    setSavingPassword(true)
    try {
      await authService.changePassword({
        currentPassword: passwordData.currentPassword,
        newPassword: passwordData.newPassword
      })
      showSuccess('Mot de passe modifié avec succès')
      setPasswordData({ currentPassword: '', newPassword: '', confirmPassword: '' })
    } catch (err) {
      showError(err.response?.data?.error || 'Erreur lors du changement de mot de passe')
    } finally {
      setSavingPassword(false)
    }
  }

  // Gestion des préférences
  const handlePreferenceChange = (key, value) => {
    updatePreference(key, value)
    showSuccess('Préférence enregistrée')
  }

  const handleResetPreferences = () => {
    if (window.confirm('Réinitialiser toutes les préférences aux valeurs par défaut ?')) {
      resetPreferences()
      showSuccess('Préférences réinitialisées')
    }
  }

  const getStrengthColor = () => {
    const colors = ['#ff4444', '#ff8800', '#ffcc00', '#88cc00', '#44bb00', '#00aa00']
    return colors[passwordStrength.score] || '#e0e0e0'
  }

  return (
    <Layout>
      <div className="settings-page">
        <div className="settings-header">
          <h1>⚙️ Paramètres</h1>
          <p>Gérez vos préférences et paramètres de compte</p>
        </div>

        <div className="settings-container">
          <div className="settings-tabs">
            <button
              className={`tab-button ${activeTab === 'profile' ? 'active' : ''}`}
              onClick={() => setActiveTab('profile')}
            >
              👤 Profil
            </button>
            <button
              className={`tab-button ${activeTab === 'preferences' ? 'active' : ''}`}
              onClick={() => setActiveTab('preferences')}
            >
              🎨 Préférences
            </button>
            <button
              className={`tab-button ${activeTab === 'security' ? 'active' : ''}`}
              onClick={() => setActiveTab('security')}
            >
              🔒 Sécurité
            </button>
          </div>

          <div className="settings-content">
            {/* ONGLET PROFIL */}
            {activeTab === 'profile' && (
              <div className="settings-section">
                <div className="section-header">
                  <h2>Informations de profil</h2>
                  {!isEditingProfile && (
                    <button className="btn-edit" onClick={() => setIsEditingProfile(true)}>
                      ✏️ Modifier
                    </button>
                  )}
                </div>

                {isEditingProfile ? (
                  <form onSubmit={handleSaveProfile} className="profile-form">
                    <div className="form-row">
                      <div className="form-group">
                        <label htmlFor="firstName">Prénom</label>
                        <input
                          type="text"
                          id="firstName"
                          name="firstName"
                          value={profileData.firstName}
                          onChange={handleProfileChange}
                          required
                        />
                      </div>
                      <div className="form-group">
                        <label htmlFor="lastName">Nom</label>
                        <input
                          type="text"
                          id="lastName"
                          name="lastName"
                          value={profileData.lastName}
                          onChange={handleProfileChange}
                          required
                        />
                      </div>
                    </div>
                    <div className="form-group">
                      <label htmlFor="email">Email</label>
                      <input
                        type="email"
                        id="email"
                        name="email"
                        value={profileData.email}
                        onChange={handleProfileChange}
                        required
                      />
                    </div>
                    <div className="form-actions">
                      <button type="button" className="btn-secondary" onClick={() => {
                        setIsEditingProfile(false)
                        setProfileData({
                          firstName: user?.firstName || '',
                          lastName: user?.lastName || '',
                          email: user?.email || ''
                        })
                      }}>
                        Annuler
                      </button>
                      <button type="submit" className="btn-primary" disabled={savingProfile}>
                        {savingProfile ? 'Enregistrement...' : '💾 Enregistrer'}
                      </button>
                    </div>
                  </form>
                ) : (
                  <div className="profile-info">
                    <div className="profile-avatar">
                      <div className="avatar-circle">
                        {user?.firstName?.[0]}{user?.lastName?.[0]}
                      </div>
                    </div>
                    <div className="profile-details">
                      <div className="info-group">
                        <label>Prénom</label>
                        <p>{user?.firstName || 'Non défini'}</p>
                      </div>
                      <div className="info-group">
                        <label>Nom</label>
                        <p>{user?.lastName || 'Non défini'}</p>
                      </div>
                      <div className="info-group">
                        <label>Email</label>
                        <p>{user?.email}</p>
                      </div>
                      <div className="info-group">
                        <label>Rôle</label>
                        <p className="role-badge">{user?.roles?.includes('ROLE_ADMIN') ? '👑 Administrateur' : '👤 Utilisateur'}</p>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* ONGLET PRÉFÉRENCES */}
            {activeTab === 'preferences' && (
              <div className="settings-section">
                <h2>Préférences d'affichage</h2>
                
                <div className="preference-group">
                  <label className="preference-label">
                    <input 
                      type="checkbox" 
                      checked={preferences.notifications}
                      onChange={(e) => handlePreferenceChange('notifications', e.target.checked)}
                    />
                    <span>🔔 Activer les notifications</span>
                  </label>
                  <p className="preference-description">Recevoir des notifications pour les rappels d'événements</p>
                </div>

                <div className="preference-group">
                  <label className="preference-label">
                    <input 
                      type="checkbox" 
                      checked={preferences.darkMode}
                      onChange={(e) => handlePreferenceChange('darkMode', e.target.checked)}
                    />
                    <span>🌙 Mode sombre</span>
                  </label>
                  <p className="preference-description">Utiliser un thème sombre pour réduire la fatigue oculaire</p>
                </div>

                <div className="preference-group">
                  <label className="preference-label">
                    <input 
                      type="checkbox" 
                      checked={preferences.showPastEvents}
                      onChange={(e) => handlePreferenceChange('showPastEvents', e.target.checked)}
                    />
                    <span>📅 Afficher les événements passés</span>
                  </label>
                  <p className="preference-description">Continuer à afficher les événements terminés dans le calendrier</p>
                </div>

                <div className="preference-group">
                  <div className="preference-select">
                    <label>📆 Vue par défaut du calendrier</label>
                    <select 
                      value={preferences.defaultView}
                      onChange={(e) => handlePreferenceChange('defaultView', e.target.value)}
                    >
                      <option value="month">Mois</option>
                      <option value="week">Semaine</option>
                      <option value="day">Jour</option>
                    </select>
                  </div>
                </div>

                <div className="preference-group">
                  <div className="preference-select">
                    <label>📅 Premier jour de la semaine</label>
                    <select 
                      value={preferences.weekStartsOn}
                      onChange={(e) => handlePreferenceChange('weekStartsOn', e.target.value)}
                    >
                      <option value="monday">Lundi</option>
                      <option value="sunday">Dimanche</option>
                    </select>
                  </div>
                </div>

                <div className="preference-info">
                  💡 Les préférences sont enregistrées automatiquement sur cet appareil.
                </div>

                <div className="preference-actions">
                  <button className="btn-secondary" onClick={handleResetPreferences}>
                    🔄 Réinitialiser les préférences
                  </button>
                </div>
              </div>
            )}

            {/* ONGLET SÉCURITÉ */}
            {activeTab === 'security' && (
              <div className="settings-section">
                <h2>Sécurité et confidentialité</h2>
                
                <div className="security-group">
                  <h3>🔑 Changer le mot de passe</h3>
                  <form onSubmit={handleChangePassword}>
                    <div className="form-group">
                      <label>Mot de passe actuel</label>
                      <input 
                        type="password" 
                        name="currentPassword"
                        value={passwordData.currentPassword}
                        onChange={handlePasswordChange}
                        placeholder="••••••••"
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label>Nouveau mot de passe</label>
                      <input 
                        type="password" 
                        name="newPassword"
                        value={passwordData.newPassword}
                        onChange={handlePasswordChange}
                        placeholder="••••••••"
                        required
                      />
                      {passwordData.newPassword && (
                        <div className="password-strength">
                          <div className="strength-bar">
                            <div 
                              className="strength-fill" 
                              style={{ 
                                width: `${(passwordStrength.score / 5) * 100}%`,
                                backgroundColor: getStrengthColor()
                              }}
                            />
                          </div>
                          <span className="strength-label" style={{ color: getStrengthColor() }}>
                            {passwordStrength.label}
                          </span>
                        </div>
                      )}
                      <p className="form-hint">
                        12+ caractères avec majuscules, minuscules, chiffres et caractères spéciaux
                      </p>
                    </div>
                    <div className="form-group">
                      <label>Confirmer le nouveau mot de passe</label>
                      <input 
                        type="password" 
                        name="confirmPassword"
                        value={passwordData.confirmPassword}
                        onChange={handlePasswordChange}
                        placeholder="••••••••"
                        required
                      />
                      {passwordData.confirmPassword && passwordData.newPassword !== passwordData.confirmPassword && (
                        <p className="form-error">Les mots de passe ne correspondent pas</p>
                      )}
                    </div>
                    <button 
                      type="submit" 
                      className="btn-primary"
                      disabled={savingPassword || passwordData.newPassword !== passwordData.confirmPassword}
                    >
                      {savingPassword ? 'Mise à jour...' : '🔒 Mettre à jour le mot de passe'}
                    </button>
                  </form>
                </div>

                <div className="security-group">
                  <h3>🚪 Session</h3>
                  <p>Vous êtes actuellement connecté sur cet appareil.</p>
                  <button className="btn-danger" onClick={handleLogout}>
                    Se déconnecter
                  </button>
                </div>

                <div className="security-group">
                  <h3>📊 Données du compte</h3>
                  <p>Informations sur votre compte et vos données.</p>
                  <div className="account-stats">
                    <div className="stat-item">
                      <span className="stat-label">Email</span>
                      <span className="stat-value">{user?.email}</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-label">ID utilisateur</span>
                      <span className="stat-value">#{user?.id}</span>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </Layout>
  )
}

export default SettingsPage
