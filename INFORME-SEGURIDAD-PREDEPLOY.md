# 🔒 Informe de seguridad y estado pre-deploy — QuadCore

**Proyecto:** QuadCore (ecommerce-project-s1)
**Rama:** `team-1-heavyduty`
**Dominio objetivo:** `quadcorestore.com` (Cloudflare)
**Despliegue previsto:** Lenovo Z40 con Ubuntu Server + Cloudflare Tunnel (sin VPS)
**Fecha:** 2026-06-22

---

## 1. Auditoría de seguridad — hallazgos

### ✅ Verificado y correcto (no es suposición; revisado en el código)

| Vector | Resultado |
|---|---|
| **SQL Injection** | 88 prepared statements, 0 queries con input concatenado. Las 13 `query()` crudas son agregados fijos del dashboard (COUNT/SUM/`SELECT 1`), sin input de usuario. |
| **JWT** | HS256 con `hash_hmac` + comparación `hash_equals` (tiempo constante) y validación de expiración (`exp < time()`). Inmune a bypass `alg:none` y a timing attacks. |
| **IDOR** (ver pedido ajeno) | Bloqueado: `CheckoutController::detallePedido` valida `pedido.id_usuario === user.id` (salvo rol admin). |
| **Passwords** | `password_hash` bcrypt cost 12 + `password_verify`. Sin md5/sha1. Incluye **bloqueo por fuerza bruta** (`intentos_fallidos` / `bloqueado_hasta`). |
| **XSS** | `escapeHtml` aplicado de forma consistente (52 usos); `catalogo.js` sin interpolaciones sin escapar. |
| **CORS** | En producción se restringe a `APP_URL` (no `*`). |
| **Errores** | `APP_DEBUG=false` desactiva `display_errors` → no se filtran stack traces. |
| **`.env`** | Ignorado por git y no commiteado. |

### ⚠️ Riesgos hallados (ambos de configuración, no de la app)

| # | Riesgo | Impacto |
|---|---|---|
| **R1** | `JWT_SECRET` tenía un **default público** (`clave_secreta_por_defecto_cambiar_en_produccion`). El repo es público → si se deployaba sin setear el secreto, cualquiera podía **falsificar tokens de admin**. | **Crítico** |
| **R2** | Los headers de seguridad (CSP, `X-Frame-Options`, `nosniff`) vivían **solo en `public/.htaccess` (Apache)**. El deploy usa **nginx**, que **no lee `.htaccess`** → los headers desaparecían en silencio. | Medio |

---

## 2. Soluciones aplicadas (eliminadas de raíz, no documentadas como workaround)

| # | Solución | Commit |
|---|---|---|
| **R1** | `config/app.php`: la app **aborta con HTTP 500** si corre en `APP_ENV=production` con el `JWT_SECRET` vacío o el default. Imposible servir producción con el secreto conocido. Además `APP_DEBUG` se fuerza a `false` en producción aunque el `.env` diga otra cosa. | `f336b27` |
| **R2** | `deploy/nginx.conf`: configuración de nginx **versionada** con los mismos headers del `.htaccess`. Deja de ser un paso manual olvidable. | `f336b27` |

> Resultado: los dos riesgos quedan cerrados a nivel de repositorio. No dependen de que alguien "se acuerde" al deployar.

---

## 3. Checklist OBLIGATORIO antes de exponer el sitio

- [ ] `.env` de producción con **`JWT_SECRET` propio** (`openssl rand -base64 48`). *(Si falta, la app ahora se niega a arrancar — R1.)*
- [ ] `APP_ENV=production` y `APP_URL=https://quadcorestore.com` en `.env`.
- [ ] **Password fuerte** para el usuario MySQL `ecommerce_app` (`ALTER USER ...`), reflejado en `DB_PASS`.
- [ ] **Borrar `public/setup.php`** (diagnóstico que filtra entorno) o bloquearlo (ya bloqueado en `deploy/nginx.conf`).
- [ ] Instalar nginx con `deploy/nginx.conf` (trae los headers de seguridad — R2).
- [ ] `ufw default deny incoming` + permitir solo SSH. Con Cloudflare Tunnel **no se abren puertos** entrantes.
- [ ] `unattended-upgrades` activo (parches automáticos de Ubuntu).
- [ ] MySQL escuchando solo en `127.0.0.1` (no exponer 3306).
- [ ] (Al final) `MP_ACCESS_TOKEN` de producción + webhook `https://quadcorestore.com/api/pagos/webhook`.

---

## 4. Estado final del proyecto antes de subir

### Trabajo integrado y verificado esta sesión
- **Merge del trabajo de Fredo** (`feat/fredo-pulido`, tandas 1–6): botón hero, datos de contacto unificados, páginas de ayuda, "Mis Pedidos", 404, microcopy, accesibilidad, SEO + favicon, estados de carga/vacíos unificados. *(Tanda 7 — QA responsive — queda pendiente, decisión del propio Fredo.)*
- **Limpieza:** eliminado `contact-data.js` (huérfano).
- **Fix 404:** `render()` estaba duplicado y el activo tenía el HTML como stub `'...'`. Completado y eliminado el duplicado.
- **Migración completa** a `UI.loader` / `UI.mostrarVacio` (spinners y empty-states que habían quedado a mano).
- **Bugs de Fredo arreglados:**
  - Botón "He leído y acepto" del modal de T&C ahora **marca el checkbox**.
  - Datos de despacho ahora **se guardan en el perfil** al comprar (precarga en la próxima compra).
- **Seguridad:** R1 y R2 cerrados (ver sección 2).

### Calidad
- Todo el JS pasa `node --check`; todo el PHP pasa `php -l`.
- Sin código muerto, sin `import/export` sueltos, sin placeholders `'...'`, sin `console.log`/`TODO`.

### Pendientes (no bloquean el deploy)
- **Tanda 7:** QA responsive en móvil (375px).
- **Features de backlog** (cada una su PR): búsqueda de pedido por ID, mensajes de contacto en admin (hoy el form es decorativo), compra como invitado, múltiples direcciones (requiere cambio de BD).
- **Email real** (hoy simulado).

---

## 5. Conclusión

El **código de la aplicación está sólido** desde el punto de vista de seguridad: sin SQLi, sin IDOR, JWT y passwords correctos, XSS controlado. Los únicos dos riesgos eran de configuración y **ya están eliminados en el repo**. Cumpliendo el checklist de la sección 3, exponer el sitio mediante **Cloudflare Tunnel** (sin abrir puertos, con la IP de casa oculta y filtrado de Cloudflare por delante) es una opción segura para el alcance del proyecto.
