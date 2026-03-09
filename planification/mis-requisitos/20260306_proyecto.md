# BetProject — Lo que quiero que haga

## La idea

Una página única que muestra, para un conjunto fijo de equipos de fútbol europeos, el próximo partido de liga y estadísticas útiles para decidir apuestas.

- URL: `/tomorrow/bets`
- Fuente de datos: **football-data.org** (con mi API key)

---

## Equipos que sigo

| Equipo | Liga |
|--------|------|
| Real Madrid | La Liga |
| FC Barcelona | La Liga |
| Bayern Munich | Bundesliga |
| Borussia Dortmund | Bundesliga |
| AS Roma | Serie A |
| Juventus | Serie A |

---

## Cómo debe verse la página

- Listado vertical de todos los equipos, ordenado por fecha de próximo partido
- Los equipos con partido mañana aparecen resaltados visualmente
- Cada fila es colapsable (acordeón); al hacer clic se despliega el detalle
- No son fichas grandes: es un listado limpio de filas

### Cabecera de cada fila (siempre visible)

- Nombre del partido en formato **local vs visitante** (el equipo local siempre va primero)
- Fecha y hora del partido
- Badges: TOMORROW (si aplica) + Home/Away

### Detalle desplegable

Dos columnas: **nuestro equipo** a la izquierda | **rival** a la derecha.

**Columna izquierda — nuestro equipo:**
- Racha de los últimos 8 partidos en liga (sin filtro casa/fuera)
- Racha de los últimos 5 partidos en su condición (en casa si juega en casa, fuera si juega fuera)
- Veces que marcó más goles del umbral en esa condición (X de N partidos)

**Columna derecha — rival:**
- Racha de los últimos 5 partidos del rival en su condición inversa
  - Si nuestro equipo juega en casa → racha del rival jugando fuera
  - Si nuestro equipo juega fuera → racha del rival jugando en casa
- Veces que el rival marcó más goles del umbral en su condición (solo cuando el partido es mañana)

### Umbrales de goles

| Nuestro equipo juega | Umbral para nuestro equipo | Umbral para el rival |
|----------------------|---------------------------|----------------------|
| En casa | +2.5 goles en casa | +1.5 goles fuera |
| Fuera | +1.5 goles fuera | +2.5 goles en casa |

### Nota sobre rachas

Si un equipo no ha jugado 5 partidos en esa condición (casa/fuera), se muestran los que haya. Los resultados se muestran del más reciente (izquierda) al más antiguo (derecha).

---

## Comportamiento de los datos

- Los datos se guardan en base de datos y se refresca llamando a la API solo cuando hace falta
- Si el equipo ya tiene partido programado y los datos están al día, no se llama a la API
- Si el partido ya ha pasado, se refresca automáticamente al cargar la página
