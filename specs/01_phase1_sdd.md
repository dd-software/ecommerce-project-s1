# Documentación SDD: Fase 1 - Estructura Base y Base de Datos

## Análisis
La fase inicial establece los cimientos del proyecto "Ecommerce UCT — SDD Academic Edition v1.0". Esto incluye la estructura de directorios (`public/`, `src/`, `config/`, `database/`, `specs/`), el modelado relacional de la base de datos (con soporte para borrados lógicos y control granular de stock a nivel de variantes), y la configuración segura de conexión mediante PDO en PHP 8.

## Casos de Uso
1. **Conexión a Base de Datos:** El sistema debe ser capaz de conectarse a la instancia MySQL utilizando credenciales provenientes del entorno mediante un patrón Singleton y usando `PDO`.
2. **Despliegue de Esquema:** El administrador/desarrollador debe poder inicializar la base de datos y poblarla con datos iniciales utilizando un script SQL limpio y estandarizado.

## Tareas
- [x] Crear estructura de carpetas inicial implícitamente al generar los archivos base.
- [x] Diseñar e implementar `database/schema.sql` cubriendo entidades y reglas de negocio obligatorias (Soft delete, inventario por variante, snapshot de precios inmutables en los pedidos).
- [x] Diseñar e implementar `database/seed.sql` con roles, usuarios y datos de prueba.
- [x] Implementar `config/database.php` usando PDO y tipos estrictos de PHP 8.
- [x] Configurar archivo `.env.example` y README técnico base.

## Criterios de Aceptación
- La base de datos debe aplicar el paradigma "Soft Delete" en las entidades principales mediante la columna `deleted_at`.
- La entidad `order_items` debe registrar el precio de la variante en el momento exacto de la compra (`unit_price`).
- La clase `Database` debe ser estricta (`declare(strict_types=1)`), manejar PDO de forma segura y no usar emulación de sentencias preparadas.
