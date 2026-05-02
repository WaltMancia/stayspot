# 🏠 StaySpot — Sistema de Reservas de Espacios

Plataforma de reservas estilo Airbnb construida con Laravel 11 + Vue 3.
Proyecto de portfolio con arquitectura profesional, pagos reales y WebSockets.

[![PHP](https://img.shields.io/badge/PHP-8.3-8892BF)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D)](https://vuejs.org)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED)](https://docker.com)

## 🌐 Demo en vivo

| Servicio | URL |
|---------|-----|
| Frontend | https://stayspot.vercel.app |
| API | https://stayspot-backend.onrender.com |

### 👤 Usuarios demo

| Email | Contraseña | Rol |
|-------|-----------|-----|
| carlos@stayspot.com | password | Anfitrión |
| maria@stayspot.com | password | Anfitrión |
| demo@stayspot.com | password | Huésped |
| admin@stayspot.com | password | Admin |

> Los datos se resetean automáticamente cada noche a medianoche (hora Guatemala).

## ✨ Features

- **Catálogo** con búsqueda y filtros por ciudad, precio y capacidad
- **Calendario de disponibilidad** en tiempo real con WebSockets
- **Sistema de reservas** con prevención de doble booking (DB locking)
- **Pagos** con Stripe (tarjeta de crédito)
- **Panel de anfitrión** con estadísticas e historial
- **Reseñas** con distribución de ratings
- **Auth** con tokens revocables (Laravel Sanctum)

## 🔐 Seguridad implementada

| Vector | Solución |
|--------|---------|
| SQL Injection | Eloquent ORM (prepared statements) |
| XSS | strip_tags + sanitización en Service |
| IDOR | Laravel Policies por recurso |
| Mass Assignment | $fillable en todos los modelos |
| Fuerza bruta | Rate limiting (5 intentos/min en login) |
| Headers HTTP | Middleware SecurityHeaders personalizado |
| CSRF | Laravel CSRF protection |

## 🏗️ Arquitectura
frontend/ (Vue 3 + Vite + Pinia)
│
│ HTTPS API
▼
backend/ (Laravel 11)
├── app/Http/Controllers/   ← Thin controllers
├── app/Services/           ← Business logic
├── app/Models/             ← Eloquent + Relations
├── app/Policies/           ← Authorization
└── app/Events/             ← WebSocket events
│
├── MySQL (datos)
├── Redis (caché + sesiones)
└── Pusher (WebSockets)

## ⚙️ Stack completo

| Capa | Tecnología |
|------|-----------|
| Frontend | Vue 3, Vite, Pinia, Vue Router 4 |
| Estilos | Tailwind CSS v4 |
| Backend | PHP 8.3, Laravel 11 |
| Auth | Laravel Sanctum |
| ORM | Eloquent |
| Base de datos | MySQL 8 |
| Caché | Redis 7 |
| WebSockets | Laravel Echo + Pusher |
| Pagos | Stripe |
| Tests | Pest (PHPUnit) |
| Deploy | Render + Vercel |

## 🚀 Levantar con Docker

```bash
# 1. Clonar
git clone https://github.com/tu-usuario/stayspot.git
cd stayspot

# 2. Variables de entorno
cp .env.example .env
# Edita .env con tus credenciales de Stripe, Pusher, etc.

# 3. Levantar todo
docker-compose up --build

# Servicios disponibles:
# Frontend:  http://localhost
# Backend:   http://localhost:8000
# MySQL:     localhost:3306
# Redis:     localhost:6379
```

## 🔧 Desarrollo local (sin Docker)

```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend (otra terminal)
cd frontend
npm install
npm run dev
```

## 🧪 Tests

```bash
cd backend
php artisan test                         # todos los tests
php artisan test --filter=SpaceSecurity  # solo tests de seguridad
php artisan test --filter=Reservation    # solo tests de reservas
```

## 🔄 Reset de datos demo

```bash
curl -X POST https://stayspot-backend.onrender.com/api/admin/reset-demo \
  -H "X-Reset-Secret: tu_reset_secret"
```