import { Link } from 'react-router-dom'
import Layout from '../components/Layout'
import './HomePage.css'

function HomePage() {
  const menuItems = [
    {
      id: 1,
      title: 'Calendrier',
      icon: '📅',
      description: 'Visualisez et gérez les événements',
      link: '/calendar',
      color: '#667eea'
    },
    {
      id: 2,
      title: 'Agendas',
      icon: '📚',
      description: 'Créez et partagez vos agendas',
      link: '/agendas',
      color: '#f093fb'
    },
    {
      id: 3,
      title: 'Tableau de bord',
      icon: '📊',
      description: 'Visualisez vos statistiques',
      link: '/dashboard',
      color: '#4facfe'
    },
    {
      id: 4,
      title: 'Événements',
      icon: '🎯',
      description: 'Gérez vos événements',
      link: '/events',
      color: '#43e97b'
    },
    {
      id: 5,
      title: 'Paramètres',
      icon: '⚙️',
      description: 'Configurez votre profil',
      link: '/settings',
      color: '#fa709a'
    },
    {
      id: 6,
      title: 'À propos',
      icon: 'ℹ️',
      description: 'Informations de l\'application',
      link: '/about',
      color: '#fee140'
    }
  ]

  return (
    <Layout>
      <div className="home-page">
        <div className="home-header">
          <h1>Bienvenue dans Agenda Partagé</h1>
          <p>Gérez efficacement vos calendriers et événements</p>
        </div>

        <div className="menu-grid">
          {menuItems.map((item) => (
            <Link 
              key={item.id} 
              to={item.link} 
              className="menu-card"
              style={{ borderTopColor: item.color }}
            >
              <div className="menu-icon" style={{ backgroundColor: item.color }}>
                {item.icon}
              </div>
              <div className="menu-content">
                <h3>{item.title}</h3>
                <p>{item.description}</p>
              </div>
              <div className="menu-arrow">→</div>
            </Link>
          ))}
        </div>
      </div>
    </Layout>
  )
}

export default HomePage
