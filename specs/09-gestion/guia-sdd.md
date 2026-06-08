# Guía Metodológica para Desarrollo SDD (Software Design Document)

Esta guía define las directrices y estándares documentales para el Sprint 1 y sprints posteriores de este proyecto de e-commerce. Los documentos generados actúan como la única fuente de verdad técnica para programadores y agentes de inteligencia artificial.

## 1. Enfoque del Diseño
Cada componente, módulo o servicio debe especificarse completamente antes de codificar. Se debe documentar el comportamiento esperado de extremo a extremo (Frontend, Backend, Persistencia y de Integración).

## 2. Pautas Documentales por Archivo
Para mantener la coherencia a lo largo de las subcarpetas del proyecto, cada módulo debe tener exactamente los siguientes archivos con sus roles correspondientes:
- **`spec.md`**: Define el core del módulo (el objetivo, el alcance, los actores, las entidades involucradas y las reglas de negocio críticas).
- **`casos-uso.md`**: Define la secuencia de interacción de los actores en el sistema, contemplando flujos principales y excepciones técnicas.
- **`api-contract.md`**: Detalla el comportamiento REST de las rutas de backend en formato JSON para facilitar la integración frontend-backend sin sorpresas.
- **`user-stories.md`**: Define las historias de usuario en lenguaje ágil y sus condiciones mínimas de satisfacción.
- **`criterios-aceptacion.md`**: Detalla los estándares de calidad que la funcionalidad debe pasar antes del despliegue.
- **`checklist.md`**: Sirve como hoja de ruta paso a paso del desarrollador a nivel de infraestructura, código y despliegue.
- **`tareas.md`**: Desglosa y estima en horas el esfuerzo previsto de cada paso para planificar la carga de trabajo.
- **`testing.md`**: Especifica los nombres y objetivos de los tests automáticos PHPUnit y manuales.
- **`riesgos.md`**: Advierte sobre cuellos de botella de rendimiento o de seguridad, proponiendo soluciones concretas de mitigación.

## 3. Uso del Stack Tecnológico en la Especificación
- **Frontend**: Utilizar Bootstrap 5.3 para garantizar interfaces responsivas. Escribir lógica interactiva mediante Javascript estándar (Vanilla JS), evitando complejidades de frameworks a menos que sea explícito.
- **Backend**: Implementar servicios HTTP REST con PHP 8, utilizando estructuración en clases, enrutamiento limpio y manejo de dependencias.
- **Persistencia**: Diseñar relaciones claras y esquemas normalizados en MySQL. Optimizar consultas para evitar sobrecarga en la base de datos.
- **Seguridad**: Proteger endpoints de administración y transacciones delicadas a través de JWT firmados de forma segura con algoritmos como HS256.
