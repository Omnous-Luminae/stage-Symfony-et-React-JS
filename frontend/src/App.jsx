import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import CalendarPage from './pages/CalendarPage'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import { AuthProvider, RequireAuth } from './auth/AuthContext'
import './App.css'

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route
            path="/calendar"
            element={
              <RequireAuth>
                <CalendarPage />
              </RequireAuth>
            }
          />
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}

export default App
