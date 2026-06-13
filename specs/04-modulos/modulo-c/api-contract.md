# Contratos de API - Autenticación

### `POST /api/auth/login`
- **Parámetros de Entrada**: email (string), password (string)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {"id": 1, "nombre": "Dev User", "rol": "Dev"}
}
```

### `POST /api/auth/register`
- **Parámetros de Entrada**: nombre (string), email (string), password (string)
- **Formato de Comunicación**: JSON (`application/json`)
- **Respuesta Esperada (200 OK)**:
```json
{
  "success": true,
  "message": "Usuario registrado exitosamente"
}
```

