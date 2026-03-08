# Nuevos requerimientos — 2026-03-08

## Gestor de apuestas

### La idea

Un sistema que, dado un partido próximo, evalúa automáticamente si cumple ciertos criterios y lo registra como apuesta. Después de que el partido se dispute, comprueba el resultado y marca la apuesta como ganada o perdida.

El objetivo no es apostar dinero real, sino ver si los criterios son viables y si emergen patrones ganadores.

### Tipos de apuesta

Se pueden apostar varias cosas distintas sobre el mismo partido. Ejemplos:
- Over 2.5 goles
- Victoria local
- Victoria visitante
- Ambos equipos marcan (BTTS)
- ... (se irán añadiendo)

Cada tipo de apuesta es independiente: un partido puede generar varias apuestas si cumple varios criterios.

### Criterios (se definen después)

Los criterios determinan CUÁNDO se hace una apuesta. Siempre están ligados a un tipo de apuesta concreto.

Ejemplo de cómo funcionará: "si en los últimos 10 partidos del equipo, 7 o más acabaron con over 2.5 → apostar a over 2.5 en su próximo partido".

Los criterios específicos se definirán en una sesión posterior. El sistema debe estar diseñado para que añadir nuevos criterios sea sencillo.

### Flujo general

1. Se carga la página de mañana → el sistema evalúa si el partido de cada equipo cumple algún criterio → si cumple, se registra la apuesta como **pendiente**
2. Cuando el partido ya se ha disputado → el sistema llama a la API para obtener el resultado → marca la apuesta como **ganada** o **perdida**

Ambos pasos ocurren en la carga de página (no hay procesos en background de momento).

### Dónde se ve

- **Página actual** (`/tomorrow/bets`): pequeño indicador visual en cada partido que diga si se apuesta o no (y a qué)
- **Página nueva** (por definir la URL): historial completo de apuestas con resultados, para analizar la viabilidad de los criterios

### Restricción: sin duplicados

Si ya existe una apuesta para el mismo equipo + partido + tipo de apuesta, no se crea otra aunque se recargue la página.

### Liquidación de apuestas

Para saber si una apuesta ganó o perdió, se usa el `getFinishedMatches` que ya existe. No hace falta un endpoint nuevo.

### Estadísticas históricas por equipo

Quiero guardar, por equipo, por tipo de apuesta y por temporada:
- Cuántas veces se ha apostado a ese criterio
- Cuántas se han ganado
- Cuántas se han perdido

Ejemplo: "Bayern Munich, ov2.5, temporada 2025/26 → 8 apostadas, 6 ganadas, 2 perdidas"

Esto permite ver qué criterios funcionan bien para qué equipos y en qué temporadas.

### Criterios concretos (con umbrales)

**Over 2.5 goles** (`over_2_5`):
- El equipo local marcó over 2.5 en 6 o más de sus últimos 8 partidos en casa
- Y el rival marcó over 1.5 en 5 o más de sus últimos 8 partidos fuera

**Victoria local** (`home_win`):
- El equipo local ganó 4 o más de sus últimos 5 partidos en casa
- Y el rival perdió 3 o más de sus últimos 5 partidos fuera

Los criterios deben mostrarse visualmente en la página actual, en cada partido que los cumpla.

### Lo que debe mostrar la página de historial

- Listado de todas las apuestas registradas
- Por cada apuesta: equipo, rival, fecha del partido, tipo de apuesta, resultado (ganada / perdida / pendiente)
- Resumen global: ganadas, perdidas, % de acierto
- Desglosado por tipo de apuesta (ov2.5 por separado de victoria local, etc.)
- Desglosado por equipo y temporada (usando las estadísticas guardadas)
