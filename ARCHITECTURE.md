# Arquitectura de la Plataforma FX

## Decisión Arquitectónica: Arquitectura por Capas (Layered Architecture)

### Justificación
Se eligió una arquitectura por capas para garantizar:
- **Separación clara de responsabilidades** (Single Responsibility Principle)
- **Bajo acoplamiento** entre componentes
- **Alta cohesión** dentro de cada capa
- **Facilidad de testing** y mantenimiento
- **Escalabilidad** horizontal y vertical

---

## Estructura de Capas

### 1. Capa de Presentación (Presentation Layer)
**Responsabilidad**: Manejar peticiones HTTP, validar entrada, formatear salida.

**Componentes**:
- `src/Application/Controllers/` - Controladores REST API
- `public/api/` - Endpoints públicos

**Principios aplicados**:
- Thin controllers (lógica delegada a servicios)
- Validación de entrada
- Manejo de errores HTTP

---

### 2. Capa de Aplicación (Application Layer)
**Responsabilidad**: Orquestar casos de uso y lógica de negocio.

**Componentes**:
- `src/Application/Services/` - Servicios de aplicación
- `src/Application/UseCases/` - Casos de uso específicos

**Principios aplicados**:
- Single Responsibility (cada servicio un propósito)
- Dependency Inversion (depende de interfaces, no implementaciones)

---

### 3. Capa de Dominio (Domain Layer)
**Responsabilidad**: Entidades de negocio y reglas de dominio.

**Componentes**:
- `src/Domain/Entities/` - Entidades de dominio
- `src/Domain/ValueObjects/` - Objetos de valor
- `src/Domain/Interfaces/` - Contratos/Interfaces

**Principios aplicados**:
- Rich Domain Model (lógica de negocio en entidades)
- Encapsulación

---

### 4. Capa de Infraestructura (Infrastructure Layer)
**Responsabilidad**: Acceso a datos, servicios externos, WebSocket.

**Componentes**:
- `src/Infrastructure/Repositories/` - Implementaciones de repositorios
- `src/Infrastructure/Database/` - Configuración y conexión DB
- `src/Infrastructure/WebSocket/` - Servidor WebSocket
- `src/Infrastructure/Logger/` - Sistema de logging

**Principios aplicados**:
- Dependency Inversion (implementa interfaces del dominio)
- Open/Closed (extensible sin modificar)

---

## Comunicación entre Capas

```
Frontend (HTML/JS)
    ↓ HTTP REST
Capa de Presentación (Controllers)
    ↓ Dependencias
Capa de Aplicación (Services/UseCases)
    ↓ Interfaces
Capa de Dominio (Entities/Interfaces)
    ↑ Implementaciones
Capa de Infraestructura (Repositories/DB/WebSocket)
```

**Regla de dependencia**: Las capas superiores dependen de las inferiores, pero el dominio NO depende de infraestructura (Dependency Inversion Principle).

---

## WebSocket Architecture

### Canales Separados (Separated Channels Pattern)

**Justificación**: Separación de responsabilidades y gestión uniforme de datos.

#### Canal 1: Precios (Prices Channel)
- **Responsabilidad**: Transmitir precios y cantidades en tiempo real
- **Origen**: Generador Python → Backend PHP → Frontend
- **Formato**: JSON con timestamp, instrumento, precio, cantidad

#### Canal 2: Configuraciones (Configurations Channel)
- **Responsabilidad**: Enviar/recibir configuraciones de usuario
- **Flujo**: Frontend → Backend PHP (validación) → Confirmación
- **Formato**: JSON con usuario, timestamp, array de configuraciones

**Ventajas**:
- Escalabilidad independiente
- Manejo de errores específico por canal
- Reconexión selectiva

---

## Gestión de Estado

### Frontend State Management
- **Estado local**: Configuraciones de usuario (sesión)
- **Estado sincronizado**: Precios desde backend
- **Patrón**: Observer/Publisher-Subscriber para actualizaciones

### Backend State
- **Persistencia**: Base de datos (usuarios, notificaciones, configuraciones)
- **Cache**: Sesiones en memoria para configuraciones activas
- **Sincronización**: WebSocket mantiene estado consistente

---

## Sistema de Logging

### Estructura por Contexto
```
logs/
  ├── auth/          # Autenticación
  ├── prices/        # Generación de precios
  ├── websocket/     # Comunicación WebSocket
  ├── api/           # Peticiones REST
  └── errors/        # Errores generales
```

**Formato**: JSON estructurado con contexto, nivel, timestamp, mensaje.

---

## Base de Datos

### Esquema Principal
- `users` - Usuarios del sistema
- `instruments` - Instrumentos FX (EUR/USD, ARG/USD, ARG/EUR)
- `notifications` - Historial de notificaciones
- `user_configurations` - Configuraciones por usuario (sesión)

---

## Principios SOLID Aplicados

1. **S**ingle Responsibility: Cada clase tiene una única razón para cambiar
2. **O**pen/Closed: Extensible sin modificar código existente
3. **L**iskov Substitution: Interfaces bien definidas, implementaciones intercambiables
4. **I**nterface Segregation: Interfaces específicas, no genéricas
5. **D**ependency Inversion: Dependencias hacia abstracciones, no concreciones

---

## Decisiones Técnicas Adicionales

### Python para Generador de Precios
- **Razón**: Valorado en requisitos, mejor para generación de datos aleatorios
- **Comunicación**: Python → PHP vía HTTP/WebSocket interno

### Docker Compose
- **Servicios**: PHP-FPM, Nginx, MariaDB, Python (generador)
- **Ventaja**: Desarrollo y despliegue consistente

### AJAX para Comunicación REST
- **Razón**: Valorado en requisitos, mejor UX que recargas completas
- **Uso**: Login, consulta de notificaciones históricas

---

## Escalabilidad

- WebSocket puede escalarse horizontalmente (Redis Pub/Sub)
- Base de datos puede replicarse
- Cache layer puede agregarse sin cambiar lógica de negocio
