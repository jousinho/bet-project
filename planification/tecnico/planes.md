# Planes de implementación

Notas técnicas detalladas de cada batch de mejoras. Generado por Claude.

---

## Batch 3 — 2026-03-08 (PENDIENTE)

Implementación del gestor de apuestas definido en `mis-requisitos/mejoras-2026-03-08.md`.

> ⚠️ Los criterios concretos de evaluación se definen en una sesión posterior. La arquitectura está preparada para añadirlos sin cambios estructurales.

---

### Resumen de lo que se construye

1. Dos entidades nuevas: `Bet` y `TeamBetStats`
2. Dos servicios nuevos: `BetEvaluatorService` y `BetSettlementService`
3. Un mecanismo extensible para definir criterios (`BetCriterionInterface`)
4. Integración en el flujo de carga de página actual
5. Página nueva `/bets` con historial y estadísticas
6. Migraciones de BD

---

### 1. Entidad `Bet`

`src/Domain/Betting/Entity/Bet.php`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | Autoincremental |
| `team` | Team (ManyToOne) | Equipo al que se refiere la apuesta |
| `fixtureDate` | DateTimeImmutable | Fecha del partido |
| `opponentName` | string | Nombre del rival |
| `betType` | string | Tipo de apuesta (`over_2_5`, `home_win`, `away_win`, `btts`…) |
| `status` | string | `pending` / `won` / `lost` |
| `season` | string | Temporada derivada de `fixtureDate` (ej: `2025/26`) |
| `createdAt` | DateTimeImmutable | Cuándo se creó la apuesta |
| `settledAt` | DateTimeImmutable\|null | Cuándo se liquidó |

Convenciones:
- Constructor privado + `Bet::create(Team, fixtureDate, opponentName, betType, season): self`
- `betType` como constantes de clase: `Bet::TYPE_OVER_2_5`, `Bet::TYPE_HOME_WIN`, etc.
- `status` como constantes: `Bet::STATUS_PENDING`, `Bet::STATUS_WON`, `Bet::STATUS_LOST`

**Unicidad**: índice único sobre `(team_id, fixture_date, bet_type)` para evitar duplicados a nivel de BD, además de comprobación en el servicio.

---

### 2. Entidad `TeamBetStats`

`src/Domain/Betting/Entity/TeamBetStats.php`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | Autoincremental |
| `team` | Team (ManyToOne) | Equipo |
| `betType` | string | Tipo de apuesta |
| `season` | string | Temporada (ej: `2025/26`) |
| `timesBet` | int | Total de apuestas registradas |
| `timesWon` | int | Ganadas |
| `timesLost` | int | Perdidas |

Índice único sobre `(team_id, bet_type, season)`.

Método de dominio: `winRate(): float` → `timesWon / timesBet * 100`.

---

### 3. Interfaz de criterios

`src/Domain/Betting/Criterion/BetCriterionInterface.php`

```php
interface BetCriterionInterface
{
    public function betType(): string;
    public function evaluate(Team $team): bool;
}
```

Cada criterio es una clase que implementa esta interfaz. `evaluate()` recibe el equipo (con sus stats ya sincronizadas) y devuelve `true` si se debe apostar.

Ejemplo futuro:
```php
class Over25HomeCriterion implements BetCriterionInterface
{
    public function betType(): string { return Bet::TYPE_OVER_2_5; }

    public function evaluate(Team $team): bool
    {
        // Si marcó over 2.5 en 7 de los últimos 10 partidos en casa...
    }
}
```

Los criterios se registran en `services.yaml` con un tag y se inyectan automáticamente en `BetEvaluatorService` como colección, sin tocar el servicio al añadir nuevos.

---

### 4. Servicio `BetEvaluatorService`

`src/Application/Betting/Service/BetEvaluatorService.php`

Recibe la colección de criterios vía inyección de dependencias.

```
Para cada equipo con próximo partido:
  Para cada criterio registrado:
    Si criterion->evaluate(team) === true:
      Comprobar si ya existe Bet para (team, fixtureDate, betType)
      Si no existe → crear Bet::create(...) con status=pending
      Si ya existe → no hacer nada (sin duplicados)
```

---

### 5. Servicio `BetSettlementService`

`src/Application/Betting/Service/BetSettlementService.php`

```
Buscar todas las Bets con status=pending cuya fixtureDate <= ahora
Para cada una:
  Buscar en getFinishedMatches() del equipo el partido correspondiente
  (identificar por fecha aproximada, ±1 día)
  Si se encuentra el partido:
    Evaluar si la condición del betType se cumplió en ese partido
    Actualizar bet.status = won | lost
    Actualizar bet.settledAt = ahora
    Actualizar TeamBetStats (timesBet++, timesWon++ o timesLost++)
    Si no existe TeamBetStats para (team, betType, season) → crearlo
```

---

### 6. Derivación de temporada

`src/Domain/Betting/Service/SeasonResolver.php` (domain service puro)

```php
public function resolve(DateTimeImmutable $date): string
{
    $year = (int) $date->format('Y');
    $month = (int) $date->format('n');
    return $month >= 8
        ? $year . '/' . substr((string)($year + 1), 2)
        : ($year - 1) . '/' . substr((string)$year, 2);
}
// marzo 2026 → "2025/26", septiembre 2025 → "2025/26"
```

---

### 7. Integración en `TomorrowBetsService::getData()`

Tras el sync de equipos y antes de construir los DTOs:

```php
// 1. Liquidar apuestas pendientes pasadas
$this->betSettlementService->settleAll();

// 2. Evaluar criterios para partidos futuros
$this->betEvaluatorService->evaluateAll($teams);
```

El DTO `TeamBetDTO` se amplía con `activeBets: string[]` (lista de tipos de apuesta activos para ese partido), para mostrar el indicador en la página actual.

---

### 8. Nueva página `/bets`

**Controller**: `src/Infrastructure/Betting/Http/Controller/BetsHistoryController.php`

**Template**: `templates/bets/history.html.twig`

Contenido:
- Resumen global: total, ganadas, perdidas, % acierto
- Tabla desglosada por tipo de apuesta y temporada (desde `TeamBetStats`)
- Listado paginado de todas las `Bet`s ordenadas por `fixtureDate DESC`

---

### 9. Migraciones

- `Version_...001`: tabla `bet` con índice único `(team_id, fixture_date, bet_type)`
- `Version_...002`: tabla `team_bet_stats` con índice único `(team_id, bet_type, season)`

---

### 10. Tests

**Unitarios**:
- `SeasonResolverTest`: fechas de agosto, julio, enero → temporada correcta
- `Over25HomeCriterionTest` (cuando se defina): equipo con stats concretas → true/false

**Integración**:
- `BetEvaluatorServiceTest`: evalúa equipo, comprueba que se crea Bet en BD, segunda llamada no crea duplicado
- `BetSettlementServiceTest`: Bet pendiente con partido ya jugado → se liquida correctamente y actualiza TeamBetStats

---

### Orden de implementación

1. Entidades `Bet` y `TeamBetStats` + migraciones
2. `SeasonResolver`
3. `BetCriterionInterface` (sin criterios concretos aún)
4. `BetEvaluatorService` y `BetSettlementService`
5. Integración en `TomorrowBetsService`
6. Ampliar `TeamBetDTO` con `activeBets`
7. Indicador visual en `tomorrow.html.twig`
8. Página `/bets` con historial y estadísticas
9. Tests

---

## Batch 1 — 2026-03-07

Implementación de las mejoras pedidas el 2026-03-06.

---

### 1. Mostrar contra qué equipo juega el próximo partido

**Causa raíz**: `nextFixtureOpponentName` no se guardaba en la entidad `Team` durante el sync. El nombre del rival estaba disponible en la respuesta de `getNextFixture` pero no se persistía.

**Solución**:
- `FootballDataClient::getNextFixture()`: añadir `'opponentName' => $opponent['name']` al array de retorno
- `Team`: añadir propiedad `nextFixtureOpponentName: ?string` con getter y setter
- `TeamSyncService::updateNextFixture()`: leer `$fixture['opponentName']` y llamar a `$team->setNextFixtureOpponentName(...)`
- `TomorrowBetsService::buildDto()`: leer `$team->nextFixtureOpponentName()` directamente
- Migración de BD para la nueva columna
- Actualizar tests afectados (`FootballDataClientTest`, `TeamSyncServiceTest`)

---

### 2. Mostrar la racha situacional del rival para todos los partidos

**Causa raíz**: la racha del rival solo se calculaba cuando el partido era mañana (`$isHighlighted`). Para el resto de equipos, siempre era null.

**Solución**: durante el sync de cada equipo, al obtener el próximo partido, hacer una llamada adicional a la API para obtener los partidos recientes del rival y calcular su racha situacional. Guardarla en `Team.nextFixtureOpponentFormSituational`.

- `Team`: añadir propiedad `nextFixtureOpponentFormSituational: ?string`
- `TeamSyncService`: añadir método privado `fetchOpponentFormSituational(opponentExternalId, league, teamIsHome)` que llama a `getFinishedMatches` del rival, filtra por su condición (inversa a la nuestra) y calcula la racha con `FormCalculator`
- `TomorrowBetsService::buildDto()`: leer `$team->nextFixtureOpponentFormSituational()` directamente, sin guarda `$isHighlighted`
- Migración de BD
- Tests: `TeamSyncServiceTest` añade stub para la segunda llamada a `getFinishedMatches` y aserto del campo

**Nota sobre la inversión de condición**: cuando nuestro equipo juega en casa (`teamIsHome = true`), el rival juega fuera → filtramos sus partidos donde `isHome === false`. Viceversa cuando jugamos fuera.

---

### 3. Indicar cuál es el partido más reciente en la racha

**Contexto**: `FormCalculator` genera la cadena con el partido más reciente primero. Con "WDLWW", la W de la izquierda es el más reciente.

**Solución**: añadir en el template la etiqueta `Form — last 8 (newest → oldest)` junto a cada sección de racha. El orden de `FormCalculator` no cambia; solo se añade contexto visual.

---

### 4. Refactorizar servicios: extraer métodos privados

**Afecta a**: `TeamSyncService::sync()`.

Bloques extraídos a métodos privados:
```
sync()
  ├── private isAlreadySynced(Team, DateTimeImmutable): bool
  ├── private findProviderExternalId(Team): ?string
  ├── private updateMatchStats(Team, string): void
  └── private updateNextFixture(Team, string): void
        └── private fetchOpponentFormSituational(string, string, bool): ?string
```

Los tests de `TeamSyncServiceTest` no necesitan cambios porque prueban el comportamiento externo del método público.

---

### 5. Eliminar carpeta `src/Controller` generada por Symfony

Borrar `app/src/Controller/` (solo contenía un `.gitignore` generado por Symfony). El controller real del proyecto está en `app/src/Infrastructure/Betting/Http/Controller/`.

---

### 6. Entidades: constructor privado + factory method estático

Patrón aplicado en `Team` y `TeamExternalId`:

```php
private function __construct(...) { ... }

public static function create(...): self
{
    return new self(...);
}
```

Archivos actualizados: `SeedTeamsCommand`, `TeamTest`, `TeamExternalIdTest` y tests de integración que instanciaban entidades directamente.

---

### 7. Getters de entidades sin prefijo `get`

Renombrados en `Team` y `TeamExternalId`: `getName()` → `name()`, `getLeague()` → `league()`, etc.

Archivos actualizados: servicios, repositorios, tests unitarios y de integración. Los setters no se tocan.

---

## Batch 2 — 2026-03-08

Implementación de las mejoras pedidas el 2026-03-08.

---

### 8. [DISEÑO] Mostrar primero el equipo local en la cabecera

**Qué se veía**: `nuestroEquipo vs rival`, siempre nuestro equipo primero independientemente de si jugaba en casa o fuera.

**Solución**: cambio únicamente en `tomorrow.html.twig`. Condición `{% if bet.isHome %}` para intercambiar el orden de los nombres. Sin cambios PHP ni en tests.

```twig
{% if bet.isHome %}
    {{ bet.teamName }} <span class="vs">vs</span> {{ bet.nextFixtureOpponentName ?? 'TBD' }}
{% else %}
    {{ bet.nextFixtureOpponentName ?? 'TBD' }} <span class="vs">vs</span> {{ bet.teamName }}
{% endif %}
```

---

### 9. [BUG] El primer partido de la lista nunca mostraba la racha del rival

**Síntoma**: la columna derecha del primer partido mostraba siempre "No data" en la racha situacional del rival.

**Causa raíz**: `isAlreadySynced()` devolvía `true` (saltaba el sync) cuando `nextFixtureDate > now`, aunque `nextFixtureOpponentFormSituational` fuera `null`. Si durante el primer sync de un equipo la llamada a la API para obtener la racha del rival fallaba silenciosamente (rate limit u error transitorio), el `null` quedaba grabado en BD y nunca se sobreescribía porque el sync siempre se saltaba en peticiones posteriores.

El equipo con el partido más próximo (primero en la lista) era el más propenso a acumular este problema.

**Solución en `TeamSyncService::isAlreadySynced()`**: añadir condición — si `nextFixtureOpponentFormSituational` es `null`, no considerar el equipo como sincronizado, forzando un reintento:

```php
private function isAlreadySynced(Team $team, DateTimeImmutable $now): bool
{
    if ($team->nextFixtureDate() === null || $team->nextFixtureDate() <= $now) {
        return false;
    }
    if ($team->nextFixtureOpponentFormSituational() === null) {
        return false;
    }
    $hoursUntilFixture = ($team->nextFixtureDate()->getTimestamp() - $now->getTimestamp()) / 3600;
    return $hoursUntilFixture >= 48;
}
```

También se fuerza re-sync si el partido está a menos de 48 horas (para refrescar datos cerca del partido).

**Test añadido**: `test_syncing_team__when_next_fixture_is_within_48h__should_call_api`
**Test actualizado**: `test_syncing_team__when_next_fixture_is_future__should_not_call_api` ahora setea `nextFixtureOpponentFormSituational` en el equipo de prueba para representar correctamente un equipo "ya sincronizado".
