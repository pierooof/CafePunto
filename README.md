# ☕ CaféPunto

Tarjeta de fidelización digital para cafeterías. El cliente se registra, enseña su QR en la barra y el barista le suma sellos desde el móvil; al llegar a **10 sellos**, la siguiente bebida es gratis.

Proyecto de portafolio: PHP 8.3 sin framework (MVC propio), MySQL 8 y despliegue con Docker en Railway.

> 🔗 Demo: <https://cafe.pierooof.dev>

## Capturas

Todas en formato móvil (390 px), que es el uso real detrás de la barra.

**Visitante**

| Inicio | Login (claro) | Login (oscuro) |
| --- | --- | --- |
| <img src="docs/screenshots/01-inicio.png" width="240" alt="Pantalla de inicio"> | <img src="docs/screenshots/02-login-claro.png" width="240" alt="Login en modo claro"> | <img src="docs/screenshots/03-login-oscuro.png" width="240" alt="Login en modo oscuro"> |

**Registro y límite de intentos**

| Contraseña visible (ojo) | Login bloqueado tras 5 fallos |
| --- | --- |
| <img src="docs/screenshots/04-registro-contrasena-visible.png" width="240" alt="Registro con la contraseña visible"> | <img src="docs/screenshots/05-login-bloqueado.png" width="240" alt="Mensaje de demasiados intentos"> |

**Cliente: tarjeta, QR e historial**

| Claro | Oscuro |
| --- | --- |
| <img src="docs/screenshots/06-tarjeta-cliente-claro.png" width="240" alt="Tarjeta del cliente en modo claro"> | <img src="docs/screenshots/07-tarjeta-cliente-oscuro.png" width="240" alt="Tarjeta del cliente en modo oscuro"> |

**Barista: panel y ficha de cliente**

| Panel (claro) | Panel (oscuro) | Ficha de cliente |
| --- | --- | --- |
| <img src="docs/screenshots/08-panel-barista-claro.png" width="240" alt="Panel del barista en modo claro"> | <img src="docs/screenshots/09-panel-barista-oscuro.png" width="240" alt="Panel del barista en modo oscuro"> | <img src="docs/screenshots/10-ficha-cliente.png" width="240" alt="Ficha de cliente con botones de sellos"> |

**Administrador: gestión del equipo**

| Claro | Oscuro |
| --- | --- |
| <img src="docs/screenshots/11-equipo-claro.png" width="240" alt="Pantalla Equipo en modo claro"> | <img src="docs/screenshots/12-equipo-oscuro.png" width="240" alt="Pantalla Equipo en modo oscuro"> |

## Funcionalidades

- **Dos roles**: barista (empleado) y cliente.
- **Cliente**: se registra con nombre y teléfono, ve su tarjeta con los sellos, su **QR personal** y su historial completo.
- **Barista**: busca al cliente por teléfono (parcial) o nombre, o **escanea su QR** con la cámara; suma 1, 2 o 3 sellos y **canjea la bebida gratis** al llegar a 10.
- **Panel con estadísticas**: sellos de hoy, canjes del mes, nº de clientes y ranking de los clientes más fieles.
- **Historial** completo de cada tarjeta (quién sumó cada sello y cuándo).
- **Equipo**: los baristas con permiso de administrador añaden y eliminan baristas desde el panel.
- **Modo claro y oscuro** con un botón sol/luna animado; respeta el tema del sistema y recuerda tu elección.
- **Mostrar/ocultar contraseña** en todos los formularios para evitar erratas en el móvil.
- **Instalable** como app (manifest + iconos): «Añadir a la pantalla de inicio» en Android y iOS.
- Diseño cálido de cafetería, pensado para usarse con una mano en el móvil detrás de la barra.

## Stack

| Capa | Tecnología |
| --- | --- |
| Backend | PHP 8.3, MVC propio (router, vistas, sesión, CSRF), autoload con Composer |
| Base de datos | MySQL 8 con PDO (consultas parametrizadas, `utf8mb4`) |
| QR | [`chillerlan/php-qrcode`](https://github.com/chillerlan/php-qrcode) (SVG, sin extensión GD) |
| Escáner | `BarcodeDetector` del navegador o [jsQR](https://github.com/cozmo/jsQR) como alternativa |
| Front | CSS propio (sin frameworks), JS mínimo |
| Tests | PHPUnit 11 |
| Despliegue | Docker (`php:8.3-apache`) en Railway |

### Estructura

```
public/            index.php (front controller) y assets
src/Core/          Router, Request/Response, Session, CSRF, Config, Proxy, Database…
src/Controllers/   Auth, Customer, Barista, Team, Health
src/Models/        UserRepository, StampLog (consultas)
src/Services/      StampRules (reglas puras), LoyaltyService (transacciones), LoginThrottle, Migrator, QrService
templates/         Vistas PHP (todo escapado con e())
db/migrations/     SQL versionado
bin/               migrate.php, seed.php, create-barista.php, entrypoint.sh
tests/             PHPUnit
```

### Modelo de datos

- `users`: `id, name, phone (único), password_hash, role (barista|customer), is_admin, qr_token (único), stamps (0–10, con CHECK), created_at`
- `stamp_log`: `id, customer_id, staff_id, type (stamp|redeem), amount, created_at`
- `login_attempts`: `id, ip, phone, created_at` (fallos de login recientes, para el límite de intentos)

El saldo vive en `users.stamps` y cada movimiento se registra en `stamp_log`, ambos dentro de una transacción con el cliente bloqueado (`SELECT … FOR UPDATE`), de modo que dos baristas a la vez no pueden pasarse de 10 ni canjear dos veces.

## Seguridad

- Contraseñas con `password_hash` (bcrypt) y verificación en tiempo constante contra usuarios inexistentes.
- Consultas parametrizadas (`PDO`, sin emulación de prepares); los comodines de `LIKE` se escapan.
- **Límite de intentos de login**: 5 fallos por teléfono o 20 por IP en 15 minutos bloquean el acceso (HTTP 429 con `Retry-After`) y ni siquiera se comprueba la contraseña. Un login correcto borra los fallos. La IP es la real del cliente (`CF-Connecting-IP`/`X-Forwarded-For`).
- Token CSRF en todos los formularios POST.
- Salida siempre escapada (`e()`), cabeceras seguras: CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` y HSTS en producción.
- Cookie de sesión `HttpOnly`, `SameSite=Lax` y `Secure` en producción; el id se regenera al iniciar sesión.
- La redirección tras el login solo admite rutas internas (sin redirecciones abiertas).
- Las URLs absolutas (QR, redirecciones) se construyen desde `APP_URL`, nunca desde la cabecera `Host`.

## Ejecución en local

Requisitos: PHP ≥ 8.3 con `pdo_mysql` y `mbstring`, Composer y MySQL 8.

```bash
git clone <tu-repo> && cd CafePunto
composer install
cp .env.example .env          # ajusta MYSQL_URL a tu MySQL local
```

Crea la base de datos vacía (ajusta usuario y contraseña):

```bash
mysql -u root -p -e "CREATE DATABASE cafepunto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Migra, siembra los datos demo y arranca:

```bash
php bin/migrate.php
php bin/seed.php
php -S 0.0.0.0:8000 -t public public/index.php
```

Abre <http://localhost:8000>. En `APP_ENV=development` la app funciona con http, sin forzar HTTPS.

> Para probar el escáner de QR con la cámara del móvil hace falta HTTPS (los navegadores solo dan acceso a la cámara en contextos seguros; `localhost` cuenta como seguro). Puedes usar la cámara nativa del móvil para escanear el QR y abrir el enlace.

### Tests

```bash
composer test
```

Los tests unitarios (reglas de sellos, proxies/HTTPS/IP, configuración, CSRF, router, teléfonos) no necesitan base de datos. Los de integración (`LoyaltyServiceTest`: migraciones, transacciones, estadísticas, búsqueda) se activan con una base de datos **vacía y desechable**; ¡borran sus tablas!:

```bash
mysql -u root -p -e "CREATE DATABASE cafepunto_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
TEST_MYSQL_URL=mysql://root:TU_PASSWORD@127.0.0.1:3306/cafepunto_test composer test
```

## Variables de entorno

Toda la configuración va por variables de entorno (ver [`.env.example`](.env.example)).

| Variable | Descripción | Por defecto |
| --- | --- | --- |
| `APP_ENV` | `development` o `production` (HTTPS forzado, cookies `Secure`, HSTS) | `production` |
| `APP_URL` | URL pública sin barra final; base del QR y de las redirecciones. En producción debe ser `https://…` | `http://localhost:8000` (solo en desarrollo) |
| `APP_TIMEZONE` | Zona horaria para «sellos de hoy» y «canjes del mes» | `Europe/Madrid` |
| `PORT` | Puerto de escucha (Railway lo inyecta) | `8080` en Docker |
| `TRUSTED_PROXIES` | Nº de proxies de confianza delante de la app | `1` |
| `MYSQL_URL` | Conexión completa `mysql://usuario:clave@host:puerto/base` | – |
| `MYSQLHOST` `MYSQLPORT` `MYSQLUSER` `MYSQLPASSWORD` `MYSQLDATABASE` | Alternativa a `MYSQL_URL` (las inyecta Railway) | – |
| `SEED_DEMO` | `true` para sembrar los datos demo al arrancar | – |
| `DEMO_HINT` | `true` para mostrar las credenciales demo en el login | – |

## Despliegue en Railway

La app se despliega desde GitHub con el [`Dockerfile`](Dockerfile) y [`railway.json`](railway.json) (healthcheck en `/health`). Al arrancar, el contenedor ejecuta las migraciones (`bin/migrate.php`, que espera a que MySQL esté listo) y, si `SEED_DEMO=true`, los datos de ejemplo.

1. **Sube el repositorio a GitHub.**
2. **Crea el proyecto**: en [railway.com](https://railway.com) → *New Project* → *Deploy from GitHub repo* → elige este repositorio. Railway detecta el `Dockerfile`.
3. **Añade MySQL**: en el proyecto → *New* → *Database* → *Add MySQL*.
4. **Referencia las variables de MySQL** en el servicio de la app → *Variables* → *New Variable* (o *Raw Editor*):
   ```
   MYSQL_URL=${{MySQL.MYSQL_URL}}
   ```
   (Si tu servicio de base de datos tiene otro nombre, cambia `MySQL` por ese nombre.) Alternativamente puedes referenciar `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD` y `MYSQLDATABASE` de la misma forma.
5. **Resto de variables** del servicio de la app:
   ```
   APP_ENV=production
   APP_URL=https://<tu-dominio>        # primero el *.up.railway.app que te da Railway; luego tu subdominio
   TRUSTED_PROXIES=1                   # 2 si pones Cloudflare delante
   APP_TIMEZONE=Europe/Madrid
   SEED_DEMO=true
   DEMO_HINT=true
   ```
   No definas `PORT`: Railway lo inyecta.
6. **Genera un dominio**: servicio → *Settings* → *Networking* → *Generate Domain*. Copia esa URL en `APP_URL` (con `https://`) y redespliega.
7. Comprueba `https://<tu-dominio>/health` → `{"status":"ok"}`.

> Con `SEED_DEMO=true` cualquiera puede entrar con las credenciales demo (útil para el portafolio). Si prefieres una instalación limpia, ponlo a `false` o bórralo.

Comandos manuales (con la [CLI de Railway](https://docs.railway.com/guides/cli), `railway run`):

```bash
railway run php bin/migrate.php
railway run php bin/seed.php
```

## Dominio propio con Cloudflare (subdominio de pierooof.dev)

Ejemplo con `cafe.pierooof.dev`:

1. En Railway: servicio → *Settings* → *Networking* → *Custom Domain* → escribe `cafe.pierooof.dev`. Railway muestra un **CNAME** de destino (algo como `xxxxxxxx.up.railway.app`) y, a veces, un registro **TXT** de verificación.
2. En Cloudflare → *DNS* → *Records* → añade:
   - `CNAME` · nombre `cafe` · destino el valor que te dio Railway.
   - El `TXT` de verificación, si Railway lo pide.
   
   Déjalo primero en **«Solo DNS»** (nube gris) hasta que Railway emita el certificado y marque el dominio como válido.
3. Cuando Railway lo valide, puedes activar el proxy de Cloudflare (nube naranja). En Cloudflare → *SSL/TLS* → *Overview* usa el modo **Full (strict)**. Con *Flexible* habría un bucle de redirecciones, porque la app fuerza HTTPS.
4. Actualiza las variables del servicio en Railway y redespliega:
   ```
   APP_URL=https://cafe.pierooof.dev
   TRUSTED_PROXIES=2        # Cloudflare + proxy de Railway
   ```
5. Comprueba que el QR de una tarjeta apunta a `https://cafe.pierooof.dev/c/…` y que las cookies llevan `Secure`.

**Cómo detecta la app los proxies**

- HTTPS: se deduce de `X-Forwarded-Proto`. Si `TRUSTED_PROXIES=0` se ignora y, en producción, la app redirigiría en bucle a HTTPS: déjalo en 1 o más detrás de Railway.
- IP real del cliente: si llega `CF-Connecting-IP` se usa esa; si no, se toma `X-Forwarded-For` contando `TRUSTED_PROXIES` saltos desde la derecha, de modo que una cabecera falsificada por el cliente no cuenta.
- `CF-Connecting-IP` solo es fiable si el tráfico pasa por Cloudflare; accediendo directamente al dominio `*.up.railway.app` un cliente podría falsificarla. Si te importa, desactiva el dominio de Railway cuando uses el propio.

## Gestión del equipo

Solo los baristas **administradores** ven el botón «Gestionar equipo» en el panel y pueden entrar en `/barista/team`: añadir baristas (con o sin permiso de administrador) y eliminarlos. Nadie puede eliminarse a sí mismo, así que siempre queda un administrador.

Los baristas de demostración **no** son administradores a propósito: la demo tiene credenciales públicas y así nadie puede borrar ni crear cuentas reales. El primer administrador se crea por línea de comandos:

```bash
php bin/create-barista.php --admin "Nombre Apellido" 600123456 "contraseña-de-8+"
```

En Railway, ejecútalo en la pestaña **Console** del servicio. Sin `--admin` se crea un barista normal. La migración `005` convierte en administradores a los baristas que ya existieran (salvo los dos de la demo).

## Credenciales demo

Se crean con `SEED_DEMO=true` (o `php bin/seed.php`):

| Rol | Teléfono | Contraseña |
| --- | --- | --- |
| Barista | `600000001` | `barista123` |
| Cliente | `600000002` | `cliente123` |

Además hay otro barista y once clientes con historial de los últimos meses para que el panel muestre estadísticas.

## Mejoras futuras

- Recuperación de contraseña.
- Sesiones en base de datos para que sobrevivan a los redespliegues.
- Pase digital para Apple Wallet y Google Wallet (el `qr_token` ya sirve como contenido del pase).
- Programa configurable por local (nº de sellos, recompensas), multi-sucursal.
- Anular el último sello (deshacer) y exportar movimientos a CSV.
- Modo sin conexión (service worker) para la tarjeta.
- Notificaciones al cliente («te falta 1 sello»).
- Bloqueo por pareja IP + teléfono, para que un atacante no pueda bloquear a un cliente legítimo.
- Tests end-to-end del flujo completo.
