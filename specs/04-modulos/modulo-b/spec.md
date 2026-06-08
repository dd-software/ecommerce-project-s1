# Módulo B - Carrito

## Objetivo y Propósito
Gestionar la selección temporal de productos que el usuario desea comprar antes del proceso de checkout.

## Responsabilidades Funcionales
- Agregar productos al carrito indicando la cantidad deseada.
- Actualizar la cantidad de los ítems existentes en el carrito.
- Eliminar ítems individuales o vaciar por completo el carrito de compras.
- Calcular el subtotal, impuestos (IVA 19%) y total de los ítems agregados.

## Actores Involucrados
- Invitado
- Cliente

## Entidades y Relaciones Relevantes
- Carrito (id, usuario_id, creado_en, actualizado_en)
- CarritoItem (id, carrito_id, producto_id, cantidad, precio_unitario)

## Reglas de Negocio
- RN-B01: No se puede agregar una cantidad de producto superior al stock disponible.
- RN-B02: Si el usuario es Invitado, el carrito persiste localmente (LocalStorage/SessionStorage). Si inicia sesión, el carrito se asocia a su cuenta y se sincroniza con la base de datos MySQL.

## Dependencias y Restricciones
- Depende del stack de Frontend (Bootstrap 5.3, JS Vanilla), Backend (PHP 8 REST), Persistencia (MySQL) e Integración (JWT, JSON).
- Integración segura mediante paso de tokens en cabeceras HTTP de autorización.
