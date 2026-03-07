# Step 3 - API Client (football-data.org)

## Configuración

Añadir al `.env`:

```env
FOOTBALL_DATA_API_KEY=tu_api_key_aqui
```

Añadir a `config/services.yaml`:

```yaml
App\Domain\Betting\Repository\FootballDataProviderInterface:
    class: App\Infrastructure\Betting\Http\Client\FootballDataClient
    arguments:
        $apiKey: '%env(FOOTBALL_DATA_API_KEY)%'
```

Añadir a `config/services_test.yaml` (para tests de integración):

```yaml
App\Domain\Betting\Repository\FootballDataProviderInterface:
    class: App\Infrastructure\Betting\Http\Client\FootballDataClient
    arguments:
        $apiKey: 'test_key'
    autowire: true
    public: true
```

## Interfaz de dominio

`src/Domain/Betting/Repository/FootballDataProviderInterface.php`

```php
interface FootballDataProviderInterface
{
    public function getNextFixture(string $externalTeamId, string $competition): array;

    public function getFinishedMatches(string $externalTeamId, string $competition, int $limit): array;
}
```

## Implementación

`src/Infrastructure/Betting/Http/Client/FootballDataClient.php`

- Implementa `FootballDataProviderInterface`
- Usa `symfony/http-client`
- Base URL: `https://api.football-data.org/v4`
- Header en cada request: `X-Auth-Token: {apiKey}`

### getNextFixture
Endpoint: `GET /teams/{id}/matches?status=SCHEDULED&competitions={competition}&limit=1`

### getFinishedMatches
Endpoint: `GET /teams/{id}/matches?status=FINISHED&competitions={competition}&limit={limit}`

### Respuesta: datos que nos interesan por partido
- Fecha del partido
- Si el equipo es local o visitante
- Goles marcados por el equipo
- Resultado (W/D/L) desde la perspectiva del equipo

## Manejo de errores

- Rate limit (HTTP 429): loguear y devolver array vacío, no romper la página
- API caída: devolver array vacío, el servicio usará datos de DB

## Tests

### Unitarios (HttpClient mockeado)
- `test_getting_next_fixture__should_return_expected_fixture_data`
- `test_getting_finished_matches__should_return_expected_matches_array`
- `test_getting_finished_matches__when_api_returns_empty__should_return_empty_array`
- `test_api_client__when_rate_limit_exceeded__should_return_empty_array_without_exception`

Ubicación: `tests/Unit/Infrastructure/Betting/FootballDataClientTest.php`

### Integración (respuesta JSON fija mockeada)
- `test_getting_next_fixture_from_football_data__should_return_mapped_response`
- `test_getting_finished_matches_from_football_data__should_return_mapped_response`

Ubicación: `tests/Integration/Infrastructure/Betting/FootballDataClientTest.php`

Usar `MockHttpClient` + `MockResponse` de Symfony para simular la respuesta sin llamada real.
