# BetProject

Herramienta personal para seguimiento y análisis de apuestas deportivas basada en estadísticas de equipos de fútbol europeo.

## ¿Qué hace?

- Sincroniza estadísticas de equipos desde la API de [football-data.org](https://www.football-data.org/)
- Evalúa criterios estadísticos para sugerir apuestas en partidos del día siguiente
- Registra el historial de apuestas y las liquida automáticamente con el resultado real
- Muestra un panel con rachas de forma, goles y apuestas activas por equipo

## Equipos seguidos (23)

| Liga | Equipos |
|---|---|
| La Liga (PD) | Real Madrid CF, FC Barcelona, Club Atlético de Madrid |
| Premier League (PL) | Arsenal FC, Chelsea FC, Liverpool FC, Manchester City FC, Manchester United FC |
| Bundesliga (BL1) | Bayer 04 Leverkusen, Borussia Dortmund, FC Bayern München |
| Ligue 1 (FL1) | Olympique Lyonnais, Paris Saint-Germain FC, AS Monaco FC |
| Serie A (SA) | AC Milan, AS Roma, Atalanta BC, FC Internazionale Milano, Juventus FC, SSC Napoli, Como 1907 |
| Eredivisie (DED) | PSV, AFC Ajax |

## Tipos de apuesta

| Tipo | Descripción | Criterio |
|---|---|---|
| `over_2_5` | Más de 2.5 goles | ≥75% local / ≥62.5% visitante (mín 5) |
| `home_win` | Victoria local | 4+ W en últimos 5 en casa + rival 3+ L fuera |
| `over_1_5` | Más de 1.5 goles | ≥70% local o visitante (mín 5) |
| `over_3_5` | Más de 3.5 goles | ≥50% local o visitante (mín 5) |
| `under_2_5` | Menos de 2.5 goles | ≥60% partidos sin 3+ goles (mín 5) |
| `away_win` | Victoria visitante | 3+ W fuera + rival 3+ L en casa |
| `double_chance` | Doble oportunidad (1X) | 4+ W/D en casa + rival 4+ D/L fuera |
| `over_05_ht` | Más de 0.5 goles en 1ª parte | ≥70% con gol en primera parte (mín 5) |
| `win_both_halves` | Ganar ambas partes | ≥40% ganando las dos partes (mín 5) |

## Stack técnico

- **PHP 8.4** + **Symfony 8.0**
- **Doctrine ORM 3.x** + **PostgreSQL 16**
- Arquitectura **DDD** con bounded contexts `Tracking` y `Betting`
- Tests con **PHPUnit** (113 tests, 216 assertions)

## Puesta en marcha

```bash
# Levantar servicios
docker compose up -d

# Instalar dependencias
docker compose exec php-cli composer install

# Crear la base de datos y ejecutar migraciones
docker compose exec -T php-cli php bin/console doctrine:migrations:migrate

# Cargar equipos
docker compose exec -T php-cli php bin/console app:teams:seed

# Sincronizar estadísticas (tarda ~3 min por el rate limit de la API)
docker compose exec -T php-cli php bin/console app:teams:sync
```

La app queda disponible en `http://localhost:8080`.

## Comandos útiles

```bash
# Sincronizar todos los equipos con la API
docker compose exec -T php-cli php bin/console app:teams:sync

# Ejecutar tests
docker compose exec -T php-cli php bin/phpunit

# Limpiar caché
docker compose exec -T php-cli php bin/console cache:clear
```

## Arquitectura

```
src/
  Domain/
    Tracking/    → Team, TeamExternalId, repositorios, FormCalculator, GoalsCounterUpdater
    Betting/     → Bet, TeamBetStats, criterios (BetCriterionInterface), TeamSnapshot VO
  Application/
    Tracking/    → TeamSyncService
    Betting/     → TomorrowBetsService, BetEvaluatorService, BetSettlementService
  Infrastructure/
    Tracking/    → FootballDataClient, Doctrine repos, SeedTeamsCommand, SyncTeamsCommand
    Betting/     → Doctrine repos, controllers, BetsController, BetsHistoryController
```
