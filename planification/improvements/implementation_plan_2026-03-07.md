# Plan de implementación — fixes_from_user_2026-03-06

---

## 1. No sale contra qué equipo juega el próximo partido

**Causa raíz**: en `TomorrowBetsService::buildDto()`, la búsqueda del rival sólo se ejecuta
cuando `$isHighlighted === true`. Para equipos que no juegan mañana, `$opponentName` siempre
es `null` y la plantilla muestra "TBD".

**Solución propuesta** (en lugar de sólo quitar la guarda `$isHighlighted`):
Añadir el campo `nextFixtureOpponentName: ?string` directamente a la entidad `Team` y
guardarlo durante el sync, ya que la API de football-data.org devuelve el nombre del rival
en la misma llamada a `getNextFixture` (el objeto `homeTeam`/`awayTeam` incluye `name`).

Pasos:
- `FootballDataClient::getNextFixture()`: añadir `'opponentName' => $opponent['name']` al array de retorno.
- `FootballDataProviderInterface`: actualizar la firma del contrato (docblock del array).
- `Team`: añadir propiedad `nextFixtureOpponentName: ?string` con getter y setter.
- `TeamSyncService::sync()`: leer `$fixture['opponentName']` y llamar a `$team->setNextFixtureOpponentName(...)`.
- `TomorrowBetsService::buildDto()`: leer `$team->getNextFixtureOpponentName()` directamente, sin hacer lookup a la DB para el nombre (el lookup a la DB sigue siendo necesario para los STATS del rival, ver punto 2).
- Migración de BD para la nueva columna.
- Actualizar tests afectados (`FootballDataClientTest`, `TeamSyncServiceTest`).

---

## 2. No salen los datos del equipo contra el que juega el próximo partido

**Causa raíz**: en `TomorrowBetsService::buildDto()`, los stats del rival (form, goles) también
están guardados bajo la guarda `$isHighlighted`. Para partidos no mañana, nunca se muestran.

**Solución**: eliminar la guarda `$isHighlighted` del bloque que hace el lookup del rival en la DB y
que rellena `$opponentFormSituational`, `$opponentOverCount`, `$opponentMatchesPlayed`.
El sync del rival ya tiene su propia guarda interna (`nextFixtureDate > $now`), así que no se
re-sincroniza innecesariamente.

La condición en el template que muestra los stats del rival (`{% if bet.highlightedTomorrow and bet.nextFixtureOpponentName %}`)
también debe abrirse: mostrar los datos del rival siempre que `bet.nextFixtureOpponentName` exista,
independientemente de si es mañana o no.

Nota: si el rival no es un equipo de los que seguimos en la app, sus stats no estarán disponibles.
Eso es una limitación de diseño, no un bug. Se puede mostrar "—" en ese caso (ya ocurre así).

---

## 3. Indicar cuál es el partido más reciente en la racha

**Contexto**: el `FormCalculator` genera la cadena con el partido más reciente primero
(el array de entrada viene ordenado more-recent-first, como indica su docblock).
Así, con la cadena "WDLWW", la W de la izquierda es el partido más reciente.

**Solución**: añadir en la plantilla `tomorrow.html.twig` una pequeña indicación visual
debajo o al lado de cada racha. Opciones (elegir una):
- Añadir etiquetas texto: `← más reciente` al principio de los badges.
- Añadir un texto fijo en la cabecera de la sección: "Los resultados se muestran del más reciente (izquierda) al más antiguo (derecha)".

El orden en el que `FormCalculator` genera la cadena NO cambia; sólo se añade contexto visual.

---

## 4. Refactorizar servicios: extraer métodos privados

**Afecta a**: `TeamSyncService::sync()` (el más denso).

El método `sync()` tiene 4 bloques funcionales diferenciados. Extraer cada uno a un método privado:

```
sync()
  ├── private function isAlreadySynced(Team $team, DateTimeImmutable $now): bool
  ├── private function fetchProviderExternalId(Team $team): ?string
  ├── private function updateForm(Team $team, array $matches): void
  └── private function updateNextFixture(Team $team, string $externalId): void
```

`TomorrowBetsService::buildDto()` y `getData()` ya son suficientemente cortos; no se tocan.

El usuario indica que los tests de servicios no deberían necesitar cambios porque sólo
se reorganiza la estructura interna del método público. Confirmar esto antes de commitear
(los tests de `TeamSyncServiceTest` prueban el comportamiento externo, no los métodos privados).

---

## 5. Eliminar carpeta `src/Controller` generada por Symfony

**Qué borrar**: `app/src/Controller/` (contiene sólo un `.gitignore` generado por Symfony,
no pertenece a la arquitectura del proyecto).

El controller real del proyecto está en `app/src/Infrastructure/Betting/Http/Controller/` y no se toca.

---

## 6. Entidades: constructor privado + factory method estático

**Afecta a**: `Team` y `TeamExternalId`.

Patrón a aplicar:
```php
// Antes
public function __construct(string $name, string $league) { ... }

// Después
private function __construct(string $name, string $league) { ... }

public static function create(string $name, string $league): self
{
    return new self($name, $league);
}
```

Misma transformación para `TeamExternalId::create(Team $team, string $provider, string $externalId)`.

**Archivos a actualizar** (todos los que hacen `new Team(...)` o `new TeamExternalId(...)`):
- `SeedTeamsCommand` → `Team::create(...)`, `TeamExternalId::create(...)`
- `TeamTest` → `Team::create(...)`
- `TeamExternalIdTest` → `Team::create(...)`, `TeamExternalId::create(...)`
- Cualquier otro test de integración que instancie entidades directamente.

**Nota**: los tests SÍ necesitan cambios en este punto (al contrario que el punto 4).

---

## 7. Getters de entidades sin prefijo `get`

**Afecta a**: `Team` y `TeamExternalId`.

Renombrar todos los métodos `getXxx()` a `xxx()`. Ejemplos:
- `getName()` → `name()`
- `getLeague()` → `league()`
- `getFormLast8()` → `formLast8()`
- `getNextFixtureDate()` → `nextFixtureDate()`
- etc.

**Archivos a actualizar** (todos los que llaman a estos getters):
- `TeamTest`, `TeamExternalIdTest`
- `TeamSyncService` (usa `$team->getName()`, `$team->getLeague()`, `$team->getNextFixtureDate()`, etc.)
- `TomorrowBetsService` (usa múltiples getters de `Team`)
- `GoalsCounterUpdater` (usa getters si los hay)
- `DoctrineTeamRepository`, `DoctrineTeamExternalIdRepository`
- Tests de integración (`TeamSyncServiceTest`, `TomorrowBetsServiceTest`, `DoctrineTeamRepositoryTest`)
- Twig templates si acceden directamente a propiedades (en Twig `bet.teamName` accede al getter automáticamente — el DTO `TeamBetDTO` es `readonly` con propiedades públicas, así que no hay getters que cambiar ahí)

Los setters (`setXxx()`) no se mencionan en el fix; se dejan como están salvo que indiques lo contrario.

---

## Orden de implementación recomendado

1. **Punto 5** — Borrar `src/Controller/`. Sin riesgo, sin dependencias.
2. **Punto 4** — Refactorizar `TeamSyncService`. Sin cambio de API pública, tests no cambian.
3. **Puntos 6 y 7** — Cambios en entidades (hacerlos juntos para no pasar dos veces por todos los archivos afectados). Tests SÍ cambian.
4. **Punto 1** — Añadir `nextFixtureOpponentName` a `Team` + sync + migración.
5. **Punto 2** — Quitar guarda `$isHighlighted` del lookup de stats del rival.
6. **Punto 3** — Ajuste visual en la plantilla Twig.

---

## Sugerencias adicionales no incluidas en el fix original

### A. Proteger el sync excesivo cuando no hay próximo partido
`TeamSyncService::sync()` sólo omite el sync si `nextFixtureDate > now`. Si un equipo no tiene
partido programado (`nextFixtureDate === null`), se sincroniza en cada request. Se podría añadir
una comprobación: si `lastSyncedAt` fue hace menos de X horas, saltar. Esto evitaría llamadas
innecesarias a la API cuando no hay fixture.

### B. Nombre del factory method en `TeamExternalId`
`TeamExternalId::create()` es genérico. Podría ser más expresivo: `TeamExternalId::forProvider()`.
Pero `create()` es perfectamente válido y consistente con `Team::create()`. A criterio tuyo.
