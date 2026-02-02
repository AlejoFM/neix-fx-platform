// Configuración de la aplicación
const CONFIG = {
    API_BASE_URL: window.location.origin + '/api',
    WS_PRICES_URL: `ws://${window.location.hostname}:8081`,
    WS_CONFIG_URL: `ws://${window.location.hostname}:8082`,
    PRICE_UPDATE_INTERVAL: 5000, // 5 segundos
    RECONNECTION_DELAY: 3000, // 3 segundos
    MAX_RECONNECTION_ATTEMPTS: 5,
};
