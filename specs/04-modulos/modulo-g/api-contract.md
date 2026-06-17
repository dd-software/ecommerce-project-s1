# Contratos de API - Administración

### `POST /api/admin/productos`
- **Parámetros de Entrada**: nombre (string), descripcion (string), precio (decimal), stock (int), categoria_id (int), imagen (file)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "message": "Producto creado con éxito",
  "producto_id": 150
}
```

### `PUT /api/admin/pedidos/{id}/estado`
- **Parámetros de Entrada**: estado (string: PENDIENTE/PAGADO/DESPACHADO/ENTREGADO/CANCELADO)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "message": "Estado del pedido actualizado correctamente"
}
```

