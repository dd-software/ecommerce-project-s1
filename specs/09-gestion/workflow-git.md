# Workflow de Git y Convenciones de Código

Este documento describe la metodología de control de versiones y el flujo de integración de código para todos los desarrolladores y agentes involucrados en el proyecto de e-commerce.

## 1. Estrategia de Ramificación (Git Flow Simplificado)
El repositorio contará con las siguientes ramas estables y de desarrollo:
- **`main`**: Contiene únicamente código estable y en estado de producción. Cada actualización debe pasar por pruebas exhaustivas.
- **`develop`**: Rama de integración donde se consolidan las funcionalidades desarrolladas en el sprint. Sirve como base para el entorno de pruebas/staging.
- **`feature/<nombre-feature>`**: Ramas temporales de desarrollo individuales para implementar características específicas (ej: `feature/catalogo-buscar`, `feature/carrito-persistencia`). Se crean desde `develop` y se reintegran mediante Pull Requests.
- **`hotfix/<nombre-hotfix>`**: Ramas rápidas para solucionar errores graves detectados en `main`. Se integran a `main` y a `develop` de inmediato.

## 2. Convención para Mensajes de Commit (Conventional Commits)
Los commits deben seguir un estándar descriptivo para automatizar el historial de cambios:
- **`feat:`**: Incorporación de una nueva funcionalidad (ej. `feat: agregar buscador de productos en catalogo`).
- **`fix:`**: Corrección de un error o bug (ej. `fix: corregir calculo de IVA al vaciar el carrito`).
- **`docs:`**: Cambios únicamente en la documentación o especificaciones (ej. `docs: actualizar api-contract de checkout`).
- **`style:`**: Modificaciones estéticas, formateo de código o estilos CSS sin alterar funcionalidad (ej. `style: ajustar márgenes de Bootstrap en ficha de producto`).
- **`refactor:`**: Reestructuración de código existente que no añade funcionalidad ni corrige errores (ej. `refactor: modularizar middleware de validacion JWT`).
- **`test:`**: Agregar o corregir suites de pruebas automatizadas (ej. `test: agregar tests unitarios para autenticacion`).
- **`chore:`**: Tareas administrativas, configuración de librerías o dependencias (ej. `chore: actualizar libreria jwt`).

## 3. Proceso de Pull Requests y Code Review
1. Crear una rama descriptiva `feature/...` desde `develop`.
2. Realizar commits claros y enfocados.
3. Subir la rama y crear un Pull Request (PR) apuntando a `develop`.
4. El PR debe incluir una descripción clara de qué cambia y cómo probarlo.
5. Al menos un miembro del equipo o el supervisor de desarrollo debe revisar y aprobar el código antes de realizar el merge.
