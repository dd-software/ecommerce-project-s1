# Gestión de Riesgos - Pasarela de Pago

## Identificación y Mitigación de Riesgos
### Riesgo: La pasarela aprueba el cobro pero el servidor web se cae antes de registrar el estado en BD.\n- **Impacto**: Alto\n- **Estrategia de Mitigación**: Implementar un conciliador programado en PHP (Cron) que verifique los pedidos 'Pendientes' directamente contra el API del procesador.\n
