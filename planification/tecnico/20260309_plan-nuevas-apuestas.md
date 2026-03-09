# Plan técnico — Nuevos tipos de apuesta — 2026-03-09

## Visión general

Se añaden 7 nuevos tipos de apuesta. Algunos reutilizan stats ya existentes; otros requieren nuevas columnas en `Team` y cambios en `GoalsCounterUpdater` y `FootballDataProviderInterface`.

---

## Análisis de dependencias por apuesta

| Apuesta | Constante | Stats nuevas en Team | Migración | Cambios API |
|---|---|---|---|---|
| Over 1.5 goles | `TYPE_OVER_1_5` | `over15Home` | ✅ | No |
| Over 3.5 goles | `TYPE_OVER_3_5` | `over35Home`, `over35Away` | ✅ | No |
| Under 2.5 goles | `TYPE_UNDER_2_5` | Ninguna (derivado) | No | No |
| Victoria visitante | `TYPE_AWAY_WIN` | Ninguna (derivado) | No | No |
| Doble oportunidad (1X) | `TYPE_DOUBLE_CHANCE` | Ninguna (derivado) | No | No |
| Over 0.5 1ª parte | `TYPE_OVER_05_HT` | `over05HtHome`, `over05HtAway` | ✅ | `halfTimeGoals` |
| Ganar ambas partes | `TYPE_WIN_BOTH_HALVES` | `winBothHalvesHome`, `winBothHalvesAway` | ✅ | `halfTimeGoals` |

---

## Paso 0 — Migración y nuevas columnas en `Team`

### Nuevas propiedades en `Team`:

```php
#[ORM\Column(options: ['default' => 0])]
private int $over15Home = 0;

#[ORM\Column(options: ['default' => 0])]
private int $over35Home = 0;

#[ORM\Column(options: ['default' => 0])]
private int $over35Away = 0;

#[ORM\Column(options: ['default' => 0])]
private int $over05HtHome = 0;

#[ORM\Column(options: ['default' => 0])]
private int $over05HtAway = 0;

#[ORM\Column(options: ['default' => 0])]
private int $winBothHalvesHome = 0;

#[ORM\Column(options: ['default' => 0])]
private int $winBothHalvesAway = 0;
```

Getters y setters para cada una, siguiendo convenciones del proyecto.

También añadir a `TeamSnapshot` las 7 propiedades nuevas.

Una migración Doctrine con las 7 columnas nuevas.

---

## Paso 1 — Ampliar `FootballDataProviderInterface` y `FootballDataClient`

Actualmente `getFinishedMatches` devuelve por partido:
```
['date', 'isHome', 'goalsScored', 'goalsAgainst', 'result']
```

Se añade `halfTimeGoalsScored` e `halfTimeGoalsAgainst`:
```
['date', 'isHome', 'goalsScored', 'goalsAgainst', 'result', 'halfTimeGoalsScored', 'halfTimeGoalsAgainst']
```

En `FootballDataClient::getFinishedMatches()`:
```php
$htHome = $match['score']['halfTime']['home'] ?? 0;
$htAway = $match['score']['halfTime']['away'] ?? 0;
$htGoalsScored  = $isHome ? $htHome : $htAway;
$htGoalsAgainst = $isHome ? $htAway : $htHome;

$result[] = [
    ...
    'halfTimeGoalsScored'  => $htGoalsScored,
    'halfTimeGoalsAgainst' => $htGoalsAgainst,
];
```

Actualizar `FootballDataProviderInterface` con el nuevo shape del array de retorno.

Tests afectados: `FootballDataClientTest` — añadir los nuevos campos a los stubs.

---

## Paso 2 — Ampliar `GoalsCounterUpdater`

Añadir el cálculo de los 7 nuevos contadores:

```php
// por partido, si isHome:
$over15Home    += ($goalsScored + $goalsAgainst) >= 2 ? 1 : 0;
$over35Home    += ($goalsScored + $goalsAgainst) >= 4 ? 1 : 0;
$over05HtHome  += ($htScored   + $htAgainst)    >= 1 ? 1 : 0;
$winBothHalvesHome += ($htScored > $htAgainst && $goalsScored > $goalsAgainst) ? 1 : 0;

// si away:
$over35Away    += ($goalsScored + $goalsAgainst) >= 4 ? 1 : 0;
$over05HtAway  += ($htScored   + $htAgainst)    >= 1 ? 1 : 0;
$winBothHalvesAway += ($htScored > $htAgainst && $goalsScored > $goalsAgainst) ? 1 : 0;
```

Nota: `over15Home` cuenta partidos (locales) con 2+ goles totales. `over15Away` ya existía y contaba goles marcados ≥ 2 por el equipo visitante — su semántica es diferente. Se mantiene `over15Away` como está para no romper `Over25Criterion`.

Tests: `GoalsCounterUpdaterTest` — ampliar con los nuevos contadores.

---

## Paso 3 — Añadir constantes en `Bet`

```php
public const TYPE_OVER_1_5         = 'over_1_5';
public const TYPE_OVER_3_5         = 'over_3_5';
public const TYPE_UNDER_2_5        = 'under_2_5';
public const TYPE_AWAY_WIN         = 'away_win';
public const TYPE_DOUBLE_CHANCE    = 'double_chance';
public const TYPE_OVER_05_HT       = 'over_0_5_ht';
public const TYPE_WIN_BOTH_HALVES  = 'win_both_halves';
```

---

## Paso 4 — Criterios (uno por apuesta)

### 4a. `Over15Criterion` (`over_1_5`)

Criterio: 2+ goles totales en al menos el 70% de los últimos 8 partidos (mín. 5 jugados).

- Local: `over15Home / matchesPlayedHome >= 0.70` y `matchesPlayedHome >= 5`
- Visitante: `over15Away / matchesPlayedAway >= 0.70` y `matchesPlayedAway >= 5`

Liquidación: `goalsScored + goalsAgainst >= 2`

---

### 4b. `Over35Criterion` (`over_3_5`)

Criterio: 4+ goles totales en al menos el 50% de los últimos 8 partidos (mín. 5 jugados).

- Local: `over35Home / matchesPlayedHome >= 0.50` y `matchesPlayedHome >= 5`
- Visitante: `over35Away / matchesPlayedAway >= 0.50` y `matchesPlayedAway >= 5`

Liquidación: `goalsScored + goalsAgainst >= 4`

---

### 4c. `Under25Criterion` (`under_2_5`)

No requiere stats nuevas. Se deriva de las existentes:

`under25 = matchesPlayed - over25`

Criterio: menos de 3 goles en al menos el 60% de los últimos 8 partidos (mín. 5 jugados).

- Local: `(matchesPlayedHome - over25Home) / matchesPlayedHome >= 0.60`
- Visitante: derivar de `matchesPlayedAway` y `over25Away` — **pero `over25Away` no existe**. Se necesita añadir `over25Away` como counter nuevo.

> Revisión: añadir `over25Away` (int) al Paso 0 y al `GoalsCounterUpdater`.

Liquidación: `goalsScored + goalsAgainst < 3`

---

### 4d. `AwayWinCriterion` (`away_win`)

No requiere stats nuevas.

Criterio: el equipo juega fuera, y:
- Ganó 3+ de sus últimos 5 partidos fuera (`formLast5Away` con 3+ W)
- El rival perdió 3+ de sus últimos 5 partidos en casa (`nextFixtureOpponentFormSituational` con 3+ L)

Liquidación: `isHome === false` y `result === 'W'`

---

### 4e. `DoubleChanceCriterion` (`double_chance`)

Apuesta a que el local no pierde (W o D). No requiere stats nuevas.

Criterio: el equipo juega en casa, y:
- No perdió en 4+ de sus últimos 5 partidos en casa (`formLast5Home` con 4+ W o D)
- El rival no ganó en 4+ de sus últimos 5 fuera (`nextFixtureOpponentFormSituational` con 4+ D o L)

Liquidación: `isHome === true` y `result !== 'L'`

---

### 4f. `Over05HalfTimeCriterion` (`over_0_5_ht`)

Criterio: al menos 1 gol en la primera parte en el 70%+ de los últimos 8 partidos (mín. 5).

- Local: `over05HtHome / matchesPlayedHome >= 0.70`
- Visitante: `over05HtAway / matchesPlayedAway >= 0.70`

Liquidación: `halfTimeGoalsScored + halfTimeGoalsAgainst >= 1`

---

### 4g. `WinBothHalvesCriterion` (`win_both_halves`)

Criterio: el equipo ganó ambas partes en 40%+ de los últimos 8 partidos (mín. 5).

- Local: `winBothHalvesHome / matchesPlayedHome >= 0.40`
- Visitante: `winBothHalvesAway / matchesPlayedAway >= 0.40`

Liquidación: `halfTimeGoalsScored > halfTimeGoalsAgainst` y `goalsScored > goalsAgainst`

---

## Paso 5 — Liquidación en `BetSettlementService`

Ampliar el `match` en `evaluateOutcome()` con los nuevos tipos:

```php
Bet::TYPE_OVER_1_5        => ($match['goalsScored'] + $match['goalsAgainst']) >= 2,
Bet::TYPE_OVER_3_5        => ($match['goalsScored'] + $match['goalsAgainst']) >= 4,
Bet::TYPE_UNDER_2_5       => ($match['goalsScored'] + $match['goalsAgainst']) < 3,
Bet::TYPE_AWAY_WIN        => !$match['isHome'] && $match['result'] === 'W',
Bet::TYPE_DOUBLE_CHANCE   => $match['isHome'] && $match['result'] !== 'L',
Bet::TYPE_OVER_05_HT      => ($match['halfTimeGoalsScored'] + $match['halfTimeGoalsAgainst']) >= 1,
Bet::TYPE_WIN_BOTH_HALVES => $match['halfTimeGoalsScored'] > $match['halfTimeGoalsAgainst']
                             && $match['goalsScored'] > $match['goalsAgainst'],
```

---

## Paso 6 — Tests

**Unitarios nuevos:**
- `Over15CriterionTest`
- `Over35CriterionTest`
- `Under25CriterionTest`
- `AwayWinCriterionTest`
- `DoubleChanceCriterionTest`
- `Over05HalfTimeCriterionTest`
- `WinBothHalvesCriterionTest`
- `GoalsCounterUpdaterTest` — ampliar con los nuevos contadores
- `FootballDataClientTest` — añadir `halfTimeGoalsScored/Against` a los stubs

---

## Orden de ejecución ✅

1. ✅ Paso 0 — Columnas nuevas en `Team` + migración (incluye `over25Away`)
2. ✅ Paso 1 — `FootballDataProviderInterface` + `FootballDataClient` (halfTime)
3. ✅ Paso 2 — `GoalsCounterUpdater` (nuevos contadores)
4. ✅ Paso 3 — Constantes en `Bet`
5. ✅ Paso 4a-g — Criterios (uno a uno, con su test)
6. ✅ Paso 5 — Liquidación en `BetSettlementService`
7. ✅ Tests completos en verde → commit

---

## Notas

- Los umbrales propuestos son orientativos. Se pueden ajustar una vez que haya suficiente histórico.
- `over25Away` es una adición que también beneficia a `Under25Criterion`. Hay que revisar si afecta a `Over25Criterion` existente — no afecta, porque `Over25Criterion` actualmente usa `over15Away` (goles marcados por el equipo visitante), no goles totales.
- No hay cambios en la página de historial ni en los templates — los nuevos tipos aparecen automáticamente gracias al sistema de criterios extensible.
