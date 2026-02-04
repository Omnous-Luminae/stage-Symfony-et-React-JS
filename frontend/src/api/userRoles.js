import api from './axios';

const userRolesApi = {
  /**
   * Récupère tous les rôles actifs (API publique)
   */
  getAll: async () => {
    const response = await api.get('/user-roles');
    return response.data;
  },

  /**
   * Récupère tous les rôles avec infos complètes (API admin)
   */
  getAllAdmin: async () => {
    const response = await api.get('/user-roles/admin');
    return response.data;
  },

  /**
   * Crée un nouveau rôle
   */
  create: async (roleData) => {
    const response = await api.post('/user-roles', roleData);
    return response.data;
  },

  /**
   * Met à jour un rôle existant
   */
  update: async (id, roleData) => {
    const response = await api.put(`/user-roles/${id}`, roleData);
    return response.data;
  },

  /**
   * Supprime un rôle
   */
  delete: async (id) => {
    const response = await api.delete(`/user-roles/${id}`);
    return response.data;
  },

  /**
   * Réordonne les rôles
   */
  reorder: async (order) => {
    const response = await api.post('/user-roles/reorder', { order });
    return response.data;
  }
};

export default userRolesApi;
