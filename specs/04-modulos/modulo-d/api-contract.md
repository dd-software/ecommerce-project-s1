# Contratos de API - Checkout

### `POST /api/checkout`
- **Parámetros de Entrada**: direccion_despacho (string), telefono (string), datos_facturacion (object)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "pedido_id": 8002,
  "monto_total": 118.98,
  "redirect_url": "/pago.html?pedido_id=8002"
}
```

