<?php
$pageTitle = 'Plataforma FX - Dashboard';
$baseUrl = '/';
$user = $currentUser; // from auth helper
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>styles/main.css">
</head>
<body class="platform-page">
    <header class="header">
        <h1>Plataforma FX</h1>
        <div class="user-info">
            <span id="current-user">Usuario: <?= htmlspecialchars($user['username']) ?></span>
            <a href="<?= $baseUrl ?>logout" id="logout-btn" class="btn btn-secondary">Cerrar Sesión</a>
        </div>
    </header>

    <main class="main-content">
        <aside class="notifications-panel">
            <h2>Notificaciones</h2>
            <div id="notifications-list" class="notifications-list"></div>
            <button id="load-more-notifications" class="btn btn-link">Cargar más</button>
        </aside>

        <section class="instruments-panel">
            <h2>Instrumentos FX</h2>
            <div class="table-container">
                <table id="instruments-table" class="instruments-table">
                    <thead>
                        <tr>
                            <th>Instrumento</th>
                            <th>Precio</th>
                            <th>Precio Objetivo</th>
                            <th>Tipo Operación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="instruments-tbody"></tbody>
                </table>
            </div>
            <div class="actions">
                <button id="send-configurations-btn" class="btn btn-primary btn-large">
                    Enviar Todas las Configuraciones
                </button>
            </div>
        </section>
    </main>

    <script src="<?= $baseUrl ?>js/config.js"></script>
    <script src="<?= $baseUrl ?>js/api.js"></script>
    <script src="<?= $baseUrl ?>js/websocket.js"></script>
    <script src="<?= $baseUrl ?>js/state.js"></script>
    <script src="<?= $baseUrl ?>js/ui.js"></script>
    <script src="<?= $baseUrl ?>js/platform.js"></script>
</body>
</html>
