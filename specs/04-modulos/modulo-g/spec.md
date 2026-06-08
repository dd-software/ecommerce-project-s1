# Módulo G - Administración

## Objetivo y Propósito
Proveer una consola centralizada de administración (Dashboard) para gestionar productos, pedidos y roles del sistema.

## Responsabilidades Funcionales
- Ofrecer un panel visual estructurado con Bootstrap 5.3 para la gestión administrativa.
- Permitir el CRUD completo de productos y categorías de la plataforma.
- Gestionar los estados de los pedidos de los clientes.
- Visualizar reportes interactivos de ventas, facturación y productos más populares.

## Actores Involucrados
- Administrador
- Supervisor
- Vendedor

## Entidades y Relaciones Relevantes
- LogActividad (id, usuario_id, accion, fecha)

## Reglas de Negocio
- RN-G01: RN-002 Solo usuarios con rol Administrador, Supervisor o Vendedor con token JWT válido y no expirado pueden acceder al dashboard backend/frontend.
- RN-G02: Los vendedores solo pueden gestionar productos e inventarios. Los supervisores pueden gestionar productos, reportes y estados de pedidos. El Administrador tiene control completo sobre el sistema.

## Dependencias y Restricciones
- Depende del stack de Frontend (Bootstrap 5.3, JS Vanilla), Backend (PHP 8 REST), Persistencia (MySQL) e Integración (JWT, JSON).
- Integración segura mediante paso de tokens en cabeceras HTTP de autorización.
