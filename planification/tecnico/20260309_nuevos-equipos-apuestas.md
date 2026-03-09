# Plan técnico — Nuevos equipos y tipos de apuesta — 2026-03-09

## Parte 1 — Nuevos equipos

### Qué se hace

Ampliar `SeedTeamsCommand` con los 23 equipos de 6 ligas europeas.

### Cambios en `SeedTeamsCommand`

Añadir las entradas al array `TEAMS`:

```php
// Premier League
['name' => 'Arsenal FC',              'league' => 'PL',  'external_id' => '57'],
['name' => 'Chelsea FC',              'league' => 'PL',  'external_id' => '61'],
['name' => 'Liverpool FC',            'league' => 'PL',  'external_id' => '64'],
['name' => 'Manchester City FC',      'league' => 'PL',  'external_id' => '65'],
['name' => 'Manchester United FC',    'league' => 'PL',  'external_id' => '66'],

// Bundesliga
['name' => 'Bayer 04 Leverkusen',     'league' => 'BL1', 'external_id' => '3'],
['name' => 'Borussia Dortmund',       'league' => 'BL1', 'external_id' => '4'],
['name' => 'FC Bayern München',       'league' => 'BL1', 'external_id' => '5'],

// Ligue 1
['name' => 'Olympique Lyonnais',      'league' => 'FL1', 'external_id' => '523'],
['name' => 'Paris Saint-Germain FC',  'league' => 'FL1', 'external_id' => '524'],
['name' => 'AS Monaco FC',            'league' => 'FL1', 'external_id' => '548'],

// Serie A
['name' => 'AC Milan',                'league' => 'SA',  'external_id' => '98'],
['name' => 'AS Roma',                 'league' => 'SA',  'external_id' => '100'],
['name' => 'Atalanta BC',             'league' => 'SA',  'external_id' => '102'],
['name' => 'FC Internazionale Milano','league' => 'SA',  'external_id' => '108'],
['name' => 'Juventus FC',             'league' => 'SA',  'external_id' => '109'],
['name' => 'SSC Napoli',              'league' => 'SA',  'external_id' => '113'],
['name' => 'Como 1907',               'league' => 'SA',  'external_id' => '7397'],

// La Liga
['name' => 'FC Barcelona',            'league' => 'PD',  'external_id' => '81'],
['name' => 'Real Madrid CF',          'league' => 'PD',  'external_id' => '86'],
['name' => 'Club Atlético de Madrid', 'league' => 'PD',  'external_id' => '78'],

// Eredivisie
['name' => 'PSV',                     'league' => 'DED', 'external_id' => '674'],
['name' => 'AFC Ajax',                'league' => 'DED', 'external_id' => '678'],
```

### Ejecución del seed

Tras el commit, ejecutar:
```
docker compose exec -T php-cli php bin/console app:teams:seed
```

No hay migración de BD — la estructura de tablas no cambia.

---

## Parte 2 — Nuevos tipos de apuesta ✅

Ver plan detallado en `20260309_plan-nuevas-apuestas.md`. Implementado completo.

### Resumen de cambios

- **`Team`**: +8 nuevas columnas (`over15Home`, `over35Home/Away`, `over05HtHome/Away`, `winBothHalvesHome/Away`, `over25Away`) + getters/setters
- **Migración**: `Version20260309112301` — añade las 8 columnas a la tabla `team`
- **`FootballDataClient`**: `getFinishedMatches` ahora devuelve también `halfTimeGoalsScored` y `halfTimeGoalsAgainst`
- **`GoalsCounterUpdater`**: calcula 12 contadores (era 4)
- **`Bet`**: +7 constantes de tipo
- **7 nuevas clases de criterio**: `Over15Criterion`, `Over35Criterion`, `Under25Criterion`, `AwayWinCriterion`, `DoubleChanceCriterion`, `Over05HalfTimeCriterion`, `WinBothHalvesCriterion`
- **`BetSettlementService`**: evalúa los 9 tipos de apuesta
- **Template `tomorrow.html.twig`**: pills de colores para los 7 nuevos tipos
- **`SyncTeamsCommand`** (`app:teams:sync`): comando para sincronizar todos los equipos con rate limit seguro

### Tests
113 tests, 216 assertions, todo verde.

---

## Orden de ejecución ✅

1. ✅ Actualizar `SeedTeamsCommand` con los 23 equipos
2. ✅ Ejecutar el seed: `docker compose exec -T php-cli php bin/console app:teams:seed`
3. ✅ Columnas nuevas en `Team` + migración
4. ✅ `FootballDataClient` con halfTime
5. ✅ `GoalsCounterUpdater` ampliado
6. ✅ Constantes en `Bet` + 7 criterios + liquidación
7. ✅ Sincronizar equipos nuevos: `docker compose exec -T php-cli php bin/console app:teams:sync`
