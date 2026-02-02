// Aplicación de la plataforma (vista protegida)
class PlatformApp {
    constructor() {
        this.priceWebSocket = null;
        this.configWebSocket = null;
        this.init();
    }

    async init() {
        // Protección: verificar sesión antes de mostrar la plataforma
        try {
            const userResponse = await API.getCurrentUser();
            if (!userResponse.success) {
                window.location.href = '/login';
                return;
            }
            this.handleAuthSuccess(userResponse.user);
        } catch (error) {
            window.location.href = '/login';
            return;
        }

        this.setupEventListeners();
        this.setupStateSubscriptions();
    }

    handleAuthSuccess(user) {
        appState.setUser(user);

        const userElement = document.getElementById('current-user');
        if (userElement) {
            userElement.textContent = `Usuario: ${user.username}`;
        }

        this.loadInitialData();
        this.connectWebSockets();
    }

    async loadInitialData() {
        await UI.loadInstruments();
        await UI.loadConfigurations();
        await UI.loadNotifications();
    }

    setupEventListeners() {
        // Logout: el enlace <a href="/logout"> se encarga de cerrar sesión en el backend
        const sendBtn = document.getElementById('send-configurations-btn');
        if (sendBtn) {
            sendBtn.addEventListener('click', async () => {
                await UI.sendAllConfigurations();
            });
        }

        const loadMoreBtn = document.getElementById('load-more-notifications');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', async () => {
                const currentCount = appState.getNotifications().length;
                const response = await API.getNotifications(currentCount + 10);
                appState.setNotifications(response.data);
                UI.renderNotifications();
            });
        }
    }

    setupStateSubscriptions() {
        appState.subscribe('prices', () => {
            UI.updatePriceDisplay();
        });

        appState.subscribe('configurations', () => {
            UI.renderInstrumentsTable();
        });

        appState.subscribe('notifications', () => {
            UI.renderNotifications();
        });
    }

    connectWebSockets() {
        this.priceWebSocket = new PriceWebSocket(
            (prices) => {
                appState.updatePrices(prices);
            },
            (notification) => {
                appState.addNotification(notification);
                UI.renderNotifications();
                UI.showNotification(
                    notification.type,
                    notification.title,
                    notification.message
                );
                UI.loadNotifications();
            }
        );
        this.priceWebSocket.connect();
        window.priceWebSocket = this.priceWebSocket;

        this.configWebSocket = new ConfigWebSocket((response) => {
            if (response.type === 'success' || response.type === 'warning') {
                UI.showNotification(
                    response.type,
                    response.message,
                    response.message
                );
                UI.loadConfigurations();
                UI.loadNotifications();
            } else if (response.type === 'error') {
                UI.showNotification('error', 'Error', response.message);
            }
        });
        this.configWebSocket.connect();
        window.configWebSocket = this.configWebSocket;
    }

    disconnectWebSockets() {
        if (this.priceWebSocket) {
            this.priceWebSocket.close();
            this.priceWebSocket = null;
        }
        if (this.configWebSocket) {
            this.configWebSocket.close();
            this.configWebSocket = null;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.platformApp = new PlatformApp();
});
