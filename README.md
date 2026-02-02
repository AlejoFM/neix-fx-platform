# Plataforma FX - Sistema de Operación de Foreign Exchange

## Descripción
Plataforma web para visualización y configuración de instrumentos con comunicación en tiempo real mediante WebSocket.

## Tecnologías
- **Backend**: PHP 8.1+ (Arquitectura por capas)
- **Frontend**: HTML5, JavaScript (ES6+), AJAX
- **Base de Datos**: MariaDB/MySQL
- **WebSocket**: Ratchet (PHP)
- **Generador de Precios**: Python 3.9+
- **Contenedores**: Docker Compose

## Requisitos Previos
- Docker y Docker Compose instalados
- Git

## Instalación

### 1. Clonar el repositorio
```bash
git clone <repository-url>
cd NEIX-Challenge
```

### 2. Configurar variables de entorno
```bash
cp .env.example .env
# Editar .env con tus configuraciones
```

### 3. Iniciar servicios
```bash
docker-compose up -d
```

### 4. Instalar dependencias PHP
```bash
docker-compose exec php composer install
```

### 5. Configurar base de datos
```bash
docker-compose exec php php bin/migrate.php
```

### 6. Crear usuarios de prueba
```bash
docker-compose exec php php bin/seed.php
```

## Uso

### Acceso a la aplicación
- **Frontend**: http://localhost:8080
- **API REST**: http://localhost:8080/api
- **WebSocket Precios**: ws://localhost:8081
- **WebSocket Configuraciones**: ws://localhost:8082

### Usuarios de prueba
- Usuario 1: `user1` / `password123`
- Usuario 2: `user2` / `password123`

## Estructura del Proyecto

```
NEIX-Challenge/
├── src/
│   ├── Domain/           # Capa de Dominio
│   ├── Application/      # Capa de Aplicación
│   └── Infrastructure/   # Capa de Infraestructura
├── public/               # Punto de entrada público
├── tests/                # Tests unitarios e integración
├── docker/               # Configuraciones Docker
├── scripts/              # Scripts de utilidad
└── docs/                 # Documentación adicional
```

## Testing

```bash
# Tests unitarios
docker-compose exec php vendor/bin/phpunit tests/Unit

# Tests de integración
docker-compose exec php vendor/bin/phpunit tests/Integration

# Fuera de docker ( con PHP instalado en la maquina )

# Todos los tests
php  vendor/bin/phpunit

# Tests unitarios
php vendor/bin/phpunit tests/Unit

# Tests de integración
php vendor/bin/phpunit tests/Integration

```



## Logging

Los logs se encuentran en `logs/` organizados por contexto:
- `logs/auth/` - Autenticación
- `logs/prices/` - Precios
- `logs/websocket/` - WebSocket
- `logs/api/` - API REST
- `logs/errors/` - Errores

## Documentación Adicional

Ver [ARCHITECTURE.md](./ARCHITECTURE.md) para detalles arquitectónicos completos.

## Desarrollo

### Estructura de commits
- `feat:` Nueva funcionalidad
- `fix:` Corrección de bugs
- `refactor:` Refactorización
- `test:` Tests
- `docs:` Documentación

## Licencia
Proyecto de prueba técnica - NEIX Challenge
