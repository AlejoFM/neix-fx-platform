// Gestión de estado de la aplicación
class AppState {
    constructor() {
        this.user = null;
        this.instruments = [];
        this.prices = {};
        this.configurations = {};
        this.notifications = [];
        this.listeners = {
            user: [],
            instruments: [],
            prices: [],
            configurations: [],
            notifications: [],
        };
    }

    // Suscripción a cambios de estado
    subscribe(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        }
    }

    // Notificar cambios
    notify(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => callback(data));
        }
    }

    // Setters con notificación
    setUser(user) {
        this.user = user;
        this.notify('user', user);
    }

    setInstruments(instruments) {
        this.instruments = instruments;
        this.notify('instruments', instruments);
    }

    updatePrices(prices) {
        prices.forEach(price => {
            this.prices[price.instrument] = {
                price: price.price,
                timestamp: price.timestamp,
            };
        });
        this.notify('prices', this.prices);
    }

    setConfigurations(configurations) {
        const configMap = {};
        configurations.forEach(config => {
            configMap[config.instrument_id] = config;
        });
        this.configurations = configMap;
        this.notify('configurations', this.configurations);
    }

    updateConfiguration(instrumentId, config) {
        this.configurations[instrumentId] = config;
        this.notify('configurations', this.configurations);
    }

    addNotification(notification) {
        this.notifications.unshift(notification);
        // Mantener solo las últimas 50
        if (this.notifications.length > 50) {
            this.notifications = this.notifications.slice(0, 50);
        }
        this.notify('notifications', this.notifications);
    }

    setNotifications(notifications) {
        this.notifications = notifications;
        this.notify('notifications', notifications);
    }

    // Getters
    getUser() {
        return this.user;
    }

    getInstruments() {
        return this.instruments;
    }

    getPrice(instrumentSymbol) {
        return this.prices[instrumentSymbol] || null;
    }

    getConfiguration(instrumentId) {
        return this.configurations[instrumentId] || null;
    }

    getAllConfigurations() {
        return Object.values(this.configurations);
    }

    getNotifications() {
        return this.notifications;
    }
}

// Instancia global del estado
const appState = new AppState();
