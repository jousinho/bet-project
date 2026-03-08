# Refactor: separación de dominios Tracking y Betting — 2026-03-08

## Objetivo

Separar el dominio `Betting` actual en dos bounded contexts bien definidos:

- **`Tracking`** — responsable de conocer equipos, sincronizar datos con la API externa y mantener stats de forma y goles.
- **`Betting`** — responsable de evaluar criterios, registrar apuestas y liquidarlas.

El dominio `Betting` no accederá directamente a la entidad `Team`. En su lugar, recibirá un objeto de valor `TeamSnapshot` con los datos que necesita.

---

## Estructura final de carpetas

```
src/
  Domain/
    Tracking/
      Entity/
        Team.php
        TeamExternalId.php
      Repository/
        TeamRepositoryInterface.php
        TeamExternalIdRepositoryInterface.php
        FootballDataProviderInterface.php
      Service/
        FormCalculator.php
        GoalsCounterUpdater.php
    Betting/
      Entity/
        Bet.php
        TeamBetStats.php
      Repository/
        BetRepositoryInterface.php
        TeamBetStatsRepositoryInterface.php
      Criterion/
        BetCriterionInterface.php
        Over25Criterion.php
        HomeWinCriterion.php
      Service/
        SeasonResolver.php
      ValueObject/
        TeamSnapshot.php   ← NUEVO

  Application/
    Tracking/
      Service/
        TeamSyncService.php
    Betting/
      Service/
        BetEvaluatorService.php
        BetSettlementService.php
        TomorrowBetsService.php
      DTO/
        TeamBetDTO.php

  Infrastructure/
    Tracking/
      Persistence/Doctrine/
        DoctrineTeamRepository.php
        DoctrineTeamExternalIdRepository.php
      Http/Client/
        FootballDataClient.php
      Command/
        SeedTeamsCommand.php
    Betting/
      Persistence/Doctrine/
        DoctrineBetRepository.php
        DoctrineTeamBetStatsRepository.php
      Http/Controller/
        BetsController.php
        BetsHistoryController.php
```

---

## Pasos de implementación

### Paso 1 — Crear `TeamSnapshot` (Value Object)

Crear `src/Domain/Betting/ValueObject/TeamSnapshot.php`.

Es un objeto inmutable con todos los datos que los criterios y servicios de `Betting` necesitan de un equipo:

```php
final class TeamSnapshot
{
    public function __construct(
        public readonly int     $teamId,
        public readonly string  $teamName,
        public readonly string  $league,
        public readonly ?string $formLast8,
        public readonly ?string $formLast5Home,
        public readonly ?string $formLast5Away,
        public readonly int     $over25Home,
        public readonly int     $matchesPlayedHome,
        public readonly int     $over15Away,
        public readonly int     $matchesPlayedAway,
        public readonly ?\DateTimeImmutable $nextFixtureDate,
        public readonly ?int    $nextFixtureMatchday,
        public readonly ?string $nextFixtureOpponentName,
        public readonly ?bool   $nextFixtureIsHome,
        public readonly ?string $nextFixtureOpponentFormSituational,
        public readonly ?int    $nextFixtureOpponentId,
    ) {}

    public static function fromTeam(Team $team): self { ... }
}
```

El método estático `fromTeam` se coloca en `Application/Tracking` o en el propio VO — como vive en `Domain/Betting` y `Team` vive en `Domain/Tracking`, el `fromTeam` debe ir en una capa que vea ambos dominios: **`Application/Betting/Service/TomorrowBetsService`** hace la conversión, no el VO.

Para evitar dependencia circular, `TeamSnapshot::__construct` recibe solo tipos primitivos y `\DateTimeImmutable`. La conversión `Team → TeamSnapshot` se hace en `TomorrowBetsService` y `BetEvaluatorService`.

---

### Paso 2 — Adaptar `BetCriterionInterface`

Cambiar la firma para recibir `TeamSnapshot` en vez de `Team`:

```php
interface BetCriterionInterface
{
    public function betType(): string;
    public function isMet(TeamSnapshot $team): bool;
}
```

Actualizar `Over25Criterion` y `HomeWinCriterion` para usar `TeamSnapshot` (los campos son idénticos, solo cambia el tipo del argumento).

---

### Paso 3 — Adaptar `BetEvaluatorService`

Cambiar `evaluateAll(array $teams)` para recibir `TeamSnapshot[]` en vez de `Team[]`:

```php
public function evaluateAll(array $snapshots): void
```

Internamente ya no accede a `$team->nextFixtureDate()` etc. sino a `$snapshot->nextFixtureDate`.

La conversión `Team[] → TeamSnapshot[]` la hace `TomorrowBetsService` antes de llamar a `evaluateAll`.

---

### Paso 4 — Adaptar `BetSettlementService`

`BetSettlementService` accede a `$bet->team()` para obtener el `externalId` y el `league`. Como `Bet` tendrá referencia a `Team` (que sigue siendo una entidad Doctrine), esto no cambia estructuralmente.

Sin embargo, el método `findExternalId(Team $team)` puede quedarse tal cual — `Bet` sigue teniendo `ManyToOne` a `Team` porque necesita persistir la relación en BD. No hay dependencia de dominio problemática aquí.

---

### Paso 5 — Mover carpetas

Renombrar en filesystem y actualizar namespaces:

| Origen | Destino |
|---|---|
| `Domain/Betting/Entity/Team.php` | `Domain/Tracking/Entity/Team.php` |
| `Domain/Betting/Entity/TeamExternalId.php` | `Domain/Tracking/Entity/TeamExternalId.php` |
| `Domain/Betting/Repository/TeamRepositoryInterface.php` | `Domain/Tracking/Repository/TeamRepositoryInterface.php` |
| `Domain/Betting/Repository/TeamExternalIdRepositoryInterface.php` | `Domain/Tracking/Repository/TeamExternalIdRepositoryInterface.php` |
| `Domain/Betting/Repository/FootballDataProviderInterface.php` | `Domain/Tracking/Repository/FootballDataProviderInterface.php` |
| `Domain/Betting/Service/FormCalculator.php` | `Domain/Tracking/Service/FormCalculator.php` |
| `Domain/Betting/Service/GoalsCounterUpdater.php` | `Domain/Tracking/Service/GoalsCounterUpdater.php` |
| `Application/Betting/Service/TeamSyncService.php` | `Application/Tracking/Service/TeamSyncService.php` |
| `Infrastructure/Betting/Persistence/Doctrine/DoctrineTeamRepository.php` | `Infrastructure/Tracking/Persistence/Doctrine/DoctrineTeamRepository.php` |
| `Infrastructure/Betting/Persistence/Doctrine/DoctrineTeamExternalIdRepository.php` | `Infrastructure/Tracking/Persistence/Doctrine/DoctrineTeamExternalIdRepository.php` |
| `Infrastructure/Betting/Http/Client/FootballDataClient.php` | `Infrastructure/Tracking/Http/Client/FootballDataClient.php` |
| `Infrastructure/Betting/Command/SeedTeamsCommand.php` | `Infrastructure/Tracking/Command/SeedTeamsCommand.php` |
| `Infrastructure/Betting/Http/Controller/BetsController.php` | `Infrastructure/Betting/Http/Controller/BetsController.php` (sin cambio) |

---

### Paso 6 — Actualizar `services.yaml`

Actualizar los bindings de interfaces a implementaciones con los nuevos namespaces:

```yaml
App\Domain\Tracking\Repository\TeamRepositoryInterface:
    class: App\Infrastructure\Tracking\Persistence\Doctrine\DoctrineTeamRepository

App\Domain\Tracking\Repository\TeamExternalIdRepositoryInterface:
    class: App\Infrastructure\Tracking\Persistence\Doctrine\DoctrineTeamExternalIdRepository

App\Domain\Tracking\Repository\FootballDataProviderInterface:
    class: App\Infrastructure\Tracking\Http\Client\FootballDataClient
    arguments:
        $apiKey: '%env(FOOTBALL_DATA_API_KEY)%'
```

Actualizar también los autoconfigure resources:

```yaml
App\Domain\Tracking\:
    resource: '../src/Domain/Tracking/'
    exclude:
        - '../src/Domain/Tracking/**/Entity/'

App\Application\Tracking\:
    resource: '../src/Application/Tracking/'

App\Infrastructure\Tracking\:
    resource: '../src/Infrastructure/Tracking/'
    exclude:
        - '../src/Infrastructure/Tracking/**/Persistence/Doctrine/Migrations/'
        - '../src/Infrastructure/Tracking/**/Http/Controller/'
```

---

### Paso 7 — Actualizar tests

Los tests de integración que usan `TeamRepositoryInterface`, `FootballDataProviderInterface` etc. deben actualizar sus `use` statements con los nuevos namespaces.

Ficheros afectados:
- `tests/Integration/Infrastructure/Betting/DoctrineTeamRepositoryTest.php`
- `tests/Integration/Infrastructure/Betting/TeamSyncServiceTest.php`
- `tests/Integration/Infrastructure/Betting/TomorrowBetsServiceTest.php`
- `tests/Integration/Infrastructure/Betting/FootballDataClientIntegrationTest.php`
- `tests/Unit/Infrastructure/Betting/FootballDataClientTest.php`

Mover también los tests de Team/TeamExternalId a `Tests/Unit/Domain/Tracking/`.

---

### Paso 8 — Actualizar `services_test.yaml`

Actualizar los servicios públicos de test con los nuevos namespaces.

---

## Orden de ejecución recomendado

1. Crear `TeamSnapshot` VO
2. Adaptar criterios (`BetCriterionInterface`, `Over25Criterion`, `HomeWinCriterion`)
3. Adaptar `BetEvaluatorService`
4. Adaptar `TomorrowBetsService` (hace la conversión Team → TeamSnapshot)
5. Mover ficheros de `Tracking` y actualizar namespaces
6. Actualizar `services.yaml` y `services_test.yaml`
7. Actualizar tests
8. Ejecutar test suite completa — todos deben estar en verde antes de commitear

---

## Notas importantes

- No hay migración de BD — los nombres de tablas no cambian, solo los namespaces PHP.
- `Bet` sigue teniendo `ManyToOne` a `Team` (necesario para Doctrine). Esto es una dependencia de infraestructura aceptable, no una dependencia de dominio.
- Si en el futuro `Betting` necesita datos de `Tracking` en tiempo de ejecución, lo hará a través de un repositorio o un servicio de consulta, nunca importando la entidad `Team` directamente en lógica de dominio.
