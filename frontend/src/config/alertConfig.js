/**
 * Configuration des Alertes et Notifications
 * Utilisé par useEventAlerts.js et EventAlertsPage
 */

export const ALERT_CONFIG = {
  // Intervalle de vérification des alertes (en millisecondes)
  checkInterval: 30000, // 30 secondes

  // Options d'alertes disponibles
  alertOptions: [
    {
      value: '5min',
      label: '5 minutes avant',
      minutesBefore: 5,
      icon: '⏱️'
    },
    {
      value: '15min',
      label: '15 minutes avant',
      minutesBefore: 15,
      icon: '⏱️'
    },
    {
      value: '30min',
      label: '30 minutes avant',
      minutesBefore: 30,
      icon: '⏱️'
    },
    {
      value: '1h',
      label: '1 heure avant',
      minutesBefore: 60,
      icon: '⏰'
    },
    {
      value: '1d',
      label: '1 jour avant',
      minutesBefore: 1440,
      icon: '📅'
    }
  ],

  // Keys pour localStorage
  storageKeys: {
    alertsPrefix: 'event-alerts-',
    alertShownPrefix: 'alert-shown-',
    favoriteCalendars: 'favorite-calendars',
    userPreferences: 'user-preferences'
  },

  // Messages de notification
  messages: {
    alertAdded: '✅ Alerte ajoutée',
    alertRemoved: '✅ Alerte supprimée',
    eventCreated: '✅ Événement créé',
    eventUpdated: '✅ Événement modifié',
    eventDeleted: '✅ Événement supprimé',
    calendarCreated: '✅ Agenda créé',
    calendarUpdated: '✅ Agenda modifié',
    calendarDeleted: '✅ Agenda supprimé',
    error: '❌ Une erreur est survenue',
    errorLoading: '❌ Erreur lors du chargement',
    errorDeleting: '❌ Erreur lors de la suppression'
  },

  // Types de notifications
  notificationTypes: {
    SUCCESS: 'success',
    ERROR: 'error',
    WARNING: 'warning',
    INFO: 'info'
  },

  // Durée d'affichage des notifications (en millisecondes)
  notificationDuration: {
    success: 4000,
    error: 5000,
    warning: 5000,
    info: 4000
  }
}

/**
 * Fonction helper pour obtenir l'option d'alerte
 * @param {string} alertValue - La valeur de l'alerte (ex: '15min')
 * @returns {object} L'option d'alerte
 */
export const getAlertOption = (alertValue) => {
  return ALERT_CONFIG.alertOptions.find(opt => opt.value === alertValue)
}

/**
 * Fonction helper pour obtenir le nombre de minutes
 * @param {string} alertValue - La valeur de l'alerte
 * @returns {number} Le nombre de minutes
 */
export const getMinutesBefore = (alertValue) => {
  const option = getAlertOption(alertValue)
  return option ? option.minutesBefore : null
}

/**
 * Fonction helper pour formater le texte d'alerte
 * @param {string} alertValue - La valeur de l'alerte
 * @returns {string} Le texte formaté
 */
export const formatAlertText = (alertValue) => {
  const option = getAlertOption(alertValue)
  return option ? `${option.icon} ${option.label}` : alertValue
}

/**
 * Fonction pour obtenir la clé de stockage pour une alerte d'événement
 * @param {number|string} eventId - L'ID de l'événement
 * @returns {string} La clé de stockage
 */
export const getAlertStorageKey = (eventId) => {
  return `${ALERT_CONFIG.storageKeys.alertsPrefix}${eventId}`
}

/**
 * Fonction pour obtenir la clé de stockage pour indiquer qu'une alerte a été affichée
 * @param {number|string} eventId - L'ID de l'événement
 * @returns {string} La clé de stockage
 */
export const getAlertShownKey = (eventId) => {
  return `${ALERT_CONFIG.storageKeys.alertShownPrefix}${eventId}`
}

export default ALERT_CONFIG
