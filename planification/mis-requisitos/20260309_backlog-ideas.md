# Backlog de ideas — 2026-03-09

Ideas pendientes de planificar e implementar, sin orden de prioridad.

---

## Funcionalidad nueva

### Cron de sync automático
Ejecutar `app:teams:sync` de forma automática (noche o madrugada) sin intervención manual.
Opciones: cron del sistema, comando de Symfony Scheduler, o cron en Docker.

### BTTS — Ambos equipos marcan
Nuevo tipo de apuesta: los dos equipos anotan al menos un gol.
La API ya devuelve los goles de cada equipo — es un criterio derivado sin stats nuevas.
Criterio orientativo: en el X% de los últimos partidos del equipo en casa/fuera, ambos equipos marcaron.

### Criterios combinados — señal de alta confianza
Cuando un partido cumple 2 o más criterios simultáneamente, mostrar un indicador especial (ej. ⭐ o badge "STRONG") en la cabecera de la tarjeta.
Cuantos más criterios coincidan, mayor la confianza percibida.

### Clean sheet local
Nuevo tipo de apuesta: el equipo de casa no encaja ningún gol.
Stats necesarias: contador de partidos en casa sin encajar (ya se puede derivar de `goalsAgainst === 0` en partidos `isHome`).

---

## Análisis y calibración

### Rentabilidad por criterio
En la página de historial, ampliar la tabla por tipo de apuesta con:
- Nº total de apuestas
- % de acierto
- Tendencia (últimas 10 vs global)
Permite ajustar umbrales con datos reales en lugar de estimaciones iniciales.

### Calibración automática de umbrales
A partir de un mínimo de apuestas por criterio (ej. 20), sugerir automáticamente si el umbral debería subir o bajar según el % de acierto histórico.

### Tendencia reciente del equipo
Comparar la racha actual del equipo con la de hace 4 semanas.
Mostrar un indicador visual: mejorando 📈 / estable / empeorando 📉.

---

## UX / navegación

### Filtros en la página de tomorrow
Filtrar las tarjetas por:
- Liga (PD, PL, BL1, FL1, SA, DED)
- Tipo de apuesta activa
- Solo partidos de mañana

Con 23+ equipos la lista empieza a ser larga y los filtros ayudan a la consulta rápida.

### Vista de equipo individual
Página dedicada por equipo con:
- Stats completas actualizadas
- Historial de apuestas del equipo
- Evolución de la racha en el tiempo

### Ordenación configurable en tomorrow
Además de por fecha de próximo fixture, poder ordenar por:
- Liga
- Número de criterios cumplidos (los más "calientes" arriba)

---

## Operativo

### Indicador de última sync en cada tarjeta
Mostrar en la ficha de cada equipo cuándo fue la última sincronización con la API.
Útil para saber si los datos son frescos antes de apostar.

### Alerta de datos desactualizados
Si un equipo lleva más de N días sin sync, destacarlo visualmente (borde rojo, badge "STALE").
Evita tomar decisiones con datos viejos.
