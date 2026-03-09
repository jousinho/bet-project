# Plan técnico — Backlog de ideas — 2026-03-09

Orden de implementación recomendado: de menor a mayor complejidad, priorizando lo que aporta más valor inmediato.

---

## Bloque 1 — Operativo (fácil, alto valor)

### P1 — Indicador de última sync + alerta de datos desactualizados

**Complejidad:** baja
**Archivos:** `TeamBetDTO`, `TomorrowBetsService`, `tomorrow.html.twig`

`Team` ya tiene `lastSyncedAt`. Solo hay que pasarlo al DTO y mostrarlo en la ficha.

Cambios:
- Añadir `lastSyncedAt: ?\DateTimeImmutable` al `TeamBetDTO`
- En `TomorrowBetsService::buildMatchDto()`: rellenar con `$team->lastSyncedAt()`
- En template: mostrar fecha de sync en el pie de la ficha
- Si `lastSyncedAt` es null o tiene más de 3 días → añadir clase `stale` + badge "STALE" rojo en cabecera

---

## Bloque 2 — UX (medio, alto valor)

### P2 — Filtros en la página de tomorrow

**Complejidad:** media
**Archivos:** `tomorrow.html.twig` (solo JS vanilla, sin backend)

Los filtros se implementan en el cliente con JS puro — no requiere cambios en PHP.

Filtros:
- Por liga: botones toggle (ALL / PD / PL / BL1 / FL1 / SA / DED)
- Solo con apuestas activas: ocultar tarjetas sin pills
- Solo mañana: ocultar tarjetas sin `highlightedTomorrow`

Implementación:
- Añadir `data-league`, `data-has-bets`, `data-tomorrow` a cada `<details>`
- JS: al hacer click en filtro, ocultar/mostrar cards con `display: none`
- Guardar estado de filtros en `localStorage`

No necesita rama nueva si va junto a otra mejora, pero es autónomo.

---

### P3 — Criterios combinados — señal de alta confianza

**Complejidad:** baja
**Archivos:** `tomorrow.html.twig`

Cambios en template:
- Contar `bet.activeBetTypes|length`
- Si ≥ 2: añadir clase `multi-signal` a la card + badge "🔥 STRONG" en cabecera
- Si ≥ 3: badge "⚡ HIGH CONF"
- CSS: borde destacado en gold o naranja

No requiere cambios en PHP ni en el DTO.

---

### P4 — Ordenación configurable en tomorrow

**Complejidad:** baja
**Archivos:** `tomorrow.html.twig` (JS), `TomorrowBetsService`

Opciones de ordenación:
1. Por fecha de partido (actual, default)
2. Por liga (agrupar o reordenar)
3. Por número de criterios cumplidos (más "calientes" arriba)

Implementación:
- Ordenar por criterios en JS (reordenar DOM con `sort` sobre `data-bet-count`)
- La ordenación por fecha ya viene del backend
- Añadir `data-bet-count` y `data-league` a cada card

---

## Bloque 3 — Nuevos tipos de apuesta

### P5 — BTTS — Ambos equipos marcan

**Complejidad:** media
**Archivos:** `Team`, migración, `GoalsCounterUpdater`, `TeamSnapshot`, `BttsHomeCriterion` / `BttsAwayCriterion`, `BetSettlementService`, `tomorrow.html.twig`, `history.html.twig`

Stats nuevas en `Team`:
```php
private int $bttsHome = 0;   // partidos en casa donde ambos marcaron
private int $bttsAway = 0;   // partidos fuera donde ambos marcaron
```

`GoalsCounterUpdater`: añadir `$btts = $match['goalsScored'] > 0 && $match['goalsAgainst'] > 0`

Criterio:
- Local: `bttsHome / matchesPlayedHome >= 0.65`, mín 5 partidos
- Visitante: `bttsAway / matchesPlayedAway >= 0.65`, mín 5 partidos

Liquidación: `goalsScored > 0 && goalsAgainst > 0`

Migración Doctrine con las 2 columnas nuevas.

---

### P6 — Clean sheet local

**Complejidad:** baja
**Archivos:** `Team`, migración, `GoalsCounterUpdater`, `TeamSnapshot`, `CleanSheetHomeCriterion`, `BetSettlementService`

Stat nueva:
```php
private int $cleanSheetHome = 0;  // partidos en casa sin encajar
```

Criterio: `cleanSheetHome / matchesPlayedHome >= 0.40`, mín 5 partidos, solo jugando en casa

Liquidación: `isHome && goalsAgainst === 0`

---

## Bloque 4 — Análisis

### P7 — Rentabilidad por criterio con tendencia

**Complejidad:** media
**Archivos:** `BetsHistoryController`, `history.html.twig`

Actualmente `byType` en el template calcula total/won/lost con Twig. Para la tendencia (últimas 10 vs global) hay que mover esa lógica al controller.

Cambios:
- En `BetsHistoryController`: calcular `$criteriaStats` con `total`, `won`, `lost`, `last10Won`, `last10Total`
- En template: columna extra "Tendencia últimas 10" con color verde/rojo
- Comparar `last10WinRate` vs `globalWinRate` para mostrar flecha ↑ ↓ =

---

### P8 — Calibración automática de umbrales

**Complejidad:** alta
**Archivos:** nuevo `ThresholdAdvisorService`, vista dedicada o sección en historial

Con mínimo 20 apuestas por criterio:
- Si `winRate < 45%` → sugerir subir el umbral
- Si `winRate > 70%` → sugerir bajar el umbral (potencial de más volumen)
- Mostrar como sugerencias informativas, no cambios automáticos

---

### P9 — Tendencia reciente del equipo

**Complejidad:** media
**Archivos:** `TeamBetDTO`, `TomorrowBetsService`, `tomorrow.html.twig`

`Team` tiene `formLast8` (últimos 8 partidos globales). Para comparar con hace 4 semanas necesitaría guardar un snapshot histórico — esto implica una tabla nueva o guardar `formSnapshot` periódicamente.

Alternativa más simple (sin tabla nueva): comparar los primeros 4 resultados de `formLast8` (más recientes) vs los últimos 4 (más antiguos). Da una aproximación de tendencia sin infraestructura adicional.

Fórmula: `recentWins = W en [0..3]` vs `olderWins = W en [4..7]`
- `recentWins > olderWins` → mejorando
- `recentWins < olderWins` → empeorando
- igual → estable

---

### P10 — Vista de equipo individual

**Complejidad:** alta
**Archivos:** nuevo `TeamController`, nueva ruta `/team/{id}`, nuevo template `team/show.html.twig`, nuevo DTO `TeamDetailDTO`

Contenido de la página:
- Stats completas del equipo (todos los contadores)
- Racha visual (form badges)
- Historial de apuestas filtrado por equipo
- Próximo partido con rival y stats del rival

---

## Cron de sync automático

**Complejidad:** baja (opción cron del sistema)

La opción más simple es añadir una entrada en el `crontab` del contenedor o en el `docker-compose.yml`:

```
# /etc/cron.d/bet-sync
0 3 * * * www-data php /var/www/html/bin/console app:teams:sync >> /var/log/bet-sync.log 2>&1
```

Alternativa con Symfony Scheduler (más limpia pero requiere worker process):
- Instalar `symfony/scheduler`
- Registrar `SyncTeamsSchedule` con recurrencia `new CronExpression('0 3 * * *')`

---

## Orden de ejecución recomendado

| # | Mejora | Complejidad | Valor |
|---|---|---|---|
| P1 | Indicador última sync + alerta STALE | Baja | Alto |
| P2 | Filtros en tomorrow | Media | Alto |
| P3 | Señal alta confianza (criterios combinados) | Baja | Medio |
| P4 | Ordenación configurable | Baja | Medio |
| P5 | BTTS | Media | Alto |
| P6 | Clean sheet local | Baja | Medio |
| P7 | Rentabilidad por criterio con tendencia | Media | Alto |
| P8 | Calibración umbrales | Alta | Alto (futuro) |
| P9 | Tendencia reciente equipo | Media | Medio |
| P10 | Vista equipo individual | Alta | Alto (futuro) |
| — | Cron sync automático | Baja | Alto |
