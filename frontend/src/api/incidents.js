import api from './axios';

// ================================================================
// Catégories d'incidents
// ================================================================

/**
 * Récupère toutes les catégories d'incidents actives
 */
export const getIncidentCategories = async (includeAll = false) => {
    const params = includeAll ? { all: 1 } : {};
    const response = await api.get('/incident-categories', { params });
    return response.data.categories;
};

/**
 * Récupère une catégorie par son ID
 */
export const getIncidentCategory = async (id) => {
    const response = await api.get(`/incident-categories/${id}`);
    return response.data.category;
};

/**
 * Crée une nouvelle catégorie (admin)
 */
export const createIncidentCategory = async (categoryData) => {
    const response = await api.post('/incident-categories', categoryData);
    return response.data;
};

/**
 * Met à jour une catégorie (admin)
 */
export const updateIncidentCategory = async (id, categoryData) => {
    const response = await api.put(`/incident-categories/${id}`, categoryData);
    return response.data;
};

/**
 * Supprime une catégorie (admin)
 */
export const deleteIncidentCategory = async (id) => {
    const response = await api.delete(`/incident-categories/${id}`);
    return response.data;
};

// ================================================================
// Incidents
// ================================================================

/**
 * Récupère les incidents avec filtres et pagination
 */
export const getIncidents = async (filters = {}, page = 1, limit = 20) => {
    const params = { ...filters, page, limit };
    const response = await api.get('/incidents', { params });
    return response.data;
};

/**
 * Récupère un incident par son ID avec ses commentaires
 */
export const getIncident = async (id) => {
    const response = await api.get(`/incidents/${id}`);
    return response.data;
};

/**
 * Crée un nouvel incident
 */
export const createIncident = async (incidentData) => {
    const response = await api.post('/incidents', incidentData);
    return response.data;
};

/**
 * Met à jour un incident
 */
export const updateIncident = async (id, incidentData) => {
    const response = await api.put(`/incidents/${id}`, incidentData);
    return response.data;
};

/**
 * Change le statut d'un incident
 */
export const changeIncidentStatus = async (id, status, resolution = null) => {
    const data = { status };
    if (resolution) {
        data.resolution = resolution;
    }
    const response = await api.patch(`/incidents/${id}/status`, data);
    return response.data;
};

/**
 * Supprime un incident (admin)
 */
export const deleteIncident = async (id) => {
    const response = await api.delete(`/incidents/${id}`);
    return response.data;
};

/**
 * Récupère les statistiques des incidents (admin)
 */
export const getIncidentStatistics = async () => {
    const response = await api.get('/incidents/statistics');
    return response.data;
};

// ================================================================
// Commentaires
// ================================================================

/**
 * Ajoute un commentaire à un incident
 */
export const addIncidentComment = async (incidentId, content, isInternal = false) => {
    const response = await api.post(`/incidents/${incidentId}/comments`, {
        content,
        isInternal
    });
    return response.data;
};

// ================================================================
// Constantes
// ================================================================

export const INCIDENT_PRIORITIES = [
    { value: 'low', label: 'Basse', color: '#22c55e', icon: '🟢' },
    { value: 'medium', label: 'Moyenne', color: '#eab308', icon: '🟡' },
    { value: 'high', label: 'Haute', color: '#f97316', icon: '🟠' },
    { value: 'urgent', label: 'Urgente', color: '#ef4444', icon: '🔴' }
];

export const INCIDENT_STATUSES = [
    { value: 'open', label: 'Ouvert', color: '#3b82f6', icon: '📬' },
    { value: 'in_progress', label: 'En cours', color: '#f97316', icon: '⚙️' },
    { value: 'resolved', label: 'Résolu', color: '#22c55e', icon: '✅' },
    { value: 'closed', label: 'Fermé', color: '#6b7280', icon: '📁' }
];

export const getPriorityInfo = (priority) => {
    return INCIDENT_PRIORITIES.find(p => p.value === priority) || INCIDENT_PRIORITIES[1];
};

export const getStatusInfo = (status) => {
    return INCIDENT_STATUSES.find(s => s.value === status) || INCIDENT_STATUSES[0];
};
