# Contratos de API - Catálogo

### `GET /api/productos`
- **Parámetros de Entrada**: q (string), categoria (int), precio_min (decimal), precio_max (decimal), page (int), limit (int)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "data": [
    {"id": 101, "nombre": "Producto Genérico A", "precio": 49.99, "stock": 10, "imagen_url": "/assets/img/prod-a.jpg"}
  ],
  "pagination": {"current_page": 1, "total_pages": 4, "total_items": 48}
}
```

### `GET /api/productos/{id}`
- **Parámetros de Entrada**: Ninguno
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 101,
    "nombre": "Producto Genérico A",
    "descripcion": "Descripción detallada del producto",
    "precio": 49.99,
    "stock": 10,
    "imagenes": ["/assets/img/prod-a.jpg", "/assets/img/prod-a-back.jpg"]
  }
}
```

