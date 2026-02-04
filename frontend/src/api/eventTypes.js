import api from './axios';

/**
 * API pour la gestion des types d'événements
 */
const eventTypesApi = {
  /**
   * Récupère tous les types d'événements actifs
   * @returns {Promise} Liste des types actifs
   */
  getAll: async () => {
    const response = await api.get('/event-types');
    return response.data;
  },

  /**
   * Récupère tous les types (y compris inactifs) - Admin
   * @returns {Promise} Liste complète des types
   */
  getAllAdmin: async () => {
    const response = await api.get('/event-types/all');
    return response.data;
  },

  /**
   * Récupère un type par son ID
   * @param {number} id - ID du type
   * @returns {Promise} Détails du type
   */
  getById: async (id) => {
    const response = await api.get(`/event-types/${id}`);
    return response.data;
  },

  /**
   * Crée un nouveau type d'événement - Admin
   * @param {Object} data - Données du type
   * @param {string} data.name - Nom du type
   * @param {string} data.code - Code technique unique
   * @param {string} [data.description] - Description
   * @param {string} [data.color] - Couleur hexadécimale
   * @param {string} [data.icon] - Emoji ou icône
   * @param {boolean} [data.isActive] - Type actif
   * @param {number} [data.displayOrder] - Ordre d'affichage
   * @returns {Promise} Type créé
   */
  create: async (data) => {
    const response = await api.post('/event-types', data);
    return response.data;
  },

  /**
   * Met à jour un type d'événement - Admin
   * @param {number} id - ID du type
   * @param {Object} data - Données à mettre à jour
   * @returns {Promise} Type mis à jour
   */
  update: async (id, data) => {
    const response = await api.put(`/event-types/${id}`, data);
    return response.data;
  },

  /**
   * Supprime un type d'événement - Admin
   * @param {number} id - ID du type
   * @returns {Promise} Résultat de la suppression
   */
  delete: async (id) => {
    const response = await api.delete(`/event-types/${id}`);
    return response.data;
  },

  /**
   * Réorganise l'ordre des types - Admin
   * @param {number[]} orderedIds - Liste des IDs dans le nouvel ordre
   * @returns {Promise} Résultat
   */
  reorder: async (orderedIds) => {
    const response = await api.post('/event-types/reorder', { orderedIds });
    return response.data;
  },

  /**
   * Active/désactive un type - Admin
   * @param {number} id - ID du type
   * @returns {Promise} Nouvel état
   */
  toggleActive: async (id) => {
    const response = await api.post(`/event-types/${id}/toggle-active`);
    return response.data;
  }
};

export default eventTypesApi;
