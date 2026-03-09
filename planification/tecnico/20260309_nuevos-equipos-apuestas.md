# Plan técnico — Nuevos equipos y tipos de apuesta — 2026-03-09

## Parte 1 — Nuevos equipos

### Qué se hace

Ampliar `SeedTeamsCommand` con los 23 equipos de 6 ligas europeas.

### Cambios en `SeedTeamsCommand`

Añadir las entradas al array `TEAMS`:

```php
// Premier League
['name' => 'Arsenal FC',              'league' => 'PL',  'external_id' => '57'],
['name' => 'Chelsea FC',              'league' => 'PL',  'external_id' => '61'],
['name' => 'Liverpool FC',            'league' => 'PL',  'external_id' => '64'],
['name' => 'Manchester City FC',      'league' => 'PL',  'external_id' => '65'],
['name' => 'Manchester United FC',    'league' => 'PL',  'external_id' => '66'],

// Bundesliga
['name' => 'Bayer 04 Leverkusen',     'league' => 'BL1', 'external_id' => '3'],
['name' => 'Borussia Dortmund',       'league' => 'BL1', 'external_id' => '4'],
['name' => 'FC Bayern München',       'league' => 'BL1', 'external_id' => '5'],

// Ligue 1
['name' => 'Olympique Lyonnais',      'league' => 'FL1', 'external_id' => '523'],
['name' => 'Paris Saint-Germain FC',  'league' => 'FL1', 'external_id' => '524'],
['name' => 'AS Monaco FC',            'league' => 'FL1', 'external_id' => '548'],

// Serie A
['name' => 'AC Milan',                'league' => 'SA',  'external_id' => '98'],
['name' => 'AS Roma',                 'league' => 'SA',  'external_id' => '100'],
['name' => 'Atalanta BC',             'league' => 'SA',  'external_id' => '102'],
['name' => 'FC Internazionale Milano','league' => 'SA',  'external_id' => '108'],
['name' => 'Juventus FC',             'league' => 'SA',  'external_id' => '109'],
['name' => 'SSC Napoli',              'league' => 'SA',  'external_id' => '113'],
['name' => 'Como 1907',               'league' => 'SA',  'external_id' => '7397'],

// La Liga
['name' => 'FC Barcelona',            'league' => 'PD',  'external_id' => '81'],
['name' => 'Real Madrid CF',          'league' => 'PD',  'external_id' => '86'],
['name' => 'Club Atlético de Madrid', 'league' => 'PD',  'external_id' => '78'],

// Eredivisie
['name' => 'PSV',                     'league' => 'DED', 'external_id' => '674'],
['name' => 'AFC Ajax',                'league' => 'DED', 'external_id' => '678'],
```

### Ejecución del seed

Tras el commit, ejecutar:
```
docker compose exec -T php-cli php bin/console app:teams:seed
```

No hay migración de BD — la estructura de tablas no cambia.

---

## Parte 2 — Nuevos tipos de apuesta

**Pendiente de definir con el usuario.** Los criterios concretos y umbrales se documentarán aquí cuando se decidan.

Tipos candidatos (por confirmar):
- BTTS (ambos equipos marcan)
- Victoria visitante
- Over 1.5 goles
- Over 3.5 goles

Para cada tipo nuevo se necesita:
1. Nombre y constante (`Bet::TYPE_*`)
2. Criterio de evaluación con umbrales numéricos
3. Lógica de liquidación (cómo determinar si se ganó o perdió)
4. ¿Requiere estadísticas nuevas en `Team`? → posible migración de BD y cambios en `TeamSyncService`

---

## Orden de ejecución (Parte 1)

1. Actualizar `SeedTeamsCommand` con los 23 equipos
2. Ejecutar el seed en producción/dev
3. Tests: el seed no tiene test unitario, verificar manualmente que los equipos aparecen en la página

## Orden de ejecución (Parte 2)

Por definir una vez acordados los criterios.
