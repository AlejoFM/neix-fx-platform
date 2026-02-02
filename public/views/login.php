<?php
$pageTitle = 'Plataforma FX - Iniciar sesión';
$baseUrl = '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>styles/main.css">
</head>
<body>
    <div id="login-screen" class="screen active">
        <div class="login-container">
            <h1>Plataforma FX</h1>
            <form id="login-form" action="<?= $baseUrl ?>login" method="POST">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                <?php if (!empty($loginError)): ?>
                <div id="login-error" class="error-message"><?= htmlspecialchars($loginError) ?></div>
                <?php else: ?>
                <div id="login-error" class="error-message" style="display: none;"></div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="<?= $baseUrl ?>js/config.js"></script>
    <script src="<?= $baseUrl ?>js/api.js"></script>
    <script src="<?= $baseUrl ?>js/websocket.js"></script>
    <script src="<?= $baseUrl ?>js/state.js"></script>
    <script src="<?= $baseUrl ?>js/ui.js"></script>
    <script src="<?= $baseUrl ?>js/app.js"></script>
</body>
</html>
