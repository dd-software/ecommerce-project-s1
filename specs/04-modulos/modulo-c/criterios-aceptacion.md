# Criterios de Aceptación - Autenticación

Este documento define las condiciones que el componente debe cumplir para considerarse finalizado.

## Criterios de Calidad y Rendimiento
- **CA-C-001**: Toda comunicación que requiera autenticación debe viajar sobre JSON en el cuerpo o en cabeceras HTTP.
- **CA-C-002**: La contraseña nunca debe ser retornada en ninguna llamada de API (ej. GET /api/auth/me).
