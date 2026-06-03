# Remediación: Incidente de Seguridad — Cryptominer + Backdoors PHP

> **Fecha del incidente:** 2026-06-03
> **Severidad:** CRÍTICA — Sistema comprometido activamente

## Resumen

El servidor de producción tiene:
1. **Jobs maliciosos en tabla `jobs` de MySQL** — se ejecutan con `queue:listen` y recrean los backdoors
2. **Backdoors PHP** (web shells) en 14+ ubicaciones de `public/` — se regeneran automáticamente
3. **Cryptominer XMRig** corriendo como root, minando Monero para atacantes
4. **Persistencia vía crontab** que reinicia el miner cada 3 minutos y al reboot

### Por qué los archivos reaparecen al correr `composer run dev`

`composer run dev` incluye `php artisan queue:listen`. Los jobs maliciosos en la DB se procesan y despliegan los backdoors PHP en 14 ubicaciones de `public/`. Tras ejecutarse, los jobs se eliminan solos de la tabla (comportamiento normal de Laravel) — por eso la tabla aparece vacía después.

---

## Paso 0 — Limpiar tabla `jobs` ANTES de cualquier otra cosa

**Este paso es el más importante.** Si la tabla `jobs` tiene jobs maliciosos y corres `queue:listen`, recrearán todos los backdoors instantáneamente.

```bash
# Conectar con credenciales del .env
mysql -u root -p$(grep DB_PASSWORD /var/www/AELU/.env | cut -d= -f2) aelu_db

# Dentro de MySQL, ver los jobs (verificar antes de borrar):
SELECT id, queue, LEFT(payload, 200) FROM jobs;

# Verificar que el displayName es "Illuminate\Notifications\Notification" (es el indicador malicioso)
# Si es así, truncar:
TRUNCATE TABLE jobs;
TRUNCATE TABLE failed_jobs;
SELECT COUNT(*) FROM jobs; -- debe ser 0
exit;
```

**Si el dump `aelu_db.sql` existe en el servidor**, también tiene los jobs maliciosos. Limpiarlos del dump:
```bash
# En el servidor donde esté el dump:
grep -n "INSERT INTO.*\`jobs\`" /var/www/AELU/aelu_db.sql
# Anota las líneas y elimínalas con sed:
# sed -i 'LINEA_INICIO,LINEA_FINd' /var/www/AELU/aelu_db.sql
```

---

## Paso 1 — Verificar qué está corriendo

```bash
# Verificar proceso del miner
ps aux | grep xmrig

# Ver conexiones activas al pool de minería
ss -tnp | grep 10064

# Ver crontab del root
crontab -l
```

Indicadores de compromiso:
- Proceso `/root/.cache/.fontconfig/.data/.lib/xmrig` corriendo
- Conexión activa a `gulf.moneroocean.stream:10064`
- Entradas en crontab: `.rc-local` y `.cache-sync`

---

## Paso 2 — Aislar el servidor (ANTES de limpiar)

```bash
# Bloquear salida al pool de minería (si tienes iptables)
iptables -A OUTPUT -d gulf.moneroocean.stream -j DROP
iptables -A OUTPUT -p tcp --dport 10064 -j DROP

# O bloquear en tu firewall/panel de control del VPS
# Cloudflare / Hetzner / DigitalOcean: bloquear tráfico saliente puerto 10064
```

---

## Paso 3 — Matar el proceso del miner

```bash
# Obtener PID del miner
XMRIG_PID=$(pgrep -f xmrig)
echo "PID del miner: $XMRIG_PID"

# Matar el proceso
kill -9 $XMRIG_PID

# Verificar que murió
ps aux | grep xmrig | grep -v grep
# Debe estar vacío o mostrar <defunct>
```

---

## Paso 4 — Eliminar persistencia (crontab)

```bash
# Hacer backup del crontab actual
crontab -l > /tmp/crontab_backup_$(date +%Y%m%d).txt
cat /tmp/crontab_backup_*.txt  # revisar contenido

# Eliminar las entradas maliciosas
# Las líneas a eliminar son:
# @reboot sleep 90 && /root/.cache/.fontconfig/.data/.lib/.rc-local >/dev/null 2>&1
# */3 * * * * /root/.cache/.fontconfig/.data/.lib/.cache-sync >/dev/null 2>&1

# Opción A: editar manualmente
crontab -e

# Opción B: eliminar todo el crontab (si no tienes otras entradas legítimas)
crontab -r
```

---

## Paso 5 — Eliminar backdoors PHP

```bash
# Eliminar web shells
rm -f /var/www/AELU/public/build/fonts.php
rm -f /var/www/AELU/public/storage/fonts.php
rm -f /var/www/AELU/public/build/img.php
rm -f /var/www/AELU/public/storage/img.php

# Verificar que no quedan .php sospechosos en public/
find /var/www/AELU/public -name "*.php" -type f
# Solo debe aparecer: /var/www/AELU/public/index.php
```

---

## Paso 6 — Eliminar directorio del miner

```bash
# Ver qué hay antes de borrar
ls -lah /root/.cache/.fontconfig/

# Eliminar todo el directorio (~7.6 MB de binarios maliciosos)
rm -rf /root/.cache/.fontconfig/

# Verificar
ls /root/.cache/
# NO debe aparecer .fontconfig
```

---

## Paso 7 — Auditar logs de acceso

```bash
# Buscar IPs que usaron los web shells
grep -E "fonts\.php|img\.php" /var/log/nginx/access.log 2>/dev/null | tail -50
grep -E "fonts\.php|img\.php" /var/log/apache2/access.log 2>/dev/null | tail -50

# Buscar peticiones POST a esos archivos
grep -E "POST.*(fonts|img)\.php" /var/log/nginx/access.log 2>/dev/null

# Ver logs de Laravel en el período del compromiso
grep -E "error|critical|emergency" /var/www/AELU/storage/logs/laravel.log | tail -100
```

Guarda las IPs de los atacantes para reportar o bloquear.

---

## Paso 8 — Rotar todas las credenciales

```bash
cd /var/www/AELU

# 1. Nueva APP_KEY de Laravel
php artisan key:generate

# 2. Cambiar DB_PASSWORD en .env y en MySQL
mysql -u root -p -e "ALTER USER 'tu_usuario_db'@'localhost' IDENTIFIED BY 'nueva_contraseña_fuerte';"
# Actualizar .env con la nueva contraseña

# 3. Revocar todas las sesiones activas del panel admin
php artisan session:flush 2>/dev/null || true
```

Además, cambiar manualmente:
- [ ] Contraseña de root SSH del servidor
- [ ] Contraseñas de todos los usuarios del panel Filament
- [ ] Cualquier API key almacenada en `.env`
- [ ] Acceso al panel de control del VPS/hosting

---

## Paso 9 — Hardening nginx/apache (prevenir reinfección)

Agregar en la configuración de nginx para el sitio AELU:

```nginx
# Bloquear ejecución de PHP en directorios de uploads/assets
location ~* /public/(storage|build)/.*\.php$ {
    deny all;
    return 404;
}
```

Para Apache, en `.htaccess` dentro de `public/storage/`:
```apache
<Files "*.php">
    Require all denied
</Files>
```

---

## Paso 10 — Verificación final

```bash
# 1. Sin proceso xmrig
ps aux | grep xmrig | grep -v grep
# Debe estar vacío

# 2. Sin conexiones al pool
ss -tnp | grep 10064
# Debe estar vacío

# 3. Sin crontab malicioso
crontab -l | grep -E 'rc-local|cache-sync'
# Debe estar vacío

# 4. Sin directorio del miner
ls /root/.cache/.fontconfig 2>/dev/null && echo "AÚN EXISTE" || echo "OK - eliminado"

# 5. Sin backdoors en public/
find /var/www/AELU/public -name "*.php" | grep -v "^/var/www/AELU/public/index.php"
# Debe estar vacío

# 6. Solo conexiones legítimas
ss -tnp | grep php
```

---

## Timeline del compromiso

| Fecha | Evento |
|---|---|
| Agosto 1, 2024 | `img.php` backdoors instalados (punto de entrada original) |
| Mayo 18-29, 2026 | XMRig miner instalado via backdoor |
| Junio 3, 2026 16:42 | `fonts.php` backdoors actualizados (acceso activo) |
| Junio 3, 2026 17:xx | Incidente detectado y remediación iniciada |

---

## Investigar vector de entrada original

El primer compromiso fue Agosto 2024 vía `img.php` en `public/storage/`. Posibles vectores:

```bash
# Ver versión de Laravel/Filament en Agosto 2024
git log --format="%H %ad %s" --date=short composer.lock | grep "2024-08"
git show <HASH>:composer.lock | grep -E '"laravel/framework"|"filament/filament"'

# Buscar en el código uploads sin validación de tipo
grep -r "store\|upload\|move" /var/www/AELU/app --include="*.php" | grep -v "vendor"
```

Revisar si `public/storage` acepta uploads directos sin validar extensión MIME.

---

## Wallet del atacante (para reporte)

```
48H5PKE5YX2ccnFkzHipMrZ6U1S8ix6Xeh72rx6aAQJvasL4MkBDW2EhobbVST91ch4PftLH4QgzVaHHrfBYQVWiAUProK3
```

Puedes reportar a [MoneroOcean](https://moneroocean.stream) y a tu proveedor de hosting.
