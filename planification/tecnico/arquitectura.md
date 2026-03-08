# Arquitectura técnica

## Stack

| Capa | Tecnología |
|------|-----------|
| Lenguaje | PHP 8.4 |
| Framework | Symfony 8.0 |
| ORM | Doctrine ORM 3.x + Migrations |
| BD principal | PostgreSQL 16 (puerto 5432) |
| BD de tests | PostgreSQL 16 (puerto 5433) |
| Servidor web | Nginx (proxy inverso a PHP-FPM, puerto 8080) |
| Contenedores | Docker Compose |
| Tests | PHPUnit 13 |

---

## Estructura DDD

Bounded context principal: **Betting**.

```
src/
├── Domain/Betting/
│   ├── Entity/          — Team, TeamExternalId
│   ├── Repository/      — Interfaces (TeamRepositoryInterface, FootballDataProviderInterface…)
│   └── Service/         — Lógica de dominio pura (FormCalculator, GoalsCounterUpdater)
├── Application/Betting/
│   ├── Service/         — Casos de uso (TeamSyncService, TomorrowBetsService)
│   └── DTO/             — TeamBetDTO
└── Infrastructure/Betting/
    ├── Http/
    │   ├── Client/      — FootballDataClient (implementa FootballDataProviderInterface)
    │   └── Controller/  — BetsController
    ├── Persistence/Doctrine/ — DoctrineTeamRepository, DoctrineTeamExternalIdRepository
    └── Command/         — SeedTeamsCommand
```

### Reglas de capas

- `declare(strict_types=1)` en todos los ficheros
- Los repositorios e interfaces viven en `Domain/`, las implementaciones en `Infrastructure/`
- Los servicios de dominio (sin dependencias de infraestructura) van en `Domain/Betting/Service/`
- Los servicios de aplicación (orquestan dominio + infraestructura) van en `Application/Betting/Service/`
- Controllers en `Infrastructure/Betting/Http/Controller/`, registrados explícitamente en `services.yaml`
- Migraciones en `src/Infrastructure/Shared/Persistence/Doctrine/Migrations/`

### Convenciones de entidades

- Constructor privado + factory method estático `create(...): self`
- Getters sin prefijo `get`: `name()`, `league()`, `nextFixtureDate()`…

---

## Esquema de base de datos

### Tabla `team`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | ID interno autoincremental |
| `name` | varchar(100) | Nombre del equipo |
| `league` | varchar(10) | Código de liga (`PD`, `BL1`, `SA`) |
| `form_last_8` | varchar(8) | Racha últimos 8 en liga (`WDLWWDLW`) |
| `form_last_5_home` | varchar(5) | Racha últimos 5 en casa |
| `form_last_5_away` | varchar(5) | Racha últimos 5 fuera |
| `over_2_5_home` | int | Veces que marcó 3+ goles en casa |
| `matches_played_home` | int | Partidos jugados en casa |
| `over_1_5_away` | int | Veces que marcó 2+ goles fuera |
| `matches_played_away` | int | Partidos jugados fuera |
| `next_fixture_date` | datetime | Fecha y hora del próximo partido |
| `next_fixture_opponent_id` | int | ID externo (football-data.org) del rival |
| `next_fixture_opponent_name` | varchar(100) | Nombre del rival |
| `next_fixture_opponent_form_situational` | varchar(5) | Racha situacional del rival |
| `next_fixture_is_home` | bool | Si jugamos en casa o fuera |
| `last_synced_at` | datetime | Última sincronización con la API |

### Tabla `team_external_id`

Permite soportar múltiples proveedores de datos sin tocar el esquema de `team`.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | ID interno |
| `team_id` | int (FK) | FK a `team` |
| `provider` | varchar(50) | Nombre del proveedor (`football-data.org`) |
| `external_id` | varchar(50) | ID del equipo en ese proveedor |

### Equipos iniciales y sus IDs externos

| Equipo | Liga | ID football-data.org |
|--------|------|----------------------|
| Real Madrid | PD | 86 |
| FC Barcelona | PD | 81 |
| Bayern Munich | BL1 | 5 |
| Borussia Dortmund | BL1 | 4 |
| AS Roma | SA | 100 |
| Juventus | SA | 109 |

---

## Lógica de refresco de datos (TeamSyncService)

Un equipo se considera **ya sincronizado** (y no se llama a la API) si cumple TODAS estas condiciones:

1. Tiene `nextFixtureDate` en el futuro
2. Tiene `nextFixtureOpponentFormSituational` no nulo
3. Faltan más de 48 horas para el partido

Si alguna condición falla, se re-sincroniza: se llaman a la API, se recalculan rachas y contadores, y se persiste.

---

## Convenciones de testing

### Nomenclatura obligatoria

```
test_{acción}_{contexto}__should_{resultado_esperado}
```

Ejemplos:
- `test_syncing_team__when_next_fixture_is_future__should_not_call_api`
- `test_calculating_form__with_5_wins__should_return_WWWWW`
- `test_loading_bets_page__should_return_200`

### Tipos de tests

**Tests unitarios** (`tests/Unit/`)
- Prueban una sola clase en aislamiento
- Se pueden usar mocks libremente

**Tests de integración** (`tests/Integration/`)
- Solo se mockea la llamada a la API externa (football-data.org)
- La base de datos de tests es real (`postgres_test`, puerto 5433)
- Prueban el flujo completo: servicio → DB → respuesta

### Regla general

No se puede considerar implementado un cambio si los tests no pasan.

---

## API football-data.org

- Base URL: `https://api.football-data.org/v4`
- Autenticación: header `X-Auth-Token: {API_KEY}`
- Variable de entorno: `FOOTBALL_DATA_API_KEY`
- Endpoints usados:
  - `GET /teams/{id}/matches?status=SCHEDULED&competitions={liga}&limit=1` → próximo partido
  - `GET /teams/{id}/matches?status=FINISHED&competitions={liga}&limit=20` → partidos jugados
- Manejo de errores: ante rate limit (429) u otro error, se loguea y se devuelve array vacío sin romper la página
