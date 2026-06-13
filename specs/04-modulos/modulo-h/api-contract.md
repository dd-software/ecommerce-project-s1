# Contratos de API - Integración

### `POST /api/integracion/notificar`
- **Parámetros de Entrada**: pedido_id (int), tipo_evento (string: REGISTRO/PAGO_APROBADO/PEDIDO_ENVIADO)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "message": "Notificación encolada con éxito"
}
```

### `GET /api/integracion/exportar/ventas`
- **Parámetros de Entrada**: fecha_inicio (string), fecha_fin (string)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "schema_version": "1.0",
  "ventas": [
    {"pedido_id": 8002, "fecha": "2026-06-08", "total": 118.98, "items_count": 2}
  ]
}
```

