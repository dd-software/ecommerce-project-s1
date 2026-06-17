# Especificación de Control de Versiones Git

**Proyecto:** UCT Ecommerce  

**Equipo:** Los Takas — Arquitectura & DevOps  

**Versión:** 1.0  

**Estado:** Borrador — Sprint 1  

**Última actualización:** 2025  

---

## 1. Propósito y Objetivos

Este documento especifica la configuración de Git, estrategia de ramas, convenciones de commits y procedimientos de flujo de trabajo para el proyecto UCT Ecommerce. Define las reglas que deben seguir todas las personas del equipo y los sistemas automatizados para mantener un repositorio consistente, auditable y compatible con CI/CD.

---

## 2. Repositorio

| Propiedad | Valor |
| --- | --- |
| Plataforma | GitHub |
| Organización | `dd-software` |
| Repositorio | `ecommerce-project-s1` |
| Rama de equipo | `team-2-los-takas` |
| Versión de Git | 2.x (mínimo 2.30 para soporte de `--rebase-merges`) |

---

## 3. Estrategia de Ramas — Melé Flow

El proyecto utiliza un modelo de ramas estructurado, adaptado para desarrollo académico en equipo con integración CI/CD.

### 3.1 Definición de Ramas

| Rama | Patrón | Rama base | Propósito | Push directo |
| --- | --- | --- | --- | --- |
| `main` | exacta | — | Código listo para producción | Prohibido |
| `develop` | exacta | `main` | Rama de integración; dispara deploy a staging | Prohibido |
| `feature/*` | glob | `develop` | Desarrollo de funcionalidades individuales | Permitido |
| `release/*` | glob | `develop` | Preparación de release y versionado | Permitido |
| `hotfix/*` | glob | `main` | Fixes urgentes de producción | Permitido |

### 3.2 Ciclo de Vida de Ramas

#### Rama Feature

```
develop
  │
  ├──► feature/US-{id}-{short-description}
  │         │
  │         │  (trabajo de desarrollo)
  │         │
  │         ▼
  │    Pull Request → develop
  │         │
  │    Code review + CI OK
  │         │
  │    Squash merge
  ◄─────────┘
```

#### Rama Release

```
develop
  │
  ├──► release/v{major}.{minor}.{patch}
  │         │
  │         │  (bump de versión, changelog, solo bugfixes)
  │         │
  ├─────────┼──► merge a main (tag v{version})
  │         │
  ◄─────────┘    merge de vuelta a develop
```

#### Rama Hotfix

```
main
  │
  ├──► hotfix/{issue-id}-{short-description}
  │         │
  ├─────────┼──► merge a main (tag v{version}-patch)
  │         │
develop ◄───┘    merge de vuelta a develop
```

### 3.3 Reglas de Nombres de Ramas

```
feature/US-42-product-search-filter
feature/US-17-jwt-refresh-token
release/v1.0.0
release/v1.1.0
hotfix/BUG-8-null-cart-crash
hotfix/BUG-12-payment-double-charge
```

- Usar guiones (`-`) como separadores de palabras (no underscores ni espacios).
- Las branches feature incluyen el ID de la user story o tarea.
- Las branches hotfix incluyen el ID del issue/bug.
- Todo en minúsculas.

---

## 4. Convenciones de Commits — Conventional Commits

Todos los commits deben seguir la especificación **Conventional Commits 1.0.0**.

### 4.1 Formato

```
<type>(<scope>): <description>
[optional body]
[optional footer]
```

### 4.2 Types

| Type | Cuándo usar |
| --- | --- |
| `feat` | Nueva funcionalidad visible al usuario o al sistema |
| `fix` | Arreglo de bug |
| `docs` | Cambios solo de documentación |
| `style` | Cambios de formato sin afectar lógica (espacios, punto y coma) |
| `refactor` | Cambio de código que no es fix ni feature |
| `test` | Agregar o modificar tests |
| `chore` | Mantenimiento (dependencias, build config, CI) |
| `perf` | Mejoras de performance |
| `ci` | Cambios en configuración de CI/CD |
| `revert` | Revertir un commit anterior |

### 4.3 Scopes

El scope mapea a un módulo o capa de la aplicación:

| Scope | Corresponde a |
| --- | --- |
| `auth` | Autenticación y JWT |
| `products` | Catálogo de productos |
| `orders` | Gestión de pedidos |
| `payments` | Procesamiento de pagos |
| `cart` | Carrito de compras |
| `users` | Perfil de usuario |
| `admin` | Backoffice admin |
| `db` | Migraciones o esquema de base de datos |
| `api` | Routing o middleware genérico del API |
| `frontend` | Cambios HTML/CSS/JS |
| `ci` | Workflows de GitHub Actions |
| `infra` | Configuración de servidor (Apache, Docker) |
| `config` | Variables de entorno, configuración app |

### 4.4 Ejemplos

```
feat(products): add full-text search endpoint with pagination
fix(auth): prevent JWT reuse after explicit logout
docs(arch): add hexagonal architecture diagram to README
test(orders): add integration test for insufficient stock scenario
chore(ci): pin PHP version to 8.2 in GitHub Actions workflow
refactor(cart): extract price calculation into CartPricingService
ci(deploy): add production deploy approval gate
db(users): add missing index on users.email column
```

### 4.5 Breaking Changes

Los commits que introducen cambios incompatibles deben incluir `BREAKING CHANGE:` en el footer:

```
feat(api): change monetary amounts to integer cents

BREAKING CHANGE: Los campos de precio antes retornaban floats (p. ej., 89.90) y ahora retornan enteros en centavos (p. ej., 8990).
El frontend debe dividir por 100 para mostrar.
```

---

## 5. Reglas de Pull Request

### 5.1 Checks Requeridos Antes de Merge

- [ ]  El pipeline CI pasa (build, lint, tests).
- [ ]  Al menos 1 aprobación de revisión de pares.
- [ ]  No existen comentarios de revisión sin resolver.
- [ ]  La rama está actualizada respecto a la rama objetivo.
- [ ]  La descripción del PR completa el template requerido.

### 5.2 Template de Pull Request

El repositorio debe incluir `.github/pull_request_template.md`:

```markdown
## Description
<!-- What does this PR do? Reference the user story or task. -->

## Type of change
- [ ] New feature
- [ ] Bug fix
- [ ] Refactor
- [ ] Documentation
- [ ] CI/CD change

## How to test
<!-- Steps to verify this change works correctly. -->

## Checklist
- [ ] Code follows PSR-12 (PHP) or ESLint rules (JS)
- [ ] Tests added or updated
- [ ] No hardcoded credentials or API keys
- [ ] No direct SQL string interpolation
- [ ] Self-reviewed before requesting review
```

### 5.3 Estrategia de Merge

| Rama objetivo | Estrategia de merge | Por qué |
| --- | --- | --- |
| `develop` | Squash merge | Historial lineal y limpio en la rama de integración |
| `main` | Merge commit | Preserva trazabilidad del historial de releases |

---

## 6. Tags y Versionado

El proyecto usa **Semantic Versioning (SemVer)** para tags de release:

```
v{MAJOR}.{MINOR}.{PATCH}
```

| Segmento | Se incrementa cuando |
| --- | --- |
| MAJOR | Cambio incompatible de API o esquema |
| MINOR | Nueva funcionalidad compatible hacia atrás |
| PATCH | Bugfix compatible hacia atrás |

Creación de tags:

```bash
git tag -a v1.0.0 -m "Release v1.0.0 — Entregable Sprint 1"
git push origin v1.0.0
```

Los tags se crean solo en `main`, después de hacer merge de una rama release.

---

## 7. Requisitos de `.gitignore`

Lo siguiente debe excluirse del control de versiones:

```
# Dependencias PHP
/vendor/

# Configuración de entorno
.env
.env.local
.env.*.local

# composer.lock se commitea; vendor no
# composer.lock NO se ignora (debe estar commiteado)

# Archivos IDE
.idea/
.vscode/
*.swp
*.swo

# Archivos de OS
.DS_Store
Thumbs.db

# Logs
/var/log/*.log

# Directorio de subidas (contenido generado por usuarios)
/public/uploads/

# Cobertura de tests
/coverage/

# Artefactos de build
/dist/
```

### Reglas Críticas

- `.env` nunca debe aparecer en el repositorio, ni siquiera en el historial. Si se commitea por error, rotar secretos inmediatamente.
- `composer.lock` **debe** commitearse para asegurar versiones idénticas de dependencias en todos los entornos.
- `vendor/` nunca debe commitearse.

---

## 8. Estructura del Repositorio

```
ecommerce-project-s1/
├── .github/
│   ├── workflows/
│   │   ├── ci.yml
│   │   └── deploy.yml
│   ├── pull_request_template.md
│   └── CODEOWNERS
├── public/                  # Apache document root
├── src/                     # Código fuente PHP
├── views/                   # Templates HTML
├── tests/                   # Tests PHPUnit
├── database/
│   └── migrations/          # Migraciones SQL
├── docs/                    # Documentación SDD
│   ├── MODULES/
│   └── GESTIÓN/
├── .env.example             # Variables env placeholder
├── .gitignore
├── composer.json
├── composer.lock
└── README.md
```

---

## 9. CODEOWNERS

El archivo `.github/CODEOWNERS` asigna revisores requeridos por directorio:

```
# El equipo Architecture & DevOps es owner de infra y CI
/.github/      @dd-software/team-2-los-takas
/docs/         @dd-software/team-2-los-takas
*.yml          @dd-software/team-2-los-takas
.htaccess      @dd-software/team-2-los-takas
```

---

## 10. Configuración Git para Contribuyentes

Cada integrante debe configurar su identidad local antes de commitear:

```bash
git config --global user.name "FirstName LastName"
git config --global user.email "your.email@uct.cl"
git config --global core.autocrlf false    # Mantener saltos de línea Unix
git config --global pull.rebase true       # Rebase en pull en vez de merge
git config --global init.defaultBranch main
```

---

## 11. Reglas de Negocio

1. Nadie puede pushear directamente a `main` o `develop`. Todos los cambios entran por Pull Requests.
2. Un PR a `develop` requiere al menos 1 aprobación y CI pasando.
3. Un PR a `main` requiere al menos 1 aprobación, CI pasando, y debe venir desde una rama `release/*` o `hotfix/*`.
4. Los commits que no sigan Conventional Commits serán rechazados por el hook `commit-msg`.
5. Todas las ramas feature deben crearse desde `develop`, no desde `main`.
6. Las ramas ya mergeadas deben eliminarse dentro de 24 horas.
7. Archivos `.env` nunca deben commitearse; ante violación, se deben rotar secretos inmediatamente.

---

## 12. Criterios de Aceptación

- [ ]  Existe una rama `develop` y es la rama por defecto para Pull Requests.
- [ ]  Hay reglas de protección en `main` y `develop`: sin push directo, PR requerido, 1 aprobación requerida, CI debe pasar.
- [ ]  Todos los commits siguen el formato Conventional Commits.
- [ ]  `.env` está en `.gitignore` y no aparece en el historial del repositorio.
- [ ]  `composer.lock` está commiteado y presente en el repositorio.
- [ ]  `vendor/` no está commiteado y está listado en `.gitignore`.
- [ ]  Existe template de PR en `.github/pull_request_template.md`.
- [ ]  Existe CODEOWNERS configurado para archivos de CI/infra.
- [ ]  Los tags en `main` siguen SemVer (`v{MAJOR}.{MINOR}.{PATCH}`).