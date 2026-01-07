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
};
