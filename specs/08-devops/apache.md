# Especificación de Apache HTTP Server

**Proyecto:** UCT Ecommerce  

**Equipo:** Los Takas — Arquitectura & DevOps  

**Versión:** 1.0  

**Estado:** Sprint 1  

**Última actualización:** 2025  

---

## 1. Propósito y Objetivos

Este documento especifica la configuración de Apache HTTP Server para la aplicación UCT Ecommerce. Define requisitos de versión del servidor, configuración de *virtual hosts*, reglas de reescritura de URL, *security headers*, integración con PHP y restricciones operacionales.

Apache actúa como punto de entrada de todo el tráfico HTTP, enruta las requests al *front controller* en PHP y sirve los assets estáticos.

---

## 2. Versión del Servidor

| Propiedad | Valor |
| --- | --- |
| Servidor | Apache HTTP Server |
| Versión requerida | 2.4.x |
| MPM | `prefork` (para PHP con mod_php) o `event` (para PHP-FPM) |
| Integración PHP | mod_php o PHP-FPM vía mod_proxy_fcgi |

---

## 3. Módulos Apache Requeridos

Los siguientes módulos deben estar habilitados (`a2enmod` en Debian/Ubuntu):

| Módulo | Propósito |
| --- | --- |
| `mod_rewrite` | Reescritura de URLs para patrón *front controller* |
| `mod_headers` | Definir headers HTTP personalizados |
| `mod_ssl` | Soporte HTTPS en producción |
| `mod_deflate` | Compresión gzip de respuestas |
| `mod_expires` | Headers de cache-control para assets estáticos |
| `mod_php` (o `mod_proxy_fcgi`) | Procesamiento de requests PHP |

Para habilitar los módulos requeridos:

```bash
sudo a2enmod rewrite headers ssl deflate expires
```

---

## 4. Document Root

El *document root* de Apache debe apuntar al directorio `public/` del proyecto, no a la raíz del proyecto. Esto evita acceso directo a archivos fuente PHP, archivos de configuración y el `.env`.

```
Project root:   /var/www/uct-ecommerce/
Document root:  /var/www/uct-ecommerce/public/
```

**Justificación:** Los archivos en `src/`, `.env`, `composer.json` y `vendor/` nunca deben ser accesibles vía HTTP.

---

## 5. Configuración de Virtual Host

### 5.1 Desarrollo (HTTP)

```
<VirtualHost *:80>
    ServerName uct-ecommerce.local
    DocumentRoot /var/www/uct-ecommerce/public

    <Directory /var/www/uct-ecommerce/public>
        Options -Indexes -FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/uct-ecommerce-error.log
    CustomLog ${APACHE_LOG_DIR}/uct-ecommerce-access.log combined
</VirtualHost>
```

### 5.2 Producción (HTTPS)

```
<VirtualHost *:80>
    ServerName ecommerce.uct.cl
    RewriteEngine On
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName ecommerce.uct.cl
    DocumentRoot /var/www/uct-ecommerce/public

    SSLEngine on
    SSLCertificateFile     /etc/ssl/certs/uct-ecommerce.crt
    SSLCertificateKeyFile  /etc/ssl/private/uct-ecommerce.key

    <Directory /var/www/uct-ecommerce/public>
        Options -Indexes -FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

    ErrorLog  ${APACHE_LOG_DIR}/uct-ecommerce-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/uct-ecommerce-ssl-access.log combined
</VirtualHost>
```

---

## 6. Reescritura de URL — `.htaccess`

El archivo `.htaccess` en `public/` enruta todas las requests que no correspondan a archivo/directorio a `index.php` (patrón *front controller*):

```
Options -MultiViews -Indexes
RewriteEngine On

# Permitir acceso directo a archivos y directorios existentes (assets, imágenes)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Enrutar el resto al front controller
RewriteRule ^ index.php [QSA,L]
```

**Efecto:** Una request a `/api/v1/products?category=shoes` se maneja transparentemente por `public/index.php` con `$_SERVER['REQUEST_URI']` seteado a `/api/v1/products?category=shoes`.

**Requisito:** Debe estar configurado `AllowOverride All` en el bloque `<Directory>` del virtual host; si no, Apache ignorará el `.htaccess` sin avisar.

---

## 7. Security Headers

Los siguientes headers de seguridad HTTP deben estar presentes en todas las respuestas:

```
# En el virtual host o en .htaccess
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

En producción, además agregar:

```
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

---

## 8. Headers CORS

Los headers CORS para la API se definen a nivel Apache para asegurar que estén presentes en toda respuesta, incluyendo errores:

```
# Permitir requests desde el origen del frontend
SetEnvIf Origin "^https://uct-ecommerce\.local$" CORS_ORIGIN=$0
Header always set Access-Control-Allow-Origin "%{CORS_ORIGIN}e" env=CORS_ORIGIN
Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Authorization, Content-Type, X-Request-ID"
Header always set Access-Control-Max-Age "86400"

# Manejo de preflight
RewriteEngine On
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^ - [R=200,L]
```

---

## 9. Cache de Assets Estáticos

Los archivos estáticos (CSS, JS, imágenes, fuentes) en `public/assets/` deben tener headers de cache de larga duración:

```
<LocationMatch "^/assets/">
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
    Header append Cache-Control "public, immutable"
</LocationMatch>
```

Para assets versionados (filename incluye hash), `immutable` es apropiado. Para assets no versionados, usar `"access plus 1 week"` y omitir `immutable`.

---

## 10. Compresión Gzip

```
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css
    AddOutputFilterByType DEFLATE application/javascript application/json
    AddOutputFilterByType DEFLATE image/svg+xml application/xml

    BrowserMatch ^Mozilla/4 gzip-only-text/html
    BrowserMatch ^Mozilla/4\.0[678] no-gzip
    BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
</IfModule>
```

---

## 11. Integración con PHP

### 11.1 Usando mod_php

Si PHP se carga vía `mod_php`, no se necesita configuración adicional más allá de habilitar el módulo:

```bash
sudo a2enmod php8.2
```

### 11.2 Usando PHP-FPM (recomendado en producción)

```
<FilesMatch \.php$>
    SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
</FilesMatch>
```

Configuración del pool PHP-FPM en `/etc/php/8.2/fpm/pool.d/uct-ecommerce.conf`:

```
[uct-ecommerce]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
```

---

## 12. Logging

| Tipo de log | Ruta | Formato |
| --- | --- | --- |
| Access log (dev) | `/var/log/apache2/uct-ecommerce-access.log` | `combined` |
| Error log (dev) | `/var/log/apache2/uct-ecommerce-error.log` | default |
| Access log (prod) | `/var/log/apache2/uct-ecommerce-ssl-access.log` | `combined` |
| Error log (prod) | `/var/log/apache2/uct-ecommerce-ssl-error.log` | default |

La rotación de logs se maneja con `logrotate`, rotación diaria y retención de 14 días.

---

## 13. Restricciones de Seguridad

1. `Options -Indexes` debe estar seteado — está prohibido el listado de directorios en todas las rutas.
2. El document root debe ser `public/`, no la raíz del proyecto.
3. El acceso a archivos fuera de `public/` debe retornar `403 Forbidden`.
4. `ServerTokens` debe ser `Prod` (oculta versión de Apache):
    
    ```
    ServerTokens Prod
    ServerSignature Off
    ```
    
5. En producción, se debe forzar redirección HTTP → HTTPS (301).
6. El `.htaccess` no debe exponer internos de la app. Patrones de `RewriteRule` demasiado permisivos están prohibidos.

---

## 14. Criterios de Aceptación

- [ ]  Apache 2.4.x está instalado y ejecutándose.
- [ ]  `mod_rewrite` está habilitado y todas las requests que no son archivos enrutan a `index.php`.
- [ ]  Document root apunta a `public/` — el acceso HTTP directo a `src/` retorna `403`.
- [ ]  Headers de seguridad (`X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`) están presentes en todas las respuestas.
- [ ]  Headers CORS están presentes en todas las respuestas del API, incluyendo errores y preflight `OPTIONS`.
- [ ]  El listado de directorios retorna `403` en todas las rutas.
- [ ]  Los assets estáticos se sirven con `Cache-Control: public` y expiración apropiada.
- [ ]  Compresión gzip activa para HTML, CSS, JS y respuestas JSON.
- [ ]  En producción, todo HTTP redirige a HTTPS con status `301`.
- [ ]  `ServerTokens Prod` está configurado — la versión de Apache no se expone en headers de respuesta.