// Aplicación de login (vista servida por PHP; el formulario se envía al backend)
class App {
    constructor() {
        this.init();
    }

    async init() {
        // Si ya hay sesión activa (cookie de sesión), redirigir a la plataforma
        try {
            const userResponse = await API.getCurrentUser();
            if (userResponse.success) {
                window.location.href = '/platform';
                return;
            }
        } catch (error) {
            // No hay sesión, mostrar login (el formulario hace POST a /login)
        }
    }
}

// Inicializar aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});
