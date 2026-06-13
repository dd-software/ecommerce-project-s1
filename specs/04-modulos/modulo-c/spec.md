# Módulo C - Autenticación

## Objetivo y Propósito
Asegurar el control de accesos, registro y validación de usuarios utilizando el protocolo JWT.

## Responsabilidades Funcionales
- Permitir el registro seguro de nuevos clientes.
- Autenticar usuarios mediante email y contraseña cifrada.
- Generar y validar tokens JWT para la comunicación cliente-servidor.
- Controlar los permisos de acceso de acuerdo con los roles (Dev, Admin, Supervisor, Vendedor, Cliente, Invitado).

## Actores Involucrados
- Invitado
- Cliente
- Vendedor
- Supervisor
- Administrador
- Dev

## Entidades y Relaciones Relevantes
- Usuario (id, nombre, email, password_hash, rol, estado, creado_en)
- Rol (id, nombre, descripcion)

## Reglas de Negocio
- RN-C01: Las contraseñas en MySQL deben guardarse cifradas obligatoriamente mediante algoritmos seguros como `PASSWORD_BCRYPT`.
- RN-C02: Los tokens JWT deben poseer fecha de expiración configurada (ej. 2 horas).
- RN-C03: RN-004 Usuarios deshabilitados en su estado no pueden iniciar sesión bajo ninguna circunstancia.

## Dependencias y Restricciones
- Depende del stack de Frontend (Bootstrap 5.3, JS Vanilla), Backend (PHP 8 REST), Persistencia (MySQL) e Integración (JWT, JSON).
- Integración segura mediante paso de tokens en cabeceras HTTP de autorización.
