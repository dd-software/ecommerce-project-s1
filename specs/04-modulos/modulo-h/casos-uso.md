# Casos de Uso - Integración

### CU-H-001: Enviar Notificación de Pedido Pagado
- **Actor**: Sistema, Cliente (receptor de correos), Dev (monitoreo)
- **Precondición**: El estado de un pedido cambia a 'Pagado' en el Módulo E.
- **Flujo Operacional Principal**:
  1. El sistema detecta la confirmación de pago.
  2. El sistema arma la plantilla de correo con Bootstrap y datos del pedido.
  3. El sistema llama al adaptador de notificaciones para despachar el correo.
  4. El sistema registra la fecha de envío en la base de datos MySQL.
- **Excepciones y Flujos Alternativos**:
  - Fallo de conexión SMTP/API: El sistema registra el error en un log local y encola la tarea para reintento automático.

### CU-H-002: Exportar Datos de Ventas en JSON
- **Actor**: Sistema, Cliente (receptor de correos), Dev (monitoreo)
- **Precondición**: El usuario posee rol Supervisor o Administrador.
- **Flujo Operacional Principal**:
  1. El actor presiona 'Exportar Histórico de Ventas' en el panel de reportes.
  2. El sistema consulta MySQL agrupando pedidos confirmados y detalles.
  3. El sistema genera una cadena en formato JSON estructurada bajo el esquema corporativo.
  4. El sistema inicia la descarga del archivo en el navegador del cliente.
- **Excepciones y Flujos Alternativos**:
  - No hay registros en el rango seleccionado: Se descarga un JSON vacío con metadatos base.

