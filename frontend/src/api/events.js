import api from './axios';

export const eventService = {
  // Récupérer tous les événements
  getAll: () => api.get('/events'),
  
  // Créer un événement
  create: (eventData) => api.post('/events', eventData),

  // Mettre à jour un événement
  update: (id, eventData) => api.put(`/events/${id}`, eventData),
  
  // Supprimer un événement (options: { deleteSeries: true } pour supprimer la série entière)
  delete: (id, options = {}) => {
    const params = options.deleteSeries ? '?deleteSeries=true' : ''
    return api.delete(`/events/${id}${params}`)
  },
};

export const calendarService = {
  // Récupérer tous les calendriers
  getAll: () => api.get('/calendars'),

  // Créer un calendrier
  create: (calendarData) => api.post('/calendars', calendarData),

  // Mettre à jour un calendrier
  update: (id, calendarData) => api.put(`/calendars/${id}`, calendarData),

  // Supprimer un calendrier
  delete: (id) => api.delete(`/calendars/${id}`),

  // Obtenir les détails d'un calendrier
  getById: (id) => api.get(`/calendars/${id}`),

  // Partager un calendrier
  share: (id, permissionData) => api.post(`/calendars/${id}/share`, permissionData),

  // Obtenir les permissions d'un calendrier
  getPermissions: (id) => api.get(`/calendars/${id}/permissions`),

  // Supprimer une permission par ID
  removePermission: (calendarId, permissionId) => api.delete(`/calendars/${calendarId}/permissions/${permissionId}`),

  // Supprimer un partage par email
  removeShare: (calendarId, email) => api.delete(`/calendars/${calendarId}/share`, { data: { email } }),

  // Calendrier général
  getGeneralCalendar: () => api.get('/calendars/general'),
  initGeneralCalendar: () => api.post('/calendars/general/init'),
};

