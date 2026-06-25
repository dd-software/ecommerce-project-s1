# 🤖 Reporte de Uso de Inteligencia Artificial (AI_USAGE)

**Proyecto:** Ecommerce UCT — SDD Academic Edition v1.0
**Metodología:** Specification Driven Development (SDD)
**Motor LLM:** Gemini 3.1 Pro (High) - Sistema Agentic

## 1. Naturaleza de la Asistencia de IA
La IA actuó bajo el rol de **Arquitecto de Software Senior y Desarrollador Full-Stack**, asumiendo la posición de "Pair-Programmer". Se desarrolló el sistema en 6 fases iterativas basadas en restricciones de rúbrica explícitas, abordando tanto código funcional como documentación.

## 2. Justificación de Decisiones Arquitectónicas (Para la Defensa Académica)

### 2.1. Patrón Singleton para PDO (`Database.php`)
**Decisión:** Se empleó el Patrón de Diseño Singleton.
**Justificación:** Al tratarse de una API REST que invoca Middlewares (`AuthMiddleware`) y Modelos (`Order`, `Product`) en un mismo ciclo de ejecución, instanciar `new PDO()` de manera descontrolada agotaría el pool de conexiones de MySQL y enlentecería el servidor. El Singleton garantiza que exista **una única instancia de conexión compartida** en memoria por petición HTTP.

### 2.2. Aislamiento Transaccional ACID (`Order.php`)
**Decisión:** El proceso de Checkout y Pago opera bajo `beginTransaction()`, `commit()` y `rollBack()`.
**Justificación:** Prevenir "Condiciones de Carrera". Si el sistema debita stock pero el estado de la orden falla al actualizarse, se produce una inconsistencia grave. Las transacciones MySQL aseguran que o "todo se ejecuta, o no se ejecuta nada". Además, se forzó la regla `stock >= quantity` en el `UPDATE` SQL como mecanismo de *Optimistic Locking* para no vender productos agotados (`[RN-001]`).

### 2.3. Desacoplamiento (Cliente-Servidor) y Seguridad Stateless (JWT)
**Decisión:** Cero uso de PHP renderizando HTML o manejando Sesiones `$_SESSION`.
**Justificación:** Fomenta la escalabilidad. El Frontend opera de manera independiente y asíncrona como una SPA (Single Page Application) usando Vanilla JS y consumiendo la API a través de JSON. La seguridad recae en `JWTHandler.php`, que cifra y firma tokens con `HMAC SHA256` de manera nativa sin requerir de Firebase o librerías que aumenten el peso del repositorio.

### 2.4. Flexibilidad de Enrutamiento para Entornos Locales (XAMPP)
**Decisión:** El algoritmo central de `Router.php` intercepta URIs localizando el sub-string `/api/`.
**Justificación:** En producción se suele usar `dominio.com/api/`. En XAMPP, los repositorios viven en subcarpetas (`localhost/proyecto/public/api/`). Si el enrutador usase validación estricta desde la raíz, fallaría. Este ajuste algorítmico hace que el proyecto sea un "Plug-and-Play" perfecto para ser evaluado en el ordenador de un profesor sin configuraciones extras.

## 3. Consideraciones Éticas y Metodológicas
Este repositorio demuestra cómo el "Specification Driven Development" guiado por Prompts estructurados resulta en software resiliente, escalable y limpio. Todo fragmento incluye PHPDoc facilitando la lectura y auditoría posterior por parte del equipo humano.
