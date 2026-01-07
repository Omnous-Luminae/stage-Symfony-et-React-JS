import api from './axios'

export const authService = {
  login: (payload) => api.post('/auth/login', payload),
  register: (payload) => api.post('/auth/register', payload),
  me: () => api.get('/auth/me'),
  logout: () => api.post('/auth/logout'),
  checkEmail: (email) => api.get('/auth/check-email', { params: { email } })
}
