# Módulo H - Integración

## Objetivo y Propósito
Administrar la comunicación externa e interna del e-commerce, incluyendo el envío de notificaciones y la exportación estructurada de reportes en formato JSON.

## Responsabilidades Funcionales
- Disparar notificaciones automatizadas por correo electrónico (simuladas mediante SMTP o logs estructurados).
- Generar archivos JSON estructurados con reportes del sistema listos para ser consumidos por otros servicios.
- Centralizar los logs de errores y eventos de integración de la plataforma.

## Actores Involucrados
- Sistema
- Cliente (receptor de correos)
- Dev (monitoreo)

## Entidades y Relaciones Relevantes
- ColaNotificacion (id, destinatario, tipo, asunto, cuerpo, estado, reintentos, creado_en)

## Reglas de Negocio
- RN-H01: Toda llamada a APIs de terceros o servicios de notificaciones no debe bloquear el hilo principal de navegación del usuario (deben capturarse errores silenciosamente o procesarse asíncronamente).
- RN-H02: El diseño de los servicios de envío de notificaciones y exportaciones debe utilizar el patrón de diseño Adapter, aislando las librerías concretas.

## Dependencias y Restricciones
- Depende del stack de Frontend (Bootstrap 5.3, JS Vanilla), Backend (PHP 8 REST), Persistencia (MySQL) e Integración (JWT, JSON).
- Integración segura mediante paso de tokens en cabeceras HTTP de autorización.
