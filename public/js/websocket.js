// Módulo de gestión de WebSocket con reconexión automática
class WebSocketManager {
    constructor(url, onMessage, onError) {
        this.url = url;
        this.onMessage = onMessage;
        this.onError = onError;
        this.ws = null;
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.isManualClose = false;
    }

    connect() {
        try {
            this.ws = new WebSocket(this.url);

            this.ws.onopen = () => {
                console.log(`WebSocket conectado: ${this.url}`);
                this.reconnectAttempts = 0;
                this.onMessage && this.onMessage({ type: 'connected' });
            };

            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.onMessage && this.onMessage(data);
                } catch (error) {
                    console.error('Error al parsear mensaje WebSocket:', error);
                }
            };

            this.ws.onerror = (error) => {
                console.error('Error en WebSocket:', error);
                this.onError && this.onError(error);
            };

            this.ws.onclose = () => {
                console.log(`WebSocket desconectado: ${this.url}`);
                if (!this.isManualClose) {
                    this.scheduleReconnect();
                }
            };

        } catch (error) {
            console.error('Error al conectar WebSocket:', error);
            this.scheduleReconnect();
        }
    }

    scheduleReconnect() {
        if (this.reconnectAttempts >= CONFIG.MAX_RECONNECTION_ATTEMPTS) {
            console.error('Máximo de intentos de reconexión alcanzado');
            this.onError && this.onError(new Error('No se pudo reconectar'));
            return;
        }

        this.reconnectAttempts++;
        const delay = CONFIG.RECONNECTION_DELAY * this.reconnectAttempts;

        console.log(`Reintentando conexión en ${delay}ms (intento ${this.reconnectAttempts})`);

        this.reconnectTimer = setTimeout(() => {
            this.connect();
        }, delay);
    }

    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
            return true;
        }
        console.warn('WebSocket no está conectado');
        return false;
    }

    close() {
        this.isManualClose = true;
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
        }
        if (this.ws) {
            this.ws.close();
        }
    }
}

// Gestores específicos para cada canal
class PriceWebSocket extends WebSocketManager {
    constructor(onPriceUpdate, onTargetReached) {
        super(
            CONFIG.WS_PRICES_URL,
            (data) => {
                console.log('Mensaje recibido del WebSocket de precios:', data);
                if (data.type === 'prices') {
                    console.log('Actualizando precios:', data.data);
                    onPriceUpdate(data.data);
                } else if (data.type === 'target_reached') {
                    // Notificación de precio objetivo alcanzado
                    console.log('Precio objetivo alcanzado:', data.notification);
                    if (onTargetReached) {
                        onTargetReached(data.notification);
                    }
                } else if (data.type === 'connected') {
                    console.log('WebSocket de precios conectado');
                }
            },
            (error) => {
                console.error('Error en canal de precios:', error);
            }
        );
    }
}

class ConfigWebSocket extends WebSocketManager {
    constructor(onConfigResponse) {
        super(
            CONFIG.WS_CONFIG_URL,
            (data) => {
                onConfigResponse(data);
            },
            (error) => {
                console.error('Error en canal de configuraciones:', error);
            }
        );
    }

    sendConfigurations(userId, configurations, timestamp) {
        return this.send({
            user_id: userId,
            configurations: configurations,
            timestamp: timestamp,
        });
    }
}
