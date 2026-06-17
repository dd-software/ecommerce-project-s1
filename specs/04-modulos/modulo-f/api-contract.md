# Contratos de API - Inventario

### `POST /api/inventario/movimientos`
- **Parámetros de Entrada**: producto_id (int), cantidad (int), tipo_movimiento (string: INGRESO/EGRESO), motivo (string)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "message": "Movimiento de inventario registrado",
  "nuevo_stock": 25
}
```

### `GET /api/inventario/alertas`
- **Parámetros de Entrada**: umbral (int, opcional)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "data": [
    {"id": 105, "nombre": "Producto Sin Stock", "stock": 2, "umbral": 5}
  ]
}
```

