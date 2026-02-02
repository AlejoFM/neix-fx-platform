// Módulo de gestión de UI
class UI {
    static showScreen(screenId) {
        document.querySelectorAll('.screen').forEach(screen => {
            screen.classList.remove('active');
        });
        const screen = document.getElementById(screenId);
        if (screen) {
            screen.classList.add('active');
        }
    }

    static showError(elementId, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = message;
            element.style.display = 'block';
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    }

    static renderInstrumentsTable() {
        const tbody = document.getElementById('instruments-tbody');
        if (!tbody) return;

        const instruments = appState.getInstruments();
        const prices = appState.prices;
        const configurations = appState.configurations;

        tbody.innerHTML = '';

        instruments.forEach(instrument => {
            const price = prices[instrument.symbol] || { price: '-' };
            const config = configurations[instrument.id] || {};

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${instrument.symbol}<br><small>${instrument.name}</small></td>
                <td class="price">${price.price !== '-' ? price.price.toFixed(6) : '-'}</td>
                <td>
                    <input type="number" 
                           step="0.000001" 
                           class="config-input" 
                           data-instrument-id="${instrument.id}"
                           data-config-field="target_price"
                           value="${config.target_price || ''}"
                           placeholder="Precio objetivo">
                </td>
                <td>
                    <select class="config-input" 
                            data-instrument-id="${instrument.id}"
                            data-config-field="operation_type">
                        <option value="buy" ${config.operation_type === 'buy' ? 'selected' : ''}>Compra</option>
                        <option value="sell" ${config.operation_type === 'sell' ? 'selected' : ''}>Venta</option>
                    </select>
                </td>
                <td>
                    <button class="btn btn-small btn-save" 
                            data-instrument-id="${instrument.id}">
                        Guardar
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Agregar event listeners para inputs
        tbody.querySelectorAll('.config-input').forEach(input => {
            input.addEventListener('change', () => {
                const instrumentId = parseInt(input.dataset.instrumentId);
                const field = input.dataset.configField;
                const value = input.type === 'number' ? parseFloat(input.value) : input.value;

                // Actualizar estado local
                const config = appState.getConfiguration(instrumentId) || {
                    instrument_id: instrumentId,
                    target_price: null,
                    operation_type: 'buy',
                };
                config[field] = value;
                appState.updateConfiguration(instrumentId, config);
            });
        });

        // Agregar event listeners para botones de guardar individual
        tbody.querySelectorAll('.btn-save').forEach(btn => {
            btn.addEventListener('click', async () => {
                const instrumentId = parseInt(btn.dataset.instrumentId);
                const config = appState.getConfiguration(instrumentId);
                if (config) {
                    await UI.saveConfiguration(config);
                }
            });
        });
    }

    static renderNotifications() {
        const container = document.getElementById('notifications-list');
        if (!container) return;

        const notifications = appState.getNotifications();

        if (notifications.length === 0) {
            container.innerHTML = '<p class="no-notifications">No hay notificaciones</p>';
            return;
        }

        container.innerHTML = notifications.map(notif => `
            <div class="notification notification-${notif.type} ${notif.is_read ? 'read' : 'unread'}" data-notification-id="${notif.id || ''}">
                <div class="notification-header">
                    <strong>${notif.title}</strong>
                    ${!notif.is_read ? '<span class="badge">Nuevo</span>' : ''}
                </div>
                <div class="notification-body">${notif.message}</div>
                <div class="notification-footer">
                    <small>${new Date(notif.created_at).toLocaleString()}</small>
                </div>
            </div>
        `).join('');

        // Agregar event listeners para marcar como leído
        container.querySelectorAll('.notification.unread').forEach(notifEl => {
            notifEl.addEventListener('click', async () => {
                const notifId = parseInt(notifEl.dataset.notificationId || '0');
                if (notifId > 0) {
                    await API.markNotificationAsRead(notifId);
                    await UI.loadNotifications();
                }
            });
        });
    }

    static async saveConfiguration(config) {
        try {
            await API.saveConfiguration({
                instrument_id: config.instrument_id,
                target_price: config.target_price || null,
                operation_type: config.operation_type || 'buy',
            });
            UI.showNotification('success', 'Configuración guardada', 'La configuración se guardó correctamente');
        } catch (error) {
            UI.showNotification('error', 'Error', error.message);
        }
    }

    static async sendAllConfigurations() {
        const configurations = appState.getAllConfigurations();
        
        if (configurations.length === 0) {
            UI.showNotification('warning', 'Sin configuraciones', 'No hay configuraciones para enviar');
            return;
        }

        const btn = document.getElementById('send-configurations-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Enviando...';
        }

        try {
            const userId = appState.getUser()?.id;
            if (!userId) {
                throw new Error('Usuario no autenticado');
            }

            // Preparar configuraciones para envío
            const configsToSend = configurations.map(config => ({
                instrument_id: config.instrument_id,
                target_price: config.target_price || null,
                operation_type: config.operation_type || 'buy',
            }));

            // Enviar vía WebSocket
            if (window.configWebSocket) {
                const timestamp = new Date().toISOString();
                const sent = window.configWebSocket.sendConfigurations(userId, configsToSend, timestamp);
                
                if (!sent) {
                    // Fallback a API REST
                    await API.sendConfigurations(configsToSend);
                }
            } else {
                // Fallback a API REST
                await API.sendConfigurations(configsToSend);
            }

            UI.showNotification('success', 'Enviado', 'Configuraciones enviadas correctamente');
            
            // Recargar configuraciones desde el servidor
            await UI.loadConfigurations();

        } catch (error) {
            UI.showNotification('error', 'Error al enviar', error.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Enviar Todas las Configuraciones';
            }
        }
    }

    static showNotification(type, title, message) {
        const notification = {
            id: Date.now(),
            type: type,
            title: title,
            message: message,
            is_read: false,
            created_at: new Date().toISOString(),
        };
        appState.addNotification(notification);
        UI.renderNotifications();
    }

    static async loadInstruments() {
        try {
            const response = await API.getInstruments();
            appState.setInstruments(response.data);
            UI.renderInstrumentsTable();
        } catch (error) {
            console.error('Error al cargar instrumentos:', error);
        }
    }

    static async loadConfigurations() {
        try {
            const response = await API.getConfigurations();
            appState.setConfigurations(response.data);
            UI.renderInstrumentsTable();
        } catch (error) {
            console.error('Error al cargar configuraciones:', error);
        }
    }

    static async loadNotifications() {
        try {
            const response = await API.getNotifications(10);
            appState.setNotifications(response.data);
            UI.renderNotifications();
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
        }
    }

    static updatePriceDisplay() {
        // Actualizar solo las celdas de precio sin re-renderizar toda la tabla
        const instruments = appState.getInstruments();
        const prices = appState.prices;

        instruments.forEach(instrument => {
            const price = prices[instrument.symbol];
            if (price) {
                const rows = document.querySelectorAll(`tr`);
                rows.forEach(row => {
                    const input = row.querySelector(`input[data-instrument-id="${instrument.id}"]`);
                    if (input) {
                        const priceCell = row.querySelector('.price');
                        if (priceCell) priceCell.textContent = typeof price.price === 'number' ? price.price.toFixed(6) : price.price;
                    }
                });
            }
        });
    }
}
