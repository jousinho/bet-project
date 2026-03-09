# Bugs y mejoras — 2026-03-09 (sesión 2)

## Bugs

### B1 — Equipos duplicados por nombre distinto
- "Bayern Munich" y "FC Bayern München" son el mismo equipo pero con dos entradas en BD.
- "Juventus" y "Juventus FC" igual.
- Hay que unificar: eliminar el duplicado antiguo y quedarse con el nombre correcto.

### B2 — Apuestas duplicadas en partidos cruzados
- Si juegan Barça vs Madrid, se generan apuestas para los dos equipos por separado.
- Las apuestas deberían crearse una sola vez por partido, no una por cada equipo seguido.
- Habría que detectar cuando dos equipos seguidos se enfrentan y evitar el duplicado.

### B3 — Partidos cruzados aparecen dos veces en "tomorrow"
- Si dos equipos seguidos juegan entre sí, el partido aparece dos veces en la página.
- Debería mostrarse una sola tarjeta para ese partido.

## Mejoras

### M1 — Nuevos criterios en página de historial
- Los 7 nuevos tipos de apuesta (`over_1_5`, `over_3_5`, `under_2_5`, `away_win`, `double_chance`, `over_05_ht`, `win_both_halves`) no aparecen diferenciados en la página de historial.
- Revisar si necesitan algún tratamiento visual específico o simplemente ya se muestran con el texto automático.

### M2 — Resaltar el equipo seguido en las tarjetas
- En la página de tomorrow, resaltar visualmente el nombre del equipo que seguimos en la cabecera de cada tarjeta.
- Si los dos equipos del partido son seguidos (partido cruzado), resaltar los dos.

### M3 — Mostrar stats over/under de los dos equipos en la ficha
- Actualmente solo se muestran las stats del equipo seguido.
- Añadir también los contadores over/under del rival en su columna de la ficha.

### M4 — Local siempre a la izquierda, visitante a la derecha
- La columna izquierda debe mostrar siempre al equipo local y la derecha al visitante, independientemente de cuál sea el equipo seguido.
- Actualmente el equipo seguido está siempre a la izquierda.
