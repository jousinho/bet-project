# Bugs y mejoras pendientes

Registro de bugs detectados y mejoras menores propuestas. Cuando se implementan se marcan con ✅ y se mueven al historial de mejoras correspondiente.

---

## Bugs

### [BUG-001] OpCache cachea versión antigua de TomorrowBetsService — 2026-03-08
**Síntoma:** Error `Invalid service "TomorrowBetsService": method "__construct()" has no argument named "$criteria"` al cargar la página tras añadir el parámetro `$criteria` al constructor.
**Causa:** PHP OpCache en el contenedor tenía en memoria la versión anterior del fichero.
**Solución:** `docker compose restart php` + `bin/console cache:clear`.
**Estado:** ✅ Resuelto

---

## Mejoras pendientes

### [MEJ-001] Link desde Tomorrow's Bets a History — 2026-03-08
Añadir un enlace en la página `/` (tomorrow bets) que lleve a `/bets/history`.
**Estado:** ✅ Resuelto

### [MEJ-002] Sección de criterios activos en History — 2026-03-08
Mostrar en la página de history un resumen de los criterios de apuesta que se están evaluando (Over 2.5, Home Win) con su descripción y umbrales.
**Estado:** ✅ Resuelto

### [MEJ-003] Ranking de equipos con más apuestas ganadas — 2026-03-08
Mostrar en la página de history un listado ordenado de equipos por número de apuestas ganadas.
**Estado:** ✅ Resuelto
