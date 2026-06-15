# Preparación del Servidor para Producción

## Migración a PHP 8.3

El proyecto requiere PHP 8.3+ debido a la dependencia `openspout/openspout ^4.30`.

### Extensiones actuales en PHP 8.2 (verificadas)

- curl, gd, intl, mbstring, mysqli, pdo_mysql, xml, zip

---

### 1. Agregar repositorio de PHP 8.3 (Ubuntu/Debian)

```bash
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
```

### 2. Instalar PHP 8.3 y extensiones necesarias

```bash
sudo apt-get install -y \
  php8.3 \
  php8.3-cli \
  php8.3-fpm \
  php8.3-common \
  php8.3-curl \
  php8.3-gd \
  php8.3-intl \
  php8.3-mbstring \
  php8.3-mysql \
  php8.3-xml \
  php8.3-zip \
  php8.3-opcache
```

### 3. Verificar instalación

```bash
php8.3 --version
php8.3 -m | grep -E "pdo_mysql|mbstring|xml|curl|zip|gd|intl"
```

### 4. Cambiar versión activa de PHP (CLI)

```bash
sudo update-alternatives --set php /usr/bin/php8.3
sudo update-alternatives --set php-config /usr/bin/php-config8.3
sudo update-alternatives --set phpize /usr/bin/phpize8.3
```

### 5. Configurar el servidor web

**Si usas Apache con mod_php:**
```bash
sudo a2dismod php8.2
sudo a2enmod php8.3
sudo systemctl restart apache2
```

**Si usas Nginx con PHP-FPM:**

Actualizar el socket en la configuración del sitio (`/etc/nginx/sites-available/aelu` o similar):
```nginx
# Cambiar de:
fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
# A:
fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
```

```bash
sudo systemctl stop php8.2-fpm
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
sudo systemctl reload nginx
```

### 6. Reinstalar dependencias del proyecto

```bash
cd /var/www/AELU
composer install --no-dev --optimize-autoloader
```

### 7. Generar permisos de Filament Shield

Si se desplegaron nuevos módulos (resources/policies), registrar sus permisos:

```bash
# Resource específico (genera permisos + policy automáticamente)
php artisan shield:generate --resource=NombreResource

# Múltiples resources a la vez
php artisan shield:generate --resource=WorkshopTemplate,OtroResource

# Solo si es un deploy inicial o se requiere regenerar todo
php artisan shield:generate --all
```

> **Nota:** Usar `--all` en producción puede pisar asignaciones manuales de permisos a roles. Preferir `--resource` del módulo nuevo.

#### HU-I08 — Inscripción por Recuperación (feature/incripcion-recuperacion)

```bash
php artisan shield:generate --resource=Tag
```

---

### 7b. Seeders de datos maestros

Ejecutar seeders de catálogos cuando se despliegan nuevos módulos que requieren datos iniciales:

```bash
# HU-I08: Motivos de recuperación (tabla tags)
php artisan db:seed --class=TagSeeder
```

---

### 8. Verificar que todo funciona

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan about
```

---

## Checklist de validación post-migración

- [ ] `php --version` muestra 8.3.x
- [ ] `php -m | grep pdo_mysql` devuelve resultado
- [ ] `composer install` sin errores
- [ ] `php artisan about` sin errores
- [ ] Panel de Filament carga correctamente
- [ ] Exportaciones Excel funcionan (openspout)
- [ ] Importaciones Excel funcionan (maatwebsite/excel)
- [ ] Logs del visor (filament-log-viewer / sushi) funcionan
- [ ] Cron de Laravel ejecuta sin errores: `php artisan schedule:run`

---

## Notas

- El servidor actualmente tiene PHP **8.2.30** con OPcache habilitado.
- `openspout ^4.30` requiere PHP ~8.3.0 || ~8.4.0 (incompatible con 8.2).


## Permisos de storage (pail / logs)

Si aparece `Permission denied` al escribir en `storage/pail/` o `storage/logs/`, es porque algún comando artisan se ejecutó como `root` y creó archivos con `owner=root`. El web server (`www-data`) no puede escribirlos.

**Fix inmediato:**
```bash
rm -f /var/www/AELU/storage/pail/*.pail
chown -R www-data:www-data /var/www/AELU/storage/
chmod -R 775 /var/www/AELU/storage/
```

**Regla permanente — siempre correr artisan como www-data:**
```bash
sudo -u www-data php artisan pail
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:clear
# etc.
```

> En producción `php artisan pail` no se usa. El riesgo es en scripts de deploy que corran artisan como root.

---

## Elminar una migracion para volver a ejecutarlo

``````
DB::table('migrations')->where('migration', '2026_04_28_000000_update_system_settings_schedule_days')->delete();
````````
