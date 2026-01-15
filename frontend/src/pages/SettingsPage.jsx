import { useState } from 'react'
import Layout from '../components/Layout'
import { useAuth } from '../auth/AuthContext'
import './SettingsPage.css'

function SettingsPage() {
  const { user, logout } = useAuth()
  const [activeTab, setActiveTab] = useState('profile')

  const handleLogout = () => {
    if (window.confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
      logout()
    }
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
            {activeTab === 'profile' && (
              <div className="settings-section">
                <h2>Informations de profil</h2>
                {user && (
                  <div className="profile-info">
                    <div className="info-group">
                      <label>Prénom</label>
                      <p>{user.firstName || 'Non défini'}</p>
                    </div>
                    <div className="info-group">
                      <label>Nom</label>
                      <p>{user.lastName || 'Non défini'}</p>
                    </div>
                    <div className="info-group">
                      <label>Email</label>
                      <p>{user.email}</p>
                    </div>
                    <div className="info-group">
                      <label>Rôle</label>
                      <p>{user.role || 'Utilisateur'}</p>
                    </div>
                  </div>
                )}
              </div>
            )}

            {activeTab === 'preferences' && (
              <div className="settings-section">
                <h2>Préférences d'affichage</h2>
                <div className="preference-group">
                  <label className="preference-label">
                    <input type="checkbox" defaultChecked />
                    <span>Activer les notifications</span>
                  </label>
                  <p className="preference-description">Recevoir des notifications pour les rappels d'événements</p>
                </div>

                <div className="preference-group">
                  <label className="preference-label">
                    <input type="checkbox" defaultChecked />
                    <span>Mode sombre</span>
                  </label>
                  <p className="preference-description">Utiliser un thème sombre pour réduire la fatigue oculaire</p>
                </div>

                <div className="preference-group">
                  <label className="preference-label">
                    <input type="checkbox" defaultChecked />
                    <span>Afficher les événements passés</span>
                  </label>
                  <p className="preference-description">Continuer à afficher les événements terminés</p>
                </div>

                <button className="btn-primary">Sauvegarder les préférences</button>
              </div>
            )}

            {activeTab === 'security' && (
              <div className="settings-section">
                <h2>Sécurité et confidentialité</h2>
                
                <div className="security-group">
                  <h3>Changer le mot de passe</h3>
                  <div className="form-group">
                    <label>Mot de passe actuel</label>
                    <input type="password" placeholder="••••••••" />
                  </div>
                  <div className="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" placeholder="••••••••" />
                  </div>
                  <div className="form-group">
                    <label>Confirmer le nouveau mot de passe</label>
                    <input type="password" placeholder="••••••••" />
                  </div>
                  <button className="btn-primary">Mettre à jour le mot de passe</button>
                </div>

                <div className="security-group">
                  <h3>Sessions actives</h3>
                  <p>Vous êtes actuellement connecté sur cet appareil.</p>
                  <button className="btn-danger" onClick={handleLogout}>
                    🚪 Se déconnecter
                  </button>
                </div>

                <div className="security-group">
                  <h3>Données personnelles</h3>
                  <p>Gérez vos données selon la politique de confidentialité.</p>
                  <button className="btn-secondary">Télécharger mes données</button>
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
