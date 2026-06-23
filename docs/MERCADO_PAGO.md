# Integración Mercado Pago

## Paso 1: Obtener Credenciales

1. **Registrate en Mercado Pago**
   - Ve a [https://www.mercadopago.com.cl](https://www.mercadopago.com.cl) (Chile)
   - Crea una cuenta o inicia sesión
   - Ve a **Configuración → Credenciales**

2. **Genera tus credenciales**
   - Copia tu **Access Token** (comienza con `APP_USR-`)
   - Copia tu **Public Key** (para frontend, comienza con `APP_USR-`)
   - Guarda tu **Merchant ID**

## Paso 2: Instalar SDK

```bash
composer require mercadopago/sdk-php
```

## Paso 3: Configurar Variables de Entorno

Copia `.env.example` a `.env` y completa:

```env
MP_ACCESS_TOKEN=APP_USR-tu-token-aqui
MP_PUBLIC_KEY=APP_USR-tu-public-key-aqui
APP_URL=http://localhost/ecommerce-project-s1/public
```

## Paso 4: Estructura del Backend

```
src/Pagos/
├── PagosController.php        # Endpoints de pago
├── PagosService.php           # Lógica de negocio
├── PagosRepository.php        # Acceso a BD
└── MercadoPagoService.php     # Integración con MP
```

## Endpoints de API

### Crear Preferencia de Pago
```http
POST /api/pagos/mercado-pago/preferencia
Authorization: Bearer {token}
Content-Type: application/json

{
  "pedido_id": 123
}

Response:
{
  "success": true,
  "preference_id": "123456789",
  "init_point": "https://www.mercadopago.com/checkout/v1/redirect?...",
  "public_key": "APP_USR-xxxx"
}
```

### Confirmar Pago
```http
POST /api/pagos/mercado-pago/confirmar
Authorization: Bearer {token}
Content-Type: application/json

{
  "pedido_id": 123,
  "payment_id": "1234567890"
}

Response:
{
  "success": true,
  "estado": "aprobado",
  "mensaje": "Pago confirmado exitosamente.",
  "payment_id": "1234567890"
}
```

### Consultar Estado de Pago
```http
GET /api/pagos/estado/{pedidoId}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "pedido_id": 123,
    "metodo_pago": "mercado_pago",
    "monto": 50000,
    "estado": "aprobado",
    "fecha_pago": "2026-06-23 10:30:45"
  }
}
```

## Flujo de Pago (Frontend)

1. Usuario hace click en "Proceder al Pago"
2. Frontend llama a `POST /api/pagos/mercado-pago/preferencia`
3. Backend retorna `init_point` (URL de Mercado Pago)
4. Frontend redirige a Mercado Pago
5. Usuario completa pago en Mercado Pago
6. Mercado Pago redirige de vuelta con `status=approved` y `payment_id`
7. Frontend llama a `POST /api/pagos/mercado-pago/confirmar`
8. Backend actualiza estado del pedido y descuenta stock

## Webhook (Notificaciones de Mercado Pago)

Mercado Pago notifica a: `POST /api/pagos/webhook`

Payload de ejemplo:
```json
{
  "type": "payment",
  "data": {
    "id": "1234567890"
  }
}
```

Backend consultará a Mercado Pago el estado del pago y actualizará automáticamente.

## Configuración en Mercado Pago

1. Ve a **Configuración → URLs de notificación**
2. Agrega webhook en:
   ```
   https://tudominio.com/api/pagos/webhook
   ```
3. Selecciona eventos: `payment.created` y `payment.updated`

## Base de Datos

La tabla `pagos` ya incluye campos para almacenar:
- `referencia_externa`: ID del pago en Mercado Pago
- `metodo_pago`: 'mercado_pago'
- `respuesta_pasarela`: JSON con detalles de la respuesta

## Reglas de Negocio

- **RN-E01**: Stock se descuenta SOLO tras confirmar el pago
- **RN-E02**: Pedido cambia a 'pagado' solo con confirmación exitosa
- **RN-003**: Registra movimiento de inventario automáticamente

## Testing

### Tarjetas de Prueba Mercado Pago

```
Visa (Aprobada):     4111 1111 1111 1111
Mastercard (Rechazada): 5555 5555 5555 4444
American Express:    3782 822463 10005
```

Fecha: Futura (ej: 12/2025)
CVV: Cualquier número de 3 o 4 dígitos

## Troubleshooting

### Error: "MP_ACCESS_TOKEN no configurado"
- Verifica que el archivo `.env` existe
- Revisa que `MP_ACCESS_TOKEN` está correctamente seteado
- Reinicia el servidor

### Error: "Mercado Pago SDK not found"
```bash
composer require mercadopago/sdk-php
composer dump-autoload
```

### El pago aparece pendiente
- Verifica que el webhook está correctamente configurado
- Mercado Pago tardará algunos segundos en notificar
- Consult estado manual con GET `/api/pagos/estado/{pedidoId}`

## Documentación Oficial

- [API de Mercado Pago](https://www.mercadopago.com.ar/developers/es/reference)
- [Preferencias de Pago](https://www.mercadopago.com.ar/developers/es/reference/preferences/_checkout_preferences/post)
- [Pagos](https://www.mercadopago.com.ar/developers/es/reference/payments)
