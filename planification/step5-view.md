# Step 5 - Controller & View

## Controller

`src/Infrastructure/Betting/Http/Controller/BetsController.php`

```php
#[Route('/tomorrow/bets', name: 'tomorrow_bets')]
public function index(): Response
{
    $bets = $this->tomorrowBetsService->getData();
    return $this->render('bets/tomorrow.html.twig', ['bets' => $bets]);
}
```

Registrar en `config/services.yaml`:

```yaml
App\Infrastructure\Betting\Http\Controller\BetsController:
    tags: ['controller.service_arguments']
    public: true
```

Registrar ruta en `config/routes.yaml`:

```yaml
controllers:
    resource:
        path: ../src/Infrastructure/Betting/Http/Controller/
        namespace: App\Infrastructure\Betting\Http\Controller
    type: attribute
```

---

## Template Twig

`templates/bets/tomorrow.html.twig`

### Estructura HTML (acordeón con `<details>` / `<summary>`)

```
Página
└── Lista de equipos (ordenada por nextFixtureDate)
    └── Por cada equipo: <details> colapsable
        ├── <summary> (siempre visible):
        │   ├── Nombre del equipo
        │   ├── Próximo rival
        │   ├── Fecha del partido
        │   └── Casa / Fuera
        └── Detalle (visible al abrir):
            ├── Racha últimos 8 en liga (badges W/D/L)
            ├── Racha situacional del equipo (últimos 5)
            ├── Racha situacional del rival (últimos 5)
            └── Contadores de goles:
                ├── Equipo: X/N veces +2.5 en casa (o +1.5 fuera)
                └── Rival:  X/N veces +1.5 fuera (o +2.5 en casa)
```

### Comportamiento

- Equipos con partido mañana: fila resaltada visualmente
- Acordeón con `<details>` / `<summary>` nativos (sin JS, sin librerías)
- Rachas como badges de color: verde (W), gris (D), rojo (L)

### Ejemplo de fila desplegada

```
▼ Real Madrid        vs Atlético Madrid    |  Sábado 08/03  |  Casa

  Racha liga (8):     W W D W L W W D
  Racha en casa (5):  W W W D W
  Racha rival fuera:  L D W L D

  Goles:
    Real Madrid en casa:  7/10 partidos con +2.5 goles
    Atlético fuera:       3/9  partidos con +1.5 goles
```

---

## Tests

### Integración (DB real, API mockeada)

Ubicación: `tests/Integration/Infrastructure/Betting/BetsControllerTest.php`

- `test_loading_bets_page__should_return_200`
- `test_loading_bets_page__should_render_all_tracked_teams`
- `test_loading_bets_page__when_team_plays_tomorrow__should_be_highlighted_in_response`
- `test_loading_bets_page__teams_should_be_ordered_by_next_fixture_date`
