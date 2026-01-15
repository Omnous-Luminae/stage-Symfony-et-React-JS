import api from './axios';

export const eventService = {
  // Récupérer tous les événements
  getAll: () => api.get('/events'),
  
  // Créer un événement
  create: (eventData) => api.post('/events', eventData),

  // Mettre à jour un événement
  update: (id, eventData) => api.put(`/events/${id}`, eventData),
  
  // Supprimer un événement
  delete: (id) => api.delete(`/events/${id}`),
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

  // Supprimer une permission
  removePermission: (calendarId, permissionId) => api.delete(`/calendars/${calendarId}/permissions/${permissionId}`),
};

