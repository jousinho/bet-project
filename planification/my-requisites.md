# Paso 1 - Infrastructura

## Contenedores Docker

| Servicio      | Imagen                | Puerto host | Puerto contenedor |
|---------------|-----------------------|-------------|-------------------|
| php           | php:8.4-fpm-alpine    | -           | 9000              |
| php-cli       | php:8.4-fpm-alpine    | -           | -                 |
| nginx         | nginx:alpine          | 8080        | 80                |
| postgres      | postgres:16-alpine    | 5432        | 5432              |
| postgres_test | postgres:16-alpine    | 5433        | 5432              |

## PHP 8.4 (FPM Alpine)

**Extensiones instaladas:**
- pdo_pgsql
- pgsql
- intl
- bcmath
- opcache
- zip

**Configuración php.ini:**
- memory_limit = 256M
- upload_max_filesize = 64M
- post_max_size = 64M
- max_execution_time = 60
- date.timezone = Europe/Madrid
- opcache habilitado (memory_consumption = 128M, max_accelerated_files = 20000)

## Nginx (Alpine)

- Proxy inverso hacia PHP-FPM en puerto 9000
- Root: /var/www/html/public
- Puerto expuesto: 8080

## PostgreSQL 16 (Alpine)

- Base de datos principal: puerto 5432
- Base de datos de tests: puerto 5433
- Variables de entorno: POSTGRES_USER, POSTGRES_PASSWORD, POSTGRES_DB, POSTGRES_DB_TEST
- Healthcheck con pg_isready

## Framework y dependencias (Symfony 8.0)

**Producción:**
- symfony/framework-bundle 8.0.*
- symfony/console 8.0.*
- symfony/dotenv 8.0.*
- symfony/serializer 8.0.*
- symfony/http-client 8.0.*
- symfony/property-access 8.0.*
- symfony/uid 8.0.*
- symfony/yaml 8.0.*
- symfony/twig-bundle 8.0.*
- doctrine/doctrine-bundle ^3.2
- doctrine/doctrine-migrations-bundle ^4.0
- doctrine/orm ^3.6

**Desarrollo/Tests:**
- phpunit/phpunit ^13.0
- symfony/browser-kit 8.0.*

## Gestor de dependencias

- Composer (imagen: composer:latest)

# Paso 2 - Definición del proyecto

## Página única

- URL: `/tomorrow/bets`
- Fuente de datos: **football-data.org** (API key propia)

## Equipos mostrados

Lista fija con los equipos más importantes de las principales ligas europeas:
- Real Madrid, FC Barcelona (La Liga)
- Bayern Munich, Borussia Dortmund (Bundesliga)
- AS Roma, Juventus (Serie A)

Se muestran **todos los equipos siempre**, ordenados por fecha de próximo partido. Los equipos que tienen partido mañana aparecen resaltados.

## Diseño de la página

- Listado vertical de filas, una por equipo
- Cada fila muestra: nombre del equipo + fecha del próximo partido + rival
- Los equipos con partido mañana van resaltados visualmente
- Al hacer clic en una fila, se despliega (acordeón) mostrando toda la información detallada
- No son fichas grandes: es un listado de filas colapsables

## Datos en el detalle desplegable

### Para todos los equipos
- Racha de los últimos 8 partidos en liga (V/E/D), sin distinción de casa/fuera.

### Si el equipo juega en casa mañana
- Racha de los últimos 5 partidos **en casa** del equipo
- Racha de los últimos 5 partidos **fuera** del rival
- Veces que el equipo marcó **+2.5 goles en casa** (ej: 6 de 10 partidos)
- Veces que el rival marcó **+1.5 goles fuera** (ej: 3 de 8 partidos)

### Si el equipo juega fuera mañana
- Racha de los últimos 5 partidos **fuera** del equipo
- Racha de los últimos 5 partidos **en casa** del rival
- Veces que el equipo marcó **+1.5 goles fuera** (ej: 4 de 9 partidos)
- Veces que el rival marcó **+2.5 goles en casa** (ej: 7 de 10 partidos)

### Regla general de rachas
- Si no se han disputado 5 partidos en esa condición (casa/fuera), se muestran los que haya.

# Paso 3 - Implementación

## Lógica por situación

| Situación | Racha equipo | Racha rival | Goles equipo | Goles rival |
|-----------|-------------|-------------|--------------|-------------|
| Juega en casa | Últimos 5 en casa | Últimos 5 fuera | +2.5 en casa (X/N) | +1.5 fuera (X/N) |
| Juega fuera | Últimos 5 fuera | Últimos 5 en casa | +1.5 fuera (X/N) | +2.5 en casa (X/N) |

Además, para todos los equipos: racha de los últimos 8 partidos en liga (sin filtro casa/fuera).

## API

- Proveedor: **football-data.org**
- Autenticación: header `X-Auth-Token: {API_KEY}`
- Variable de entorno: `FOOTBALL_DATA_API_KEY`

## Equipos tracked

Lista fija de equipos con sus IDs de football-data.org (verificar antes de arrancar):

- Real Madrid (liga española)
- FC Barcelona (liga española)
- Bayern Munich (bundesliga)
- Borussia Dortmund (bundesliga)
- AS Roma (serie A)
- Juventus (serie A)

## Arquitectura DDD

El proyecto sigue la misma estructura DDD que `second-api`. Bounded context principal: **Betting**.

```
src/
├── Domain/Betting/
│   ├── Entity/          — Team, TeamExternalId
│   ├── Repository/      — Interfaces (TeamRepositoryInterface, FootballDataProviderInterface...)
│   ├── Service/         — Lógica de dominio pura (FormCalculator, GoalsCounterUpdater)
│   └── Exception/
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

### Reglas
- `declare(strict_types=1)` en todos los ficheros
- Los repositorios e interfaces viven en `Domain/`, las implementaciones en `Infrastructure/`
- Los servicios de dominio (sin dependencias de infraestructura) van en `Domain/Betting/Service/`
- Los servicios de aplicación (orquestan dominio + infraestructura) van en `Application/Betting/Service/`
- Controllers en `Infrastructure/Betting/Http/Controller/`, registrados explícitamente en `services.yaml`
- Migraciones en `src/Infrastructure/Shared/Persistence/Doctrine/Migrations/`

### services.yaml — patrón de wiring

```yaml
# Binding interfaz → implementación
App\Domain\Betting\Repository\TeamRepositoryInterface:
    class: App\Infrastructure\Betting\Persistence\Doctrine\DoctrineTeamRepository

# Controllers explícitos
App\Infrastructure\Betting\Http\Controller\BetsController:
    tags: ['controller.service_arguments']
    public: true
```

### services_test.yaml — interfaces públicas para tests de integración

Cada interfaz que se resuelva via `static::getContainer()->get()` en tests debe declararse pública en `config/services_test.yaml`:

```yaml
services:
    App\Domain\Betting\Repository\TeamRepositoryInterface:
        class: App\Infrastructure\Betting\Persistence\Doctrine\DoctrineTeamRepository
        autowire: true
        public: true
```

## Componentes a crear

### FootballDataProviderInterface _(Domain)_
`src/Domain/Betting/Repository/FootballDataProviderInterface.php`
- `getNextFixture(string $externalTeamId, string $competition): array`
- `getFinishedMatches(string $externalTeamId, string $competition, int $limit): array`

### FootballDataClient _(Infrastructure)_
`src/Infrastructure/Betting/Http/Client/FootballDataClient.php`
- Implementa `FootballDataProviderInterface`

### FormCalculator _(Domain Service)_
`src/Domain/Betting/Service/FormCalculator.php`
- Sin dependencias de infraestructura
- Devuelve string de racha: `"WDLWW"`

### GoalsCounterUpdater _(Domain Service)_
`src/Domain/Betting/Service/GoalsCounterUpdater.php`
- Sin dependencias de infraestructura
- Actualiza contadores `over25Home` / `over15Away` en la entidad `Team`

### TeamSyncService _(Application Service)_
`src/Application/Betting/Service/TeamSyncService.php`
- Orquesta: decide si refrescar, llama API, actualiza Team

### TomorrowBetsService _(Application Service)_
`src/Application/Betting/Service/TomorrowBetsService.php`
- Devuelve lista de `TeamBetDTO` listos para la vista

### TeamBetDTO _(Application DTO)_
`src/Application/Betting/DTO/TeamBetDTO.php`

### BetsController _(Infrastructure)_
`src/Infrastructure/Betting/Http/Controller/BetsController.php`
- `GET /tomorrow/bets` → renderiza `templates/bets/tomorrow.html.twig`

## Persistencia y estrategia de refresco

En lugar de cachear respuestas crudas de la API, se guardan en base de datos **los resultados ya calculados** por equipo. La API solo se llama cuando hay datos nuevos posibles.

### Tabla `team`

| Campo | Descripción |
|-------|-------------|
| `id` | ID interno |
| `name` | Nombre del equipo |
| `league` | Liga a la que pertenece |
| `form_last_8` | Racha últimos 8 en liga (ej: `"WDLWWDLW"`) |
| `form_last_5_home` | Racha últimos 5 en casa |
| `form_last_5_away` | Racha últimos 5 fuera |
| `over_2_5_home` | Veces que marcó más de 2.5 goles (3+) en casa |
| `matches_played_home` | Partidos jugados en casa |
| `over_1_5_away` | Veces que marcó más de 1.5 goles (2+) fuera |
| `matches_played_away` | Partidos jugados fuera |
| `next_fixture_date` | Fecha y hora del próximo partido de liga |
| `next_fixture_opponent_id` | ID interno del rival en el próximo partido |
| `next_fixture_is_home` | Si el equipo juega en casa o fuera |
| `last_synced_at` | Última vez que se actualizaron los datos |

### Tabla `team_external_id`

Para soportar múltiples proveedores de datos sin tocar el esquema principal:

| Campo | Descripción |
|-------|-------------|
| `id` | ID interno |
| `team_id` | FK a `team` |
| `provider` | Nombre del proveedor (ej: `football-data.org`) |
| `external_id` | ID del equipo en ese proveedor |

Añadir un nuevo proveedor en el futuro es solo insertar nuevas filas, sin modificar `team`.

### Lógica de refresco

- Si `next_fixture_date` > ahora → datos válidos, se sirve desde DB sin llamar a la API
- Si `next_fixture_date` <= ahora → el partido ya debería haber terminado, se llama a la API, se actualizan contadores y rachas, y se guarda la nueva `next_fixture_date`
- El refresco se hace **equipo por equipo**, solo cuando cada uno lo necesita

# Paso 4 - Testing

## Regla general

No se puede avanzar al siguiente paso si no pasan todos los tests del paso actual.

## Tipos de tests

### Tests unitarios
- Se pueden usar mocks libremente
- Prueban una sola clase/función en aislamiento

### Tests de integración
- Solo se mockea la llamada a la API externa (football-data.org)
- La base de datos de tests es real (postgres_test)
- Prueban el flujo completo: servicio → DB → respuesta

## Nomenclatura obligatoria

```
test_{acción}_{contexto}__should_{resultado_esperado}
```

Ejemplos:
- `test_getting_data_from_football_data__should_return_expected_json`
- `test_calculating_form__with_5_wins__should_return_WWWWW`
- `test_syncing_team__when_fixture_date_is_past__should_call_api`
- `test_syncing_team__when_fixture_date_is_future__should_not_call_api`
- `test_loading_bets_page__should_return_200`

## Qué testear en cada paso

| Paso | Tests unitarios | Tests de integración |
|------|----------------|---------------------|
| Step 2 - DB | Entidades (getters, lógica de dominio) | Persistencia y lectura de Team + TeamExternalId |
| Step 3 - API Client | Parsing de respuesta JSON mockeada | Request real mockeado, mapeo completo |
| Step 4 - Services | FormCalculator, GoalsCounterUpdater con datos fijos | TeamSyncService y TomorrowBetsService contra DB de test |
| Step 5 - View | - | GET /tomorrow/bets devuelve 200 y renderiza equipos |

