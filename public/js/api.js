// Módulo de comunicación con API REST
class API {
    static async request(endpoint, options = {}) {
        const url = `${CONFIG.API_BASE_URL}${endpoint}`;
        const defaultOptions = {
            credentials: 'same-origin', // enviar cookie de sesión PHP con las peticiones a /api
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const config = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, config);
            
            // Verificar content-type antes de parsear JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no es JSON:', text.substring(0, 200));
                throw new Error('El servidor devolvió una respuesta inválida. Verifica la consola para más detalles.');
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Error en la petición');
            }

            return data;
        } catch (error) {
            console.error('Error en API request:', error);
            console.error('URL:', url);
            console.error('Config:', config);
            throw error;
        }
    }

    static async login(username, password) {
        return this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ username, password }),
        });
    }

    static async logout() {
        return this.request('/auth/logout', {
            method: 'POST',
        });
    }

    static async getCurrentUser() {
        return this.request('/auth/me');
    }

    static async getInstruments() {
        return this.request('/instruments');
    }

    static async getConfigurations() {
        return this.request('/configurations');
    }

    static async saveConfiguration(configuration) {
        return this.request('/configurations', {
            method: 'POST',
            body: JSON.stringify(configuration),
        });
    }

    static async sendConfigurations(configurations) {
        return this.request('/configurations/send', {
            method: 'POST',
            body: JSON.stringify({ configurations }),
        });
    }

    static async getNotifications(limit = 10) {
        return this.request(`/notifications?limit=${limit}`);
    }

    static async markNotificationAsRead(notificationId) {
        return this.request('/notifications/read', {
            method: 'POST',
            body: JSON.stringify({ notification_id: notificationId }),
        });
    }

    static async getUnreadCount() {
        return this.request('/notifications/unread-count');
    }
}
