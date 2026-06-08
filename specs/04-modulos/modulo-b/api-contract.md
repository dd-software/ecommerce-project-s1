# Contratos de API - Carrito

### `GET /api/carrito`
- **Parámetros de Entrada**: Ninguno (Requiere Cabecera JWT si está logueado)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "data": {
    "items": [
      {"producto_id": 101, "nombre": "Producto Genérico A", "cantidad": 2, "precio": 49.99, "subtotal": 99.98}
    ],
    "subtotal": 99.98,
    "iva": 19.00,
    "total": 118.98
  }
}
```

### `POST /api/carrito/items`
- **Parámetros de Entrada**: producto_id (int), cantidad (int)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "message": "Producto añadido al carrito correctamente"
}
```

