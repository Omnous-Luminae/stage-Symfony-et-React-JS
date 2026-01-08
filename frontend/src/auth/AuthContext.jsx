import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { authService } from '../api/auth'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)
  const [authError, setAuthError] = useState(null)

  useEffect(() => {
    const bootstrap = async () => {
      try {
        const res = await authService.me()
        setUser(res.data)
      } catch (err) {
        setUser(null)
      } finally {
        setLoading(false)
      }
    }
    bootstrap()
  }, [])

  const login = async ({ email, password }) => {
    setAuthError(null)
    try {
      const res = await authService.login({ email, password })
      setUser(res.data.user || res.data)
      return true
    } catch (err) {
      const serverMsg = err?.response?.data?.error || err?.response?.data?.message
      setAuthError(serverMsg || 'Identifiants invalides ou compte non autorisé.')
      return false
    }
  }

  const register = async ({ email, firstName, lastName, password }) => {
    setAuthError(null)
    try {
      const res = await authService.register({ email, firstName, lastName, password })
      setUser(res.data.user || res.data)
      return true
    } catch (err) {
      const serverMsg = err?.response?.data?.error || err?.response?.data?.message
      setAuthError(serverMsg || 'Inscription impossible. Vérifiez les informations fournies.')
      return false
    }
  }

  const logout = () => {
    authService.logout().catch(() => {})
    setUser(null)
  }

  const value = useMemo(
    () => ({ user, loading, authError, login, register, logout }),
    [user, loading, authError]
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  return useContext(AuthContext)
}

export function RequireAuth({ children }) {
  const { user, loading } = useAuth()
  const location = useLocation()
  if (loading) return null
  if (!user) {
    return <Navigate to="/login" replace state={{ from: location }} />
  }
  return children
}
