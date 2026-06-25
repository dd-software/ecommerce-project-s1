# Reporte de Uso de Inteligencia Artificial - Plataforma E-commerce UCT

Este documento describe cómo se utilizó la Inteligencia Artificial (IA) como asistente de desarrollo y programación en pareja (*pair programming*) a lo largo del desarrollo de la plataforma de e-commerce.

---

## 1. Introducción y Modalidad de Trabajo
El proyecto se desarrolló bajo una metodología híbrida de programación colaborativa entre el desarrollador humano y un asistente de IA de codificación avanzada. El rol de la IA consistió en:
*   Proponer soluciones arquitectónicas.
*   Automatizar tareas repetitivas y despliegues.
*   Depurar errores de ejecución y de base de datos.
*   Escribir y refinar porciones de código en el frontend, backend y base de datos bajo la supervisión humana directa.

---

## 2. Áreas Principales de Apoyo de la IA

### 🎨 A. Diseño UI/UX y Estilizado (Frontend)
*   **Selección del Tema Visual**: La IA ayudó a diseñar la estética final de la tienda basada en un estilo *Cyber-punk Premium*, definiendo la paleta de colores oscuros (`#0a0b0e` / `#141722`) y acentos de color en rojo neón/carmesí.
*   **Refactorización CSS**: Estructuración del archivo `style.css` utilizando variables CSS personalizadas, mejorando los contrastes de tipografía para lectura clara y puliendo las transiciones interactivas en botones y tarjetas de productos.
*   **Compatibilidad Dinámica**: Solución del problema de enrutamiento estático mediante un script en JavaScript para cambiar dinámicamente la etiqueta `<base>` según el entorno de ejecución, y posteriormente simplificarlo a rutas relativas puras (`<base href="./">`).

### ⚙️ B. Desarrollo de Lógica y Programación (JS / PHP)
*   **Mapeo de Rutas y APIs**: Depuración del controlador frontal (`index.php`) y enrutador dinámico.
*   **Validaciones en el Frontend**: Implementación de controles interactivos obligatorios en el formulario de Checkout (Nombre, Apellido, Teléfono y Dirección de despacho) antes de permitir la compra.
*   **Buscador Global**: Escritura y sincronización del buscador en la barra de navegación para consultar la base de datos en tiempo real y filtrar el catálogo con seguridad de tipos, evitando problemas de concatenación de barras.
*   **Integración de Pasarelas**: Integración de botones interactivos para Webpay (Simulado) y PayPal en el proceso de compra.

### 🗄️ C. Bases de Datos y SQL
*   **Depuración de Esquemas**: Detección y eliminación de la duplicidad en la declaración de tablas (como la tabla `resenas` en `schema.sql`).
*   **Compatibilidad para Servidores de Producción**: Refactorización de scripts SQL (`schema.sql`, `seed.sql` y `setup.sql`) para quitar sentencias `DROP DATABASE`, `CREATE DATABASE` y `USE`, permitiendo a los desarrolladores importar el esquema directamente desde phpMyAdmin sin errores de privilegios de usuario.
*   **Pruebas de Conexión**: Creación de scripts para verificar la validez de contraseñas de red y servidores de bases de datos.

### 🚀 D. Automatización y Despliegue (DevOps)
*   **Script de Subida SFTP**: Creación de un script en Python (`upload.py`) para limpiar el servidor de la universidad y automatizar la subida de los archivos de la aplicación a través del puerto SFTP de forma controlada y segura.
*   **Inicializador Remoto de Base de Datos**: Programación de un script PHP auto-destructible (`import_db.php`) que permitía inicializar la base de datos del servidor por medio de HTTP, autolimpiándose al finalizar para mantener la seguridad.

---

## 3. Conclusión sobre el Impacto de la IA
La asistencia de Inteligencia Artificial en este proyecto permitió:
1.  **Reducción de Tiempos**: Mayor velocidad al generar plantillas de base de datos, configurar archivos de entorno `.env` y estructurar hojas de estilo repetitivas.
2.  **Seguridad y Robustez**: Disminución de errores de sintaxis comunes en consultas SQL y mayor solidez al implementar validaciones del lado del cliente.
3.  **Facilidad de Aprendizaje**: Explicación de los motivos técnicos detrás de cada error (como fallos 404 de rutas relativas o denegaciones de acceso de MySQL), potenciando el entendimiento de la arquitectura de software.
