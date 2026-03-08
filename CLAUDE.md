# BetProject — Instrucciones para Claude

## Workflow obligatorio

Antes de implementar cualquier mejora o corrección:
1. Documentarla en `planification/mis-requisitos/mejoras.md` (petición en lenguaje simple)
2. Documentar el plan técnico en `planification/tecnico/planes.md` (pasos detallados)
3. Esperar confirmación antes de ejecutar

Después de implementar:
- Actualizar el estado en `planification/mis-requisitos/mejoras.md` (marcar como ✅)

---

## Convenciones de código

### Entidades Doctrine
- Constructor **privado** + factory method estático `create(...): self`
- Getters **sin prefijo `get`**: `name()`, `league()`, `nextFixtureDate()` — nunca `getName()`
- Los setters sí mantienen el prefijo `set`

### General
- `declare(strict_types=1)` en todos los ficheros PHP sin excepción
- No añadir comentarios salvo que la lógica no sea evidente por sí sola
- No añadir docblocks ni type annotations en código que no se ha modificado

### Servicios
- Los métodos públicos principales deben ser legibles: extraer bloques de lógica a métodos privados con nombres descriptivos
- Evitar métodos privados de un solo uso trivial (no abstraer por abstraer)

---

## Arquitectura DDD — reglas de capas

```
Domain/     → entidades, interfaces de repositorio, servicios de dominio puros
Application → servicios de aplicación (orquestan dominio + infraestructura), DTOs
Infrastructure → implementaciones de repositorio, cliente HTTP, controllers, comandos
```

- Las interfaces viven en `Domain/`, las implementaciones en `Infrastructure/`
- Los servicios sin dependencias de infraestructura van en `Domain/Betting/Service/`
- Controllers registrados explícitamente en `services.yaml`
- Migraciones en `src/Infrastructure/Shared/Persistence/Doctrine/Migrations/`

---

## Testing

### Nomenclatura obligatoria
```
test_{acción}_{contexto}__should_{resultado_esperado}
```
Ejemplos:
- `test_syncing_team__when_next_fixture_is_future__should_not_call_api`
- `test_calculating_form__with_5_wins__should_return_WWWWW`

### Reglas
- Tests unitarios: mockear libremente, probar una sola clase
- Tests de integración: **solo se mockea la API externa** (football-data.org), la BD de tests es real
- No se considera implementado un cambio hasta que los tests pasan
- Ejecutar tests con: `docker compose exec -T php-cli php bin/phpunit`

---

## Infraestructura

- PHP 8.4, Symfony 8.0, Doctrine ORM 3.x, PostgreSQL 16
- Contenedor para comandos: `docker compose exec -T php-cli`
- BD principal: puerto 5432 | BD de tests: puerto 5433
- La app corre en `http://localhost:8080`
