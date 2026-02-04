import { useState, useEffect } from 'react';
import { useAuth } from '../auth/AuthContext';
import Layout from '../components/Layout';
import {
    getIncidents,
    getIncidentCategories,
    createIncident,
    updateIncident,
    changeIncidentStatus,
    deleteIncident,
    addIncidentComment,
    getIncident,
    INCIDENT_PRIORITIES,
    INCIDENT_STATUSES,
    getPriorityInfo,
    getStatusInfo
} from '../api/incidents';
import { getUsers } from '../api/users';
import './IncidentsPage.css';

const IncidentsPage = () => {
    const { user } = useAuth();

    // États principaux
    const [incidents, setIncidents] = useState([]);
    const [categories, setCategories] = useState([]);
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    // Pagination
    const [pagination, setPagination] = useState({
        page: 1,
        limit: 20,
        total: 0,
        pages: 1
    });

    // Filtres
    const [filters, setFilters] = useState({
        status: '',
        priority: '',
        category: '',
        search: ''
    });

    // Modal création/édition
    const [showModal, setShowModal] = useState(false);
    const [editingIncident, setEditingIncident] = useState(null);
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        location: '',
        categoryId: '',
        priority: 'medium',
        assigneeId: '',
        assigneeRole: ''
    });

    // Modal détails
    const [showDetailsModal, setShowDetailsModal] = useState(false);
    const [selectedIncident, setSelectedIncident] = useState(null);
    const [incidentComments, setIncidentComments] = useState([]);
    const [newComment, setNewComment] = useState('');
    const [isInternalComment, setIsInternalComment] = useState(false);

    // Rôles disponibles pour l'assignation
    const availableRoles = ['Professeur', 'Personnel', 'Intervenant'];

    // Chargement initial
    useEffect(() => {
        loadInitialData();
    }, []);

    // Chargement des incidents quand les filtres changent
    useEffect(() => {
        loadIncidents();
    }, [filters, pagination.page]);

    const loadInitialData = async () => {
        try {
            setLoading(true);
            const [categoriesData, usersData] = await Promise.all([
                getIncidentCategories(),
                user?.isAdmin ? getUsers() : Promise.resolve({ users: [] })
            ]);
            setCategories(categoriesData);
            setUsers(usersData.users || []);
            await loadIncidents();
        } catch (err) {
            setError('Erreur lors du chargement des données');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const loadIncidents = async () => {
        try {
            const result = await getIncidents(filters, pagination.page, pagination.limit);
            setIncidents(result.incidents);
            setPagination(prev => ({
                ...prev,
                ...result.pagination
            }));
        } catch (err) {
            setError('Erreur lors du chargement des incidents');
            console.error(err);
        }
    };

    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
        setPagination(prev => ({ ...prev, page: 1 }));
    };

    const handlePageChange = (newPage) => {
        setPagination(prev => ({ ...prev, page: newPage }));
    };

    // Gestion du formulaire
    const openCreateModal = () => {
        setEditingIncident(null);
        setFormData({
            title: '',
            description: '',
            location: '',
            categoryId: categories[0]?.id || '',
            priority: 'medium',
            assigneeId: '',
            assigneeRole: ''
        });
        setShowModal(true);
    };

    const openEditModal = (incident) => {
        setEditingIncident(incident);
        setFormData({
            title: incident.title,
            description: incident.description,
            location: incident.location || '',
            categoryId: incident.category.id,
            priority: incident.priority,
            assigneeId: incident.assignee?.id || '',
            assigneeRole: incident.assigneeRole || ''
        });
        setShowModal(true);
    };

    const handleFormChange = (key, value) => {
        setFormData(prev => {
            const newData = { ...prev, [key]: value };
            // Si on assigne à une personne, on retire le rôle et vice versa
            if (key === 'assigneeId' && value) {
                newData.assigneeRole = '';
            }
            if (key === 'assigneeRole' && value) {
                newData.assigneeId = '';
            }
            return newData;
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingIncident) {
                await updateIncident(editingIncident.id, formData);
            } else {
                await createIncident(formData);
            }
            setShowModal(false);
            loadIncidents();
        } catch (err) {
            setError(err.response?.data?.error || 'Erreur lors de la sauvegarde');
        }
    };

    // Gestion des détails
    const openDetailsModal = async (incident) => {
        try {
            const result = await getIncident(incident.id);
            setSelectedIncident(result.incident);
            setIncidentComments(result.comments);
            setShowDetailsModal(true);
        } catch (err) {
            setError('Erreur lors du chargement des détails');
        }
    };

    const handleStatusChange = async (newStatus) => {
        try {
            await changeIncidentStatus(selectedIncident.id, newStatus);
            const result = await getIncident(selectedIncident.id);
            setSelectedIncident(result.incident);
            loadIncidents();
        } catch (err) {
            setError(err.response?.data?.error || 'Erreur lors du changement de statut');
        }
    };

    const handleAddComment = async () => {
        if (!newComment.trim()) return;
        try {
            await addIncidentComment(selectedIncident.id, newComment, isInternalComment);
            setNewComment('');
            setIsInternalComment(false);
            const result = await getIncident(selectedIncident.id);
            setIncidentComments(result.comments);
        } catch (err) {
            setError(err.response?.data?.error || 'Erreur lors de l\'ajout du commentaire');
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet incident ?')) return;
        try {
            await deleteIncident(id);
            loadIncidents();
            if (showDetailsModal) {
                setShowDetailsModal(false);
            }
        } catch (err) {
            setError(err.response?.data?.error || 'Erreur lors de la suppression');
        }
    };

    // Vérification d'accès
    if (user?.role === 'Élève') {
        return (
            <Layout>
                <div className="incidents-page">
                    <div className="access-denied">
                        <span className="icon">🚫</span>
                        <h2>Accès refusé</h2>
                        <p>Les élèves n'ont pas accès au système de signalement d'incidents.</p>
                    </div>
                </div>
            </Layout>
        );
    }

    if (loading) {
        return (
            <Layout>
                <div className="incidents-page">
                    <div className="loading">Chargement...</div>
                </div>
            </Layout>
        );
    }

    return (
        <Layout>
            <div className="incidents-page">
                <header className="incidents-header">
                    <div className="header-content">
                        <h1>📋 Signalement d'incidents</h1>
                        <p>Signalez et suivez les incidents techniques ou matériels</p>
                    </div>
                    <button className="btn-primary" onClick={openCreateModal}>
                        ➕ Nouveau signalement
                    </button>
                </header>

            {error && (
                <div className="error-message">
                    {error}
                    <button onClick={() => setError('')}>✕</button>
                </div>
            )}

            {/* Filtres */}
            <div className="filters-section">
                <div className="filter-group">
                    <label>Statut</label>
                    <select
                        value={filters.status}
                        onChange={(e) => handleFilterChange('status', e.target.value)}
                    >
                        <option value="">Tous les statuts</option>
                        {INCIDENT_STATUSES.map(s => (
                            <option key={s.value} value={s.value}>{s.icon} {s.label}</option>
                        ))}
                    </select>
                </div>

                <div className="filter-group">
                    <label>Priorité</label>
                    <select
                        value={filters.priority}
                        onChange={(e) => handleFilterChange('priority', e.target.value)}
                    >
                        <option value="">Toutes les priorités</option>
                        {INCIDENT_PRIORITIES.map(p => (
                            <option key={p.value} value={p.value}>{p.icon} {p.label}</option>
                        ))}
                    </select>
                </div>

                <div className="filter-group">
                    <label>Catégorie</label>
                    <select
                        value={filters.category}
                        onChange={(e) => handleFilterChange('category', e.target.value)}
                    >
                        <option value="">Toutes les catégories</option>
                        {categories.map(c => (
                            <option key={c.id} value={c.id}>{c.icon} {c.name}</option>
                        ))}
                    </select>
                </div>

                <div className="filter-group search">
                    <label>Recherche</label>
                    <input
                        type="text"
                        value={filters.search}
                        onChange={(e) => handleFilterChange('search', e.target.value)}
                        placeholder="Rechercher..."
                    />
                </div>
            </div>

            {/* Liste des incidents */}
            <div className="incidents-list">
                {incidents.length === 0 ? (
                    <div className="no-incidents">
                        <span className="icon">📭</span>
                        <p>Aucun incident trouvé</p>
                    </div>
                ) : (
                    incidents.map(incident => (
                        <div
                            key={incident.id}
                            className={`incident-card status-${incident.status}`}
                            onClick={() => openDetailsModal(incident)}
                        >
                            <div className="incident-header">
                                <span
                                    className="category-badge"
                                    style={{ backgroundColor: incident.category.color }}
                                >
                                    {incident.category.icon} {incident.category.name}
                                </span>
                                <span
                                    className="priority-badge"
                                    style={{ backgroundColor: getPriorityInfo(incident.priority).color }}
                                >
                                    {getPriorityInfo(incident.priority).icon} {incident.priorityLabel}
                                </span>
                            </div>

                            <h3 className="incident-title">{incident.title}</h3>
                            
                            <p className="incident-description">
                                {incident.description.length > 150
                                    ? incident.description.substring(0, 150) + '...'
                                    : incident.description}
                            </p>

                            {incident.location && (
                                <p className="incident-location">📍 {incident.location}</p>
                            )}

                            <div className="incident-footer">
                                <div className="incident-meta">
                                    <span className="reporter">
                                        👤 {incident.reporter.name}
                                    </span>
                                    <span className="date">
                                        📅 {new Date(incident.createdAt).toLocaleDateString('fr-FR')}
                                    </span>
                                </div>
                                <span
                                    className="status-badge"
                                    style={{ backgroundColor: getStatusInfo(incident.status).color }}
                                >
                                    {getStatusInfo(incident.status).icon} {incident.statusLabel}
                                </span>
                            </div>

                            {(incident.assignee || incident.assigneeRole) && (
                                <div className="incident-assignee">
                                    <span>Assigné à : </span>
                                    <strong>
                                        {incident.assignee
                                            ? incident.assignee.name
                                            : `Rôle ${incident.assigneeRole}`}
                                    </strong>
                                </div>
                            )}
                        </div>
                    ))
                )}
            </div>

            {/* Pagination */}
            {pagination.pages > 1 && (
                <div className="pagination">
                    <button
                        disabled={pagination.page <= 1}
                        onClick={() => handlePageChange(pagination.page - 1)}
                    >
                        ← Précédent
                    </button>
                    <span className="page-info">
                        Page {pagination.page} / {pagination.pages}
                        ({pagination.total} incidents)
                    </span>
                    <button
                        disabled={pagination.page >= pagination.pages}
                        onClick={() => handlePageChange(pagination.page + 1)}
                    >
                        Suivant →
                    </button>
                </div>
            )}

            {/* Modal Création/Édition */}
            {showModal && (
                <div className="modal-overlay" onClick={() => setShowModal(false)}>
                    <div className="modal incident-modal" onClick={e => e.stopPropagation()}>
                        <div className="modal-header">
                            <h2>{editingIncident ? '✏️ Modifier l\'incident' : '➕ Nouveau signalement'}</h2>
                            <button className="close-btn" onClick={() => setShowModal(false)}>✕</button>
                        </div>

                        <form onSubmit={handleSubmit} className="incident-form">
                            <div className="form-group">
                                <label>Titre *</label>
                                <input
                                    type="text"
                                    value={formData.title}
                                    onChange={(e) => handleFormChange('title', e.target.value)}
                                    placeholder="Ex: Clavier défectueux"
                                    required
                                />
                            </div>

                            <div className="form-group">
                                <label>Description *</label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => handleFormChange('description', e.target.value)}
                                    placeholder="Décrivez le problème en détail..."
                                    rows={4}
                                    required
                                />
                            </div>

                            <div className="form-row">
                                <div className="form-group">
                                    <label>Catégorie *</label>
                                    <select
                                        value={formData.categoryId}
                                        onChange={(e) => handleFormChange('categoryId', e.target.value)}
                                        required
                                    >
                                        <option value="">Sélectionner...</option>
                                        {categories.map(c => (
                                            <option key={c.id} value={c.id}>
                                                {c.icon} {c.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="form-group">
                                    <label>Priorité</label>
                                    <select
                                        value={formData.priority}
                                        onChange={(e) => handleFormChange('priority', e.target.value)}
                                    >
                                        {INCIDENT_PRIORITIES.map(p => (
                                            <option key={p.value} value={p.value}>
                                                {p.icon} {p.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="form-group">
                                <label>Localisation</label>
                                <input
                                    type="text"
                                    value={formData.location}
                                    onChange={(e) => handleFormChange('location', e.target.value)}
                                    placeholder="Ex: Salle L201, Poste 19"
                                />
                            </div>

                            <div className="form-section">
                                <h3>Assignation (optionnel)</h3>
                                
                                <div className="form-row">
                                    <div className="form-group">
                                        <label>Assigner à un rôle</label>
                                        <select
                                            value={formData.assigneeRole}
                                            onChange={(e) => handleFormChange('assigneeRole', e.target.value)}
                                        >
                                            <option value="">Aucun rôle</option>
                                            {availableRoles.map(role => (
                                                <option key={role} value={role}>{role}</option>
                                            ))}
                                        </select>
                                    </div>

                                    {user?.isAdmin && users.length > 0 && (
                                        <div className="form-group">
                                            <label>Ou à une personne</label>
                                            <select
                                                value={formData.assigneeId}
                                                onChange={(e) => handleFormChange('assigneeId', e.target.value)}
                                            >
                                                <option value="">Aucune personne</option>
                                                {users.filter(u => u.role !== 'Élève').map(u => (
                                                    <option key={u.id} value={u.id}>
                                                        {u.name} ({u.role})
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="form-actions">
                                <button type="button" onClick={() => setShowModal(false)}>
                                    Annuler
                                </button>
                                <button type="submit" className="btn-primary">
                                    {editingIncident ? 'Enregistrer' : 'Créer le signalement'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Détails */}
            {showDetailsModal && selectedIncident && (
                <div className="modal-overlay" onClick={() => setShowDetailsModal(false)}>
                    <div className="modal details-modal" onClick={e => e.stopPropagation()}>
                        <div className="modal-header">
                            <div className="header-badges">
                                <span
                                    className="category-badge"
                                    style={{ backgroundColor: selectedIncident.category.color }}
                                >
                                    {selectedIncident.category.icon} {selectedIncident.category.name}
                                </span>
                                <span
                                    className="status-badge"
                                    style={{ backgroundColor: getStatusInfo(selectedIncident.status).color }}
                                >
                                    {getStatusInfo(selectedIncident.status).icon} {selectedIncident.statusLabel}
                                </span>
                            </div>
                            <button className="close-btn" onClick={() => setShowDetailsModal(false)}>✕</button>
                        </div>

                        <div className="incident-details">
                            <h2>{selectedIncident.title}</h2>

                            <div className="meta-info">
                                <span>
                                    {getPriorityInfo(selectedIncident.priority).icon} Priorité: {selectedIncident.priorityLabel}
                                </span>
                                {selectedIncident.location && (
                                    <span>📍 {selectedIncident.location}</span>
                                )}
                                <span>👤 Signalé par: {selectedIncident.reporter.name}</span>
                                <span>📅 {new Date(selectedIncident.createdAt).toLocaleString('fr-FR')}</span>
                            </div>

                            <div className="description-section">
                                <h3>Description</h3>
                                <p>{selectedIncident.description}</p>
                            </div>

                            {(selectedIncident.assignee || selectedIncident.assigneeRole) && (
                                <div className="assignee-section">
                                    <h3>Assigné à</h3>
                                    <p>
                                        {selectedIncident.assignee
                                            ? `${selectedIncident.assignee.name} (${selectedIncident.assignee.email})`
                                            : `Rôle: ${selectedIncident.assigneeRole}`}
                                    </p>
                                </div>
                            )}

                            {selectedIncident.resolution && (
                                <div className="resolution-section">
                                    <h3>Résolution</h3>
                                    <p>{selectedIncident.resolution}</p>
                                </div>
                            )}

                            {/* Actions de statut */}
                            <div className="status-actions">
                                <h3>Changer le statut</h3>
                                <div className="status-buttons">
                                    {INCIDENT_STATUSES.map(s => (
                                        <button
                                            key={s.value}
                                            className={selectedIncident.status === s.value ? 'active' : ''}
                                            style={{
                                                backgroundColor: selectedIncident.status === s.value ? s.color : 'transparent',
                                                borderColor: s.color,
                                                color: selectedIncident.status === s.value ? 'white' : s.color
                                            }}
                                            onClick={() => handleStatusChange(s.value)}
                                        >
                                            {s.icon} {s.label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Commentaires */}
                            <div className="comments-section">
                                <h3>💬 Commentaires ({incidentComments.length})</h3>

                                <div className="comments-list">
                                    {incidentComments.length === 0 ? (
                                        <p className="no-comments">Aucun commentaire pour le moment</p>
                                    ) : (
                                        incidentComments.map(comment => (
                                            <div
                                                key={comment.id}
                                                className={`comment ${comment.isInternal ? 'internal' : ''}`}
                                            >
                                                <div className="comment-header">
                                                    <strong>{comment.author.name}</strong>
                                                    {comment.isInternal && (
                                                        <span className="internal-badge">🔒 Interne</span>
                                                    )}
                                                    <span className="comment-date">
                                                        {new Date(comment.createdAt).toLocaleString('fr-FR')}
                                                    </span>
                                                </div>
                                                <p>{comment.content}</p>
                                            </div>
                                        ))
                                    )}
                                </div>

                                <div className="add-comment">
                                    <textarea
                                        value={newComment}
                                        onChange={(e) => setNewComment(e.target.value)}
                                        placeholder="Ajouter un commentaire..."
                                        rows={3}
                                    />
                                    <div className="comment-actions">
                                        <label className="internal-checkbox">
                                            <input
                                                type="checkbox"
                                                checked={isInternalComment}
                                                onChange={(e) => setIsInternalComment(e.target.checked)}
                                            />
                                            <span>🔒 Commentaire interne (visible uniquement par les assignés)</span>
                                        </label>
                                        <button
                                            className="btn-primary"
                                            onClick={handleAddComment}
                                            disabled={!newComment.trim()}
                                        >
                                            Envoyer
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="detail-actions">
                                <button onClick={() => {
                                    setShowDetailsModal(false);
                                    openEditModal(selectedIncident);
                                }}>
                                    ✏️ Modifier
                                </button>
                                {user?.isAdmin && (
                                    <button
                                        className="btn-danger"
                                        onClick={() => handleDelete(selectedIncident.id)}
                                    >
                                        🗑️ Supprimer
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    </Layout>
    );
};

export default IncidentsPage;
