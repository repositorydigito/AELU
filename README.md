# AELU - Sistema de Gestión de Talleres PAMA

Sistema de inscripciones y gestión de talleres para el Programa del Adulto Mayor Activo (PAMA).

## Requisitos

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL

## Instalación y configuración local

### 1. Clonar el repositorio e instalar dependencias

```bash
git clone <url-del-repositorio>
cd aelu

composer install
npm install
```

### 2. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita el archivo `.env` con tus credenciales de base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aelu
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Ejecutar las migraciones

```bash
php artisan migrate
```

### 4. Configurar Filament Shield (permisos y roles)

Genera los permisos para todos los recursos del panel:

```bash
php artisan shield:generate --all
```

Cuando se te pida seleccionar el panel, elige `admin`.

Ingresa el email del usuario administrador cuando se solicite.

### 5. Ejecutar los seeders

```bash
php artisan db:seed
```

### 5.1. Ejecutar y asignar el usuario admin los permisos

```bash
php artisan shield:super-admin
```

### 6. Limpiar y optimizar la caché

```bash
php artisan optimize:clear
```

### 7. Levantar el servidor de desarrollo

```bash
npm run dev
```

Esto levanta en paralelo:
- Servidor PHP en `<localhost>`
- Worker de colas
- Visor de logs (Pail)
- Servidor de assets Vite

Accede al panel en: `<localhost>/admin`

---

## Comandos útiles

```bash
# Limpiar cachés individualmente
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerar permisos de Shield
php artisan shield:generate --all

# Ver logs en tiempo real
php artisan pail

# Compilar assets para producción
npm run build
```
