import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import DashboardPage from './pages/DashboardPage'
import CalendarPage from './pages/CalendarPage'
import AgendasPage from './pages/AgendasPage'
import HomePage from './pages/HomePage'
import EventDetailsPage from './pages/EventDetailsPage'
import CalendarDetailsPage from './pages/CalendarDetailsPage'
import EventsListPage from './pages/EventsListPage'
import EventAlertsPage from './pages/EventAlertsPage'
import SettingsPage from './pages/SettingsPage'
import AboutPage from './pages/AboutPage'
import LoginPage from './pages/LoginPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import { AuthProvider, RequireAuth } from './auth/AuthContext'
import { NotificationProvider } from './context/NotificationContext'
import { CalendarProvider } from './context/CalendarContext'
import NotificationCenter from './components/NotificationCenter'
import './App.css'

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <CalendarProvider>
          <NotificationProvider>
            <NotificationCenter />
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route
              path="/dashboard"
              element={
                <RequireAuth>
                  <DashboardPage />
                </RequireAuth>
              }
            />
            <Route
              path="/calendar"
              element={<CalendarPage />}
            />
            <Route
              path="/calendar/:id"
              element={<EventDetailsPage />}
            />
            <Route
              path="/events"
              element={<EventsListPage />}
            />
            <Route
              path="/event/new"
              element={<EventDetailsPage />}
            />
            <Route
              path="/event/:eventId/alerts"
              element={<EventAlertsPage />}
            />
            <Route
              path="/event/:id"
              element={<EventDetailsPage />}
            />
            <Route
              path="/agendas"
              element={
                <RequireAuth>
                  <AgendasPage />
                </RequireAuth>
              }
            />
            <Route
              path="/agendas/:id"
              element={
                <RequireAuth>
                  <CalendarDetailsPage />
                </RequireAuth>
              }
            />
            <Route
              path="/settings"
              element={
                <RequireAuth>
                  <SettingsPage />
                </RequireAuth>
              }
            />
            <Route
              path="/about"
              element={<AboutPage />}
            />
            <Route
              path="/home"
              element={
                <RequireAuth>
                  <HomePage />
                </RequireAuth>
              }
            />
            <Route path="/" element={<Navigate to="/home" replace />} />
            <Route path="*" element={<Navigate to="/home" replace />} />
          </Routes>
          </NotificationProvider>
        </CalendarProvider>
      </AuthProvider>
    </BrowserRouter>
  )
}

export default App

