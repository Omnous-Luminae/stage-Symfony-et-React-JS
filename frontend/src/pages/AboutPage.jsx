import Layout from '../components/Layout'
import './AboutPage.css'

function AboutPage() {
  return (
    <Layout>
      <div className="about-page">
        <div className="about-hero">
          <div className="hero-content">
            <h1>📅 Agenda Partagé</h1>
            <p className="tagline">Gérez efficacement vos calendriers et événements</p>
          </div>
        </div>

        <div className="about-container">
          <section className="about-section">
            <h2>À propos de l'application</h2>
            <p>
              Agenda Partagé est une application de gestion de calendriers collaborative 
              conçue pour les établissements scolaires et les organisations. Elle permet 
              à chaque utilisateur de créer et de gérer ses propres agendas tout en les 
              partageant avec ses collègues.
            </p>
          </section>

          <section className="about-section">
            <h2>Fonctionnalités principales</h2>
            <div className="features-grid">
              <div className="feature-card">
                <div className="feature-icon">📅</div>
                <h3>Calendrier personnel</h3>
                <p>Créez et gérez vos propres agendas avec des événements récurrents.</p>
              </div>
              <div className="feature-card">
                <div className="feature-icon">👥</div>
                <h3>Calendriers partagés</h3>
                <p>Partagez vos agendas avec d'autres utilisateurs de l'établissement.</p>
              </div>
              <div className="feature-card">
                <div className="feature-icon">🎯</div>
                <h3>Gestion d'événements</h3>
                <p>Créez, modifiez et supprimez facilement vos événements.</p>
              </div>
              <div className="feature-card">
                <div className="feature-icon">📊</div>
                <h3>Tableau de bord</h3>
                <p>Visualisez vos événements d'aujourd'hui et à venir.</p>
              </div>
              <div className="feature-card">
                <div className="feature-icon">🔒</div>
                <h3>Sécurité</h3>
                <p>Contrôlez l'accès à vos agendas avec des rôles personnalisés.</p>
              </div>
              <div className="feature-card">
                <div className="feature-icon">📱</div>
                <h3>Responsive</h3>
                <p>Accédez à vos agendas depuis n'importe quel appareil.</p>
              </div>
            </div>
          </section>

          <section className="about-section">
            <h2>Informations techniques</h2>
            <div className="tech-info">
              <div className="tech-group">
                <h3>Frontend</h3>
                <ul>
                  <li>React 18+</li>
                  <li>React Router</li>
                  <li>FullCalendar</li>
                  <li>Axios</li>
                </ul>
              </div>
              <div className="tech-group">
                <h3>Backend</h3>
                <ul>
                  <li>Symfony 6+</li>
                  <li>Doctrine ORM</li>
                  <li>JWT Authentication</li>
                  <li>PostgreSQL/MySQL</li>
                </ul>
              </div>
              <div className="tech-group">
                <h3>Deployment</h3>
                <ul>
                  <li>Docker</li>
                  <li>Docker Compose</li>
                  <li>Nginx</li>
                  <li>PHP-FPM</li>
                </ul>
              </div>
            </div>
          </section>

          <section className="about-section">
            <h2>Support et documentation</h2>
            <div className="support-info">
              <p>
                Pour obtenir de l'aide ou signaler un problème, veuillez consulter 
                la documentation complète ou contacter l'équipe technique.
              </p>
              <div className="support-links">
                <a href="#" className="support-link">📖 Documentation</a>
                <a href="#" className="support-link">🐛 Signaler un bug</a>
                <a href="#" className="support-link">💡 Suggérer une fonction</a>
              </div>
            </div>
          </section>

          <section className="about-section footer-info">
            <h2>Légal et confidentialité</h2>
            <p>
              Agenda Partagé est conforme au RGPD et garantit la protection de vos 
              données personnelles. Pour plus d'informations, consultez notre 
              <a href="#"> politique de confidentialité</a> et nos 
              <a href="#"> conditions d'utilisation</a>.
            </p>
            <p className="version">Version 1.0.0 - © 2026 Tous droits réservés</p>
          </section>
        </div>
      </div>
    </Layout>
  )
}

export default AboutPage
