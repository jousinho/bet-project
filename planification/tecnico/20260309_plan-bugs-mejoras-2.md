# Plan técnico — Bugs y mejoras (sesión 2) — 2026-03-09

---

## B1 — Equipos duplicados (Bayern Munich / FC Bayern München, Juventus / Juventus FC)

### Causa
El seed antiguo creó equipos con nombres abreviados. El nuevo seed creó entradas correctas pero al hacer skip de equipos existentes por nombre, los duplicados quedaron en BD con el mismo `external_id` en `team_external_id`.

### Solución
Comando de consola (o script directo) que:
1. Detecta pares de duplicados buscando `TeamExternalId` con el mismo `provider` + `externalId` apuntando a `Team` distintos
2. Decide cuál conservar (el de nombre más completo / el creado más tarde)
3. Migra bets y `team_bet_stats` del equipo viejo al nuevo
4. Elimina el `TeamExternalId` del duplicado y luego el `Team` duplicado

Como los equipos duplicados son recientes y no tienen apuestas reales, en la práctica bastará con eliminar directamente los equipos viejos si no tienen bets asociados.

### Alternativa simple (sin comando)
Ejecutar en la BD:
```sql
-- Ver duplicados
SELECT t.id, t.name, te.external_id
FROM team t JOIN team_external_id te ON te.team_id = t.id
WHERE te.external_id IN ('5', '109')  -- Bayern, Juventus
ORDER BY te.external_id, t.id;

-- Eliminar el viejo (el de nombre corto, id menor)
DELETE FROM team_external_id WHERE team_id = <id_viejo>;
DELETE FROM team WHERE id = <id_viejo>;
```

---

## B2 — Apuestas duplicadas en partidos cruzados

### Causa
`BetEvaluatorService::evaluateAll()` itera todos los equipos de forma independiente. Si Madrid (local) y Barça (visitante) se enfrentan y ambos cumplen criterios, se crean dos apuestas `over_2_5` para el mismo partido.

### Solución
Antes de crear una apuesta, comprobar si el equipo rival también es un equipo seguido (`nextFixtureOpponentId` está en la lista de equipos tracked). Si es así, solo crear apuestas desde la perspectiva del equipo **local** — el visitante se salta.

Cambios:
- En `BetEvaluatorService::evaluateAll()`: recibir o construir un `Set` de `nextFixtureOpponentId` de todos los equipos tracked
- Añadir condición: si `!team->nextFixtureIsHome()` y `opponentId` está en tracked teams → `continue`

Esto es simple y no requiere cambios en el modelo de datos ni en la BD.

---

## B3 — Partido cruzado aparece dos veces en "tomorrow"

### Causa
`TomorrowBetsService::getData()` genera un DTO por equipo. Si dos equipos seguidos juegan entre sí, se generan dos DTOs para el mismo partido.

### Solución
Al construir la lista de DTOs, detectar partidos cruzados y fusionarlos:
- Identificar pares donde `team->nextFixtureDate()` y `team->nextFixtureOpponentId()` coinciden inversamente
- Generar un solo DTO para el partido, con los `activeBetTypes` de ambos equipos combinados

El DTO necesita un campo extra: `trackedTeamNames: string[]` (en partidos cruzados tendrá los dos nombres) para el resaltado (M2).

Orden interno: el equipo local siempre va primero (izquierda), el visitante a la derecha — sirve de base para M4.

---

## M1 — Nuevos criterios en página de historial

### Situación actual
El historial ya muestra todos los bet types con `typeName|replace({'_': ' '})|upper` — funcionará automáticamente en cuanto haya apuestas de los nuevos tipos liquidadas.

### Pendiente
- Revisar si el filtro por categoría o el desglose por tipo necesita algún ajuste visual
- No hay cambio de código necesario; verificar en cuanto haya datos reales

---

## M2 — Resaltar equipo seguido en cabecera

### Solución
En el template `tomorrow.html.twig`, envolver el nombre del equipo seguido en un `<span class="tracked-team">`. Si el partido es cruzado, ambos nombres llevan la clase.

El DTO pasará `trackedTeams: string[]` con los nombres de los equipos seguidos del partido (1 o 2).

CSS:
```css
.tracked-team { font-weight: 700; text-decoration: underline dotted; }
```

---

## M3 — Stats over/under del rival en la ficha

### Situación actual
El rival solo muestra su racha situacional y un único contador over (`opponentOverCount / opponentMatchesPlayed`). Este contador se elige según si nuestro equipo es local o visitante.

### Solución
Ampliar `TeamBetDTO` con stats completas del rival (solo disponibles si el rival también es equipo seguido):
```php
public ?int $opponentOver15 = null,
public ?int $opponentOver25 = null,
public ?int $opponentOver35 = null,
public ?int $opponentOver05Ht = null,
public ?int $opponentWinBothHalves = null,
public ?int $opponentMatchesPlayed = null,  // ya existe, renombrar si hace falta
```

En `TomorrowBetsService::buildDto()`, si el rival es un equipo seguido (ya se carga en `$opponentExtId`), rellenar todos estos campos.

En el template, mostrar una tabla compacta de stats del rival cuando estén disponibles.

---

## M4 — Local siempre a la izquierda, visitante a la derecha

### Situación actual
- La cabecera (`match-title`) ya pone local a la izquierda ✅
- El cuerpo de la ficha (`card-body`) pone siempre "nuestro equipo" a la izquierda — si somos visitantes, quedamos a la derecha en la cabecera pero a la izquierda en el cuerpo ❌

### Solución
En `TeamBetDTO` ya existe `isHome`. En el template, si `bet.isHome === false`, intercambiar las columnas: la izquierda muestra los datos del rival y la derecha los del equipo seguido.

Alternativa más limpia: en el DTO normalizar siempre con perspectiva del partido (no del equipo seguido):
- `homeTeamName`, `homeFormSituational`, `homeStats...`
- `awayTeamName`, `awayFormSituational`, `awayStats...`
- `trackedTeams: string[]` para saber cuáles resaltar

Esto requiere refactorizar el DTO pero el template queda muy simple y consistente. **Esta es la opción preferida**, especialmente porque B3 (fusión de partidos cruzados) también lo requiere.

---

## Orden de ejecución recomendado

1. **B1** — Limpiar duplicados en BD (Bayern, Juventus) — directo en BD, sin código
2. **B2+B3** — Refactorizar `TeamBetDTO` con perspectiva de partido + fusión de partidos cruzados en `TomorrowBetsService` + skip de apuestas visitante en `BetEvaluatorService`
3. **M4** — Template: columnas fijas local/visitante (sale gratis del paso anterior si refactorizamos el DTO)
4. **M2** — Template: resaltado de equipos seguidos (clase CSS, sale del mismo DTO)
5. **M3** — Stats completas del rival en ficha (ampliar DTO y template)
6. **M1** — Verificar visualmente cuando haya datos reales de los nuevos tipos

### Dependencias
- B3 y M4 comparten la misma refactorización del DTO → hacerlos juntos
- M2 depende de B3 (saber qué equipos son seguidos en el partido)
- M3 es independiente pero se beneficia de la refactorización del DTO
