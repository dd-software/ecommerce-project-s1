# SDD API: Contratos de Integración Global

Este documento centraliza las especificaciones técnicas y operacionales de todos los microservicios y módulos de backend que componen la arquitectura del e-commerce.

---

## MÓDULO 1: Administración (`admin.yaml`)

### 1-11. Especificación SDD
1. **Objetivo:** Proveer endpoints para la supervisión y gestión global del negocio.
2. **Responsabilidad:** Retornar métricas (KPIs) y gestionar permisos/bloqueos de usuarios.
3. **Actores:** Administradores del sistema.
4. **Entradas/Salidas:** In (Petición con Bearer Token Admin) -> Out (JSON con reportes o confirmación HTTP 200).
5. **Reglas de negocio:** Acceso denegado (HTTP 403) a cualquier usuario sin rol explícito de `ADMINISTRADOR`.
6. **Flujos operacionales:** Intercepción Middleware -> Validación JWT -> Validación Rol -> Controlador -> Respuesta.
7. **Casos de uso:** Bloqueo de cuenta maliciosa, revisión de ventas mensuales.
8. **Entidades:** `Usuario`, `Metrica`, `OrdenDeCompra`.
9. **Restricciones:** Requiere conexión segura con el módulo `auth`.
10. **Criterios de aceptación:** Acciones de mutación deben dejar registro en logs de auditoría.
11. **Integración:** Actúa como orquestador de lectura de los módulos `pagos`, `catalogo` e `inventario`.

---

## MÓDULO 2: Autenticación (`auth.yaml`)

### 1-11. Especificación SDD
1. **Objetivo:** Gestionar la identidad, inicio de sesión y emisión de credenciales seguras.
2. **Responsabilidad:** Autenticar usuarios, emitir JWT y gestionar registros.
3. **Actores:** Usuarios Anónimos y Registrados.
4. **Entradas/Salidas:** In (JSON con credenciales) -> Out (Token JWT + Perfil Básico).
5. **Reglas de negocio:** Cifrado unidireccional obligatorio para contraseñas (Bcrypt). JWT caduca a las 2 horas.
6. **Flujos operacionales:** POST Login -> Verificación Hash -> Generación Token -> Retorno de sesión.
7. **Casos de uso:** Cliente crea una cuenta nueva; administrador inicia sesión para acceder al panel.
8. **Entidades:** `Usuario`.
9. **Restricciones:** Bloqueo temporal de IP tras 5 intentos fallidos consecutivos de login.
10. **Criterios de aceptación:** Payload del JWT libre de datos sensibles (solo expone ID, Email y Rol).
11. **Integración:** Base fundamental de seguridad; todos los demás módulos requieren su firma para operaciones privadas.

---

## MÓDULO 3: Carrito (`carrito.yaml`)

### 1-11. Especificación SDD
1. **Objetivo:** Manejar la persistencia temporal de la intención de compra.
2. **Responsabilidad:** CRUD de ítems pre-compra y cálculo preliminar de subtotales.
3. **Actores:** Clientes.
4. **Entradas/Salidas:** In (ProductoID, Cantidad) -> Out (Estado actual del Carrito).
5. **Reglas de negocio:** Imposibilidad de añadir cantidades superiores al stock disponible en inventario.
6. **Flujos operacionales:** POST a Carrito -> Validación cruzada con Inventario -> Actualización de base de datos -> Retorno.
7. **Casos de uso:** Cliente modifica la cantidad de un ítem de 1 a 3 unidades.
8. **Entidades:** `Carrito`, `ItemCarrito`.
9. **Restricciones:** Estricta sincronización con `inventario` para validar topes de cantidad.
10. **Criterios de aceptación:** El subtotal numérico debe coincidir matemáticamente con la suma de los precios por cantidad.
11. **Integración:** El objeto resultante es la entrada principal del módulo `checkout`.

---

## MÓDULO 4: Catálogo (`catalogo.yaml`)

### 1-11. Especificación SDD
1. **Objetivo:** Exponer la oferta comercial para descubrimiento y búsqueda.
2. **Responsabilidad:** Entregar listas paginadas, resolver búsquedas por texto y proveer detalles de productos.
3. **Actores:** Usuarios (lectura) y Administradores (mutación).
4. **Entradas/Salidas:** In (Query params de filtros) -> Out (Arreglo JSON paginado de productos).
5. **Reglas de negocio:** Exclusión estricta de productos inactivos en endpoints públicos. Precios incluyen IVA.
6. **Flujos operacionales:** GET request -> Búsqueda indexada -> Formateo paginado -> Retorno.
7. **Casos de uso:** Cliente filtra productos por rango de precio y categoría específica.
8. **Entidades:** `Producto`, `Categoria`, `ImagenProducto`.
9. **Restricciones:** Endpoints GET deben implementar caché para soportar alto tráfico.
10. **Criterios de aceptación:** Endpoints de consulta no requieren autenticación (Bearer Token).
11. **Integración:** Funciona como fuente de verdad nominal y de precios para `carrito` y `checkout`.

---

## MÓDULO 5: Checkout (`checkout.yaml`)

### 1-11. Especificación SDD
1. **Objetivo:** Orquestar la transformación de un carrito en una orden formal de compra.
2. **Responsabilidad:** Validar envíos, consolidar totales (despacho, impuestos) y generar la Orden.
3. **Actores:** Clientes.
4. **Entradas/Salidas:** In (Datos de envío, Método de pago, ID Carrito) -> Out (ID de Orden y URL de pago).
5. **Reglas de negocio:** El proceso se detiene si hay quiebre de stock en algún ítem al momento de confirmar.
6. **Flujos operacionales:** Validación -> Bloqueo de stock temporal -> Creación Orden (PENDIENTE) -> Generación URL.
7. **Casos de uso:** Cliente finaliza su compra y solicita enlace de pago hacia Webpay.
8. **Entidades:** `OrdenCompra`, `DetalleOrden`, `DireccionEnvio`.
9. **Restricciones:** Patrón transaccional; si el servicio de pagos no responde, se debe realizar *rollback* y liberar el stock retenido.
10. **Criterios de aceptación:** Tiempos de respuesta optimizados o manejo mediante colas asíncronas.
11. **Integración:** Nodo central que coordina `carrito`, `catalogo`, `inventario` y `pagos`.

---

## MÓDULO 6: Inventario (`inventario.yaml`)

### 1-11. Especificación SDD
1. **Objetivo:** Administrar la disponibilidad física de productos y prevenir sobredemanda.
2. **Responsabilidad:** Informar disponibilidad, gestionar reservas temporales y confirmar descuentos de bodega.
3. **Actores:** Microservicios internos.
4. **Entradas/Salidas:** In (Solicitud de reserva con cantidades) -> Out (Confirmación HTTP 200 o Rechazo por conflicto).
5. **Reglas de negocio:** Las reservas de stock caducan automáticamente a los 15 minutos sin confirmación de pago.
6. **Flujos operacionales:** Petición de reserva -> Bloqueo optimista -> Retorno de confirmación a `checkout`.
7. **Casos de uso:** Se congelan unidades durante el flujo de pago de un cliente para evitar ventas cruzadas.
8. **Entidades:** `RegistroInventario`, `ReservaTemporal`.
9. **Restricciones:** Requiere mecanismos técnicos (locks) para prevenir condiciones de carrera (Race Conditions).
10. **Criterios de aceptación:** Imposibilidad sistémica de mantener saldos de stock negativos.
11. **Integración:** Totalmente acoplado a las operaciones críticas de `checkout` y notificaciones de `pagos`.

---

## MÓDULO 7: Pagos (`pagos.yaml`)

### 1-11. Especificación SDD
1. **Objetivo:** Abstraer e integrar la comunicación con pasarelas financieras externas.
2. **Responsabilidad:** Generar transacciones bancarias y procesar webhooks de confirmación asíncrona.
3. **Actores:** Clientes, Pasarelas Bancarias (Sistemas Externos).
4. **Entradas/Salidas:** In (Webhook firmado desde el banco) -> Out (Confirmación de recepción HTTP 200).
5. **Reglas de negocio:** Estados inmutables y unidireccionales: `PENDIENTE` evoluciona a `APROBADO` o `RECHAZADO`.
6. **Flujos operacionales:** Recepción Webhook -> Validación de firma -> Actualización de Orden -> Notificación de inventario.
7. **Casos de uso:** El banco notifica mediante callback que una tarjeta de crédito fue aprobada con éxito.
8. **Entidades:** `TransaccionPago`.
9. **Restricciones:** Verificación estricta de la integridad del payload para evitar fraudes por suplantación de la pasarela.
10. **Criterios de aceptación:** Registro persistente (log inmutable) de toda transacción denegada o con error.
11. **Integración:** Tras la resolución del pago, actualiza el estado en `checkout` y consolida el descuento en `inventario`.
