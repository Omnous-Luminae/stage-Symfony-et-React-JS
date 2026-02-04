import api from './axios';

/**
 * Récupère la liste des utilisateurs (admin only)
 */
export const getUsers = async (filters = {}) => {
    const response = await api.get('/admin/users', { params: filters });
    return response.data;
};

/**
 * Récupère un utilisateur par son ID
 */
export const getUser = async (id) => {
    const response = await api.get(`/admin/users/${id}`);
    return response.data;
};

/**
 * Récupère les utilisateurs par rôle
 */
export const getUsersByRole = async (role) => {
    const response = await api.get('/admin/users', { params: { role } });
    return response.data;
};

export default {
    getUsers,
    getUser,
    getUsersByRole
};
