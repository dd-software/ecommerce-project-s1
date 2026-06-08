# Contratos de API - Pasarela de Pago

### `POST /api/pagos/procesar`
- **Parámetros de Entrada**: pedido_id (int), numero_tarjeta (string), cvv (string), titular (string)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "transaccion_id": "TX-9981881",
  "estado": "APPROVED",
  "message": "Pago aprobado con éxito"
}
```

### `POST /api/pagos/webhook`
- **Parámetros de Entrada**: event_type (string), payload (object), signature (string)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "message": "Webhook procesado y stock descontado"
}
```

