# Step 4 - Services (Business Logic)

## FormCalculator _(Domain Service)_

`src/Domain/Betting/Service/FormCalculator.php`

- Sin dependencias de infraestructura (testeable en unitario puro)
- Recibe array de partidos ya procesados y el `externalTeamId`
- Devuelve string con la racha: `"WDLWW"`

Lógica:
- Recorrer partidos ordenados de más reciente a más antiguo
- Comparar goles del equipo vs goles del rival → `W` / `D` / `L`
- Concatenar hasta el límite indicado (5 u 8)

---

## GoalsCounterUpdater _(Domain Service)_

`src/Domain/Betting/Service/GoalsCounterUpdater.php`

- Sin dependencias de infraestructura
- Recibe array de partidos y la entidad `Team`
- Recalcula desde cero los contadores: `over25Home`, `matchesPlayedHome`, `over15Away`, `matchesPlayedAway`

Lógica:
- Iterar todos los partidos recibidos
- Si jugó en casa: incrementar `matchesPlayedHome`. Si marcó 3+, incrementar `over25Home`
- Si jugó fuera: incrementar `matchesPlayedAway`. Si marcó 2+, incrementar `over15Away`

---

## TeamSyncService _(Application Service)_

`src/Application/Betting/Service/TeamSyncService.php`

Orquesta el refresco de datos de un equipo individual. Inyecta `TeamRepositoryInterface`, `FootballDataProviderInterface`.

### Lógica

```
1. Si nextFixtureDate > ahora → datos válidos, no hacer nada
2. Si nextFixtureDate <= ahora (o es null):
   a. Obtener externalId del equipo para "football-data.org"
   b. Llamar a getFinishedMatches() con límite 20
   c. Recalcular formLast8, formLast5Home, formLast5Away con FormCalculator
   d. Recalcular contadores con GoalsCounterUpdater (desde cero)
   e. Llamar a getNextFixture() para obtener próximo partido
   f. Actualizar nextFixtureDate, nextFixtureOpponentId, nextFixtureIsHome
   g. Actualizar lastSyncedAt = ahora
   h. Persistir Team via TeamRepository
```

---

## TomorrowBetsService _(Application Service)_

`src/Application/Betting/Service/TomorrowBetsService.php`

Prepara todos los datos para la vista. Inyecta `TeamRepositoryInterface`, `TeamSyncService`.

### Lógica

```
1. Cargar todos los equipos tracked via TeamRepository
2. Para cada equipo, llamar a TeamSyncService (decide si refrescar)
3. Ordenar equipos por nextFixtureDate ASC
4. Para cada equipo:
   a. Marcar si nextFixtureDate es mañana (highlighted = true)
   b. Si tiene partido mañana, cargar datos del rival y sincronizarlo si hace falta
   c. Construir TeamBetDTO con todos los datos listos para la vista
5. Devolver lista de TeamBetDTO
```

---

## TeamBetDTO _(Application DTO)_

`src/Application/Betting/DTO/TeamBetDTO.php`

```php
readonly class TeamBetDTO
{
    public function __construct(
        public string   $teamName,
        public string   $nextFixtureDate,
        public ?string  $nextFixtureOpponentName,
        public bool     $isHome,
        public bool     $highlightedTomorrow,

        public ?string  $formLast8,
        public ?string  $formSituational,          // últimos 5 en casa o fuera
        public ?string  $opponentFormSituational,  // últimos 5 del rival (opuesto)

        public int      $teamOverCount,            // over25Home o over15Away
        public int      $teamMatchesPlayed,
        public int      $opponentOverCount,
        public int      $opponentMatchesPlayed,
    ) {}
}
```

---

## Tests

### Unitarios

Ubicación: `tests/Unit/Domain/Betting/Service/`

**FormCalculator**
- `test_calculating_form__with_5_wins__should_return_WWWWW`
- `test_calculating_form__with_mixed_results__should_return_correct_string`
- `test_calculating_form__when_fewer_than_requested_matches__should_return_available_results`
- `test_calculating_form__when_no_matches__should_return_empty_string`

**GoalsCounterUpdater**
- `test_updating_home_counters__when_team_scores_3_goals__should_increment_over25`
- `test_updating_home_counters__when_team_scores_2_goals__should_not_increment_over25`
- `test_updating_away_counters__when_team_scores_2_goals__should_increment_over15`
- `test_updating_away_counters__when_team_scores_1_goal__should_not_increment_over15`

### Integración (DB real, API mockeada con MockHttpClient)

Ubicación: `tests/Integration/Infrastructure/Betting/`

**TeamSyncService**
- `test_syncing_team__when_next_fixture_is_future__should_not_call_api`
- `test_syncing_team__when_next_fixture_is_past__should_call_api_and_update_team`
- `test_syncing_team__when_synced__should_update_last_synced_at`

**TomorrowBetsService**
- `test_getting_bets_data__should_return_all_tracked_teams`
- `test_getting_bets_data__should_order_teams_by_next_fixture_date`
- `test_getting_bets_data__when_team_plays_tomorrow__should_be_highlighted`
- `test_getting_bets_data__when_team_plays_at_home__should_use_home_stats`
- `test_getting_bets_data__when_team_plays_away__should_use_away_stats`
