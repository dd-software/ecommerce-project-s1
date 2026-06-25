# Módulo: Fase 6 - Despliegue y Defensa (SDD)

## Análisis
La última etapa cierra el ciclo de desarrollo acondicionando el proyecto para su evaluación y puesta en marcha en servidores locales (como XAMPP/WAMP) o servidores Apache de producción. Adicionalmente, se fundamenta académicamente la construcción mediante el reporte de asistencia de IA.

## Casos de Uso
1. **Despliegue Local (XAMPP):** El profesor/evaluador clona la carpeta dentro de `htdocs` y navega a `http://localhost/e-commerce-nuevo/public/`. El servidor Apache lee el `.htaccess` y enruta transparente las peticiones `/api/*` hacia el archivo `index.php`, sirviendo el resto de archivos estáticos (JS, CSS, HTML) de manera normal.
2. **Defensa del Proyecto:** El alumno consulta `AI_USAGE.md` para preparar su exposición técnica, comprendiendo por qué se usó Singleton, por qué se usaron Transacciones ACID en `Order.php`, y cómo se maneja la seguridad Stateless (JWT).

## Tareas
- [x] Crear `public/.htaccess` con reglas `mod_rewrite` limpias.
- [x] Refactorizar `Router.php` y `api.js` para flexibilizar las URIs y soportar el acceso vía subdirectorios en entornos locales de XAMPP (sin necesidad de crear un VirtualHost).
- [x] Generar el documento oficial `AI_USAGE.md` con las justificaciones arquitectónicas.

## Criterios de Aceptación
- La API debe responder sin errores `404` del servidor web, siendo capturados e interceptados siempre por `index.php`.
- La URL en XAMPP (`http://localhost/carpeta/public/`) debe funcionar con las peticiones asíncronas de JavaScript dinámicamente resueltas.
