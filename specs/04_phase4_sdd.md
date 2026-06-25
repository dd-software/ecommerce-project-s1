# Módulo: Fase 4 - Checkout, Pagos y Administración (SDD)

## Análisis
En esta fase se abordan los procesos críticos y transaccionales: Checkout (D), Pasarela de Pago Mock (E) y Administración (G).
Se implementa la regla de negocio `[RN-001]` verificando el stock disponible de forma estricta durante la creación de la orden. Además, se cumple `[RN-003]` garantizando que el stock físico (`inventory.stock`) solo se debita una vez que la orden ha cambiado de estado al estado 'pagado'.
Se emplea una base de datos ACID (InnoDB MySQL vía PDO) usando transacciones explícitas (`beginTransaction`, `commit`, `rollback`) para evitar condiciones de carrera o inconsistencias (ejs. pago a medias).

## Casos de Uso
1. **Checkout:** Un usuario autenticado que tiene productos en su carrito procede al checkout. El sistema crea una orden (`pendiente_pago`), copia los ítems tomando un "snapshot" inmutable del precio actual (`unit_price`), y limpia el carrito del usuario.
2. **Pago:** El usuario simula el pago de su orden. El sistema valida si ya fue pagada y, si es válida, cambia su estado a `pagado` y debita el inventario real (RN-003). Si no hay stock suficiente (ej. otra persona compró lo último minutos antes), se hace rollback del pago.
3. **Administración de Pedidos:** Un usuario con rol `admin` puede listar todas las órdenes registradas en la base de datos y actualizar su estado logístico (`en_preparacion`, `enviado`, `entregado`) cumpliendo la regla de trazabilidad `[RN-005]`.

## Tareas
- [x] Crear modelo `src/Models/Order.php` manejando transacciones completas (Checkout, Pago, Modificación de estados).
- [x] Crear `CheckoutController.php` (Módulo D) expuesto en `/api/checkout`.
- [x] Crear `PaymentController.php` (Módulo E) expuesto en `/api/payment/simulate`.
- [x] Crear `AdminController.php` (Módulo G) con protección RBAC exclusiva de administradores.
- [x] Actualizar `public/index.php` integrando las nuevas rutas.

## Criterios de Aceptación
- La creación de la orden debe guardar los precios unitarios independientemente de si el precio del catálogo cambia después (`unit_price`).
- El método de deducción de stock (Pago) debe usar un bloqueo optimista o una condición robusta en la query para prevenir sobre-ventas (`stock >= quantity`).
- Los endpoints de administración deben estar severamente protegidos devolviendo `403 Forbidden` a un cliente normal.
