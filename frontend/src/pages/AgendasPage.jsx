import Layout from '../components/Layout'
import './AgendasPage.css'

function AgendasPage() {
  return (
    <Layout>
      <div className="agendas-page">
        <div className="page-header">
          <h2>📚 Mes Agendas</h2>
          <button className="btn-primary">➕ Nouvel Agenda</button>
        </div>

        <div className="search-section">
          <input 
            type="text" 
            placeholder="🔍 Rechercher un agenda..." 
            className="search-input"
          />
        </div>

        <div className="agendas-section">
          <h3>Agendas Personnels</h3>
          <div className="agenda-card">
            <div className="agenda-color" style={{ background: '#667eea' }}></div>
            <div className="agenda-info">
              <div className="agenda-name">Mon Agenda Personnel</div>
              <div className="agenda-meta">Personnel</div>
            </div>
            <div className="agenda-actions">
              <button className="btn-secondary">✏️ Modifier</button>
            </div>
          </div>
        </div>

        <div className="agendas-section">
          <h3>Agendas Partagés (Propriétaire)</h3>
          <div className="empty-state">
            <p>Aucun agenda partagé</p>
            <p className="empty-hint">Créez un agenda et partagez-le avec vos collègues</p>
          </div>
        </div>

        <div className="agendas-section">
          <h3>Agendas Partagés (Accès)</h3>
          <div className="empty-state">
            <p>Aucun agenda partagé avec vous</p>
            <p className="empty-hint">Les agendas partagés apparaîtront ici</p>
          </div>
        </div>
      </div>
    </Layout>
  )
}

export default AgendasPage
