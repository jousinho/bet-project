# Nuevos requerimientos — 2026-03-09

## Más equipos y más tipos de apuesta

### Más equipos

Añadir los siguientes equipos al seed, ampliando a 6 ligas europeas:

| Equipo | Liga (código) | ID API |
|---|---|---|
| Arsenal FC | Premier League (PL) | 57 |
| Chelsea FC | Premier League (PL) | 61 |
| Liverpool FC | Premier League (PL) | 64 |
| Manchester City FC | Premier League (PL) | 65 |
| Manchester United FC | Premier League (PL) | 66 |
| Bayer 04 Leverkusen | Bundesliga (BL1) | 3 |
| Borussia Dortmund | Bundesliga (BL1) | 4 |
| FC Bayern München | Bundesliga (BL1) | 5 |
| Olympique Lyonnais | Ligue 1 (FL1) | 523 |
| Paris Saint-Germain FC | Ligue 1 (FL1) | 524 |
| AS Monaco FC | Ligue 1 (FL1) | 548 |
| AC Milan | Serie A (SA) | 98 |
| AS Roma | Serie A (SA) | 100 |
| Atalanta BC | Serie A (SA) | 102 |
| FC Internazionale Milano | Serie A (SA) | 108 |
| Juventus FC | Serie A (SA) | 109 |
| SSC Napoli | Serie A (SA) | 113 |
| Como 1907 | Serie A (SA) | 7397 |
| FC Barcelona | La Liga (PD) | 81 |
| Real Madrid CF | La Liga (PD) | 86 |
| Club Atlético de Madrid | La Liga (PD) | 78 |
| PSV | Eredivisie (DED) | 674 |
| AFC Ajax | Eredivisie (DED) | 678 |

### Más tipos de apuesta ✅

Se añadieron 7 nuevos tipos de apuesta con sus criterios, tests y liquidación:

| Tipo | Constante | Criterio |
|---|---|---|
| Over 1.5 goles | `over_1_5` | 70%+ partidos con 2+ goles totales (mín 5) |
| Over 3.5 goles | `over_3_5` | 50%+ partidos con 4+ goles totales (mín 5) |
| Under 2.5 goles | `under_2_5` | 60%+ partidos con menos de 3 goles totales (mín 5) |
| Victoria visitante | `away_win` | 3+ victorias fuera en últimos 5 + rival 3+ derrotas en casa |
| Doble oportunidad (1X) | `double_chance` | 4+ W/D en casa + rival 4+ D/L fuera |
| Over 0.5 1ª parte | `over_05_ht` | 70%+ partidos con gol en primera parte (mín 5) |
| Ganar ambas partes | `win_both_halves` | 40%+ partidos ganando las dos partes (mín 5) |

Los nuevos tipos aparecen automáticamente en la página de tomorrow con sus pills de colores.

### Comando de sync ✅

Se añadió `app:teams:sync` para sincronizar todos los equipos respetando el rate limit de la API (7 segundos entre equipos).

