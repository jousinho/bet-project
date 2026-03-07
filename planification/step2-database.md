# Step 2 - Database & Entities

## Entidades Doctrine

### Team

```php
// src/Entity/Team.php

#[ORM\Entity]
class Team
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 50)]
    private string $league; // ej: "PD", "BL1", "SA"

    // Rachas
    #[ORM\Column(length: 8, nullable: true)]
    private ?string $formLast8 = null; // ej: "WDLWWDLW"

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $formLast5Home = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $formLast5Away = null;

    // Contadores goles casa
    #[ORM\Column(default: 0)]
    private int $over25Home = 0; // veces que marcó 3+ en casa

    #[ORM\Column(default: 0)]
    private int $matchesPlayedHome = 0;

    // Contadores goles fuera
    #[ORM\Column(default: 0)]
    private int $over15Away = 0; // veces que marcó 2+ fuera

    #[ORM\Column(default: 0)]
    private int $matchesPlayedAway = 0;

    // Próximo partido
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextFixtureDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $nextFixtureOpponentId = null; // FK interna a Team

    #[ORM\Column(nullable: true)]
    private ?bool $nextFixtureIsHome = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TeamExternalId::class)]
    private Collection $externalIds;
}
```

### TeamExternalId

```php
// src/Entity/TeamExternalId.php

#[ORM\Entity]
class TeamExternalId
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'externalIds')]
    private Team $team;

    #[ORM\Column(length: 50)]
    private string $provider; // ej: "football-data.org"

    #[ORM\Column(length: 50)]
    private string $externalId; // ID del equipo en ese proveedor
}
```

## Migración inicial

```bash
docker compose exec php-cli php bin/console doctrine:migrations:diff
docker compose exec php-cli php bin/console doctrine:migrations:migrate
```

## Seed de equipos

Crear un comando Symfony para insertar los equipos tracked con sus IDs de football-data.org:

```bash
docker compose exec php-cli php bin/console app:teams:seed
```

El comando crea los registros en `team` y sus correspondientes en `team_external_id` con `provider = "football-data.org"`.

### Equipos iniciales y sus IDs (verificar en football-data.org)

| Nombre | Liga | ID externo |
|--------|------|-----------|
| Real Madrid | PD | 86 |
| FC Barcelona | PD | 81 |
| Bayern Munich | BL1 | 5 |
| Borussia Dortmund | BL1 | 4 |
| AS Roma | SA | 100 |
| Juventus | SA | 109 |

## Tests

### Unitarios
- `test_team_entity__when_created__should_have_correct_default_values`
- `test_team_external_id__when_provider_is_set__should_return_correct_provider`

### Integración (DB real, sin mocks)
- `test_persisting_team__should_be_retrievable_from_database`
- `test_persisting_team_external_id__should_be_linked_to_team`
- `test_finding_team_by_external_id_and_provider__should_return_correct_team`
