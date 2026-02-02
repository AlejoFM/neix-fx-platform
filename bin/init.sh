#!/bin/bash
# Script de inicialización que ejecuta migraciones y seed automáticamente

echo "=== Inicializando aplicación ==="

# Crear .env desde .env.example si no existe
if [ ! -f /var/www/.env ] && [ -f /var/www/.env.example ]; then
    cp /var/www/.env.example /var/www/.env
    echo "✓ .env creado desde .env.example"
fi

# Permisos de escritura en logs para www-data (PHP-FPM)
if [ -d /var/www/logs ]; then
    chown -R www-data:www-data /var/www/logs 2>/dev/null || true
    chmod -R 775 /var/www/logs 2>/dev/null || true
fi

# Esperar a que la base de datos esté lista
echo "Esperando a que la base de datos esté lista..."
until php -r "
try {
    \$pdo = new PDO('mysql:host=db;dbname=fx_platform', 'fx_user', 'fx_password');
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo 'Base de datos conectada\n';
    exit(0);
} catch (PDOException \$e) {
    exit(1);
}
"; do
    echo "Esperando conexión a la base de datos..."
    sleep 2
done

echo "✓ Base de datos conectada"

# Ejecutar migraciones (si es necesario)
echo "Ejecutando migraciones..."
php bin/migrate.php

# Ejecutar seed
echo "Ejecutando seed..."
php bin/seed.php

echo "=== Inicialización completada ==="
