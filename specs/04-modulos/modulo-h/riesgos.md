# Gestión de Riesgos - Integración

## Identificación y Mitigación de Riesgos
### Riesgo: Latencia alta al conectar con servidor SMTP externo que bloquea la respuesta REST al cliente.\n- **Impacto**: Alto\n- **Estrategia de Mitigación**: Utilizar una cola de correos en base de datos de procesamiento asíncrono para enviar en segundo plano.\n
