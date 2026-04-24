<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\SearchHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_renders_weather_and_deduplicates_latest_history_entry(): void
    {
        Http::fake($this->fakeWeatherResponses());

        $response = $this->post(route('weather.search'), [
            'city' => 'Manila',
            'unit' => 'metric',
        ]);

        $response->assertOk();
        $response->assertSee('Manila');
        $this->assertDatabaseCount('search_histories', 1);

        Http::fake($this->fakeWeatherResponses());

        $this->post(route('weather.search'), [
            'city' => 'Manila',
            'unit' => 'metric',
        ])->assertOk();

        $this->assertDatabaseCount('search_histories', 1);
    }

    public function test_toggle_favorite_adds_and_removes_city(): void
    {
        $payload = [
            'city' => 'Manila',
            'country' => 'PH',
            'temperature' => 29,
            'condition' => 'clear sky',
            'icon' => '01d',
        ];

        $this->postJson(route('weather.toggleFavorite'), $payload)
            ->assertOk()
            ->assertJson(['is_favorited' => true]);

        $this->assertDatabaseHas('favorites', [
            'city' => 'Manila',
            'country' => 'PH',
        ]);

        $this->postJson(route('weather.toggleFavorite'), $payload)
            ->assertOk()
            ->assertJson(['is_favorited' => false]);

        $this->assertDatabaseMissing('favorites', [
            'city' => 'Manila',
            'country' => 'PH',
        ]);
    }

    public function test_remove_favorite_endpoint_deletes_saved_city(): void
    {
        Favorite::create([
            'city' => 'Tokyo',
            'country' => 'JP',
            'temperature' => 21,
            'condition' => 'cloudy',
            'icon' => '03d',
        ]);

        $this->delete(route('weather.removeFavorite'), [
            'city' => 'Tokyo',
            'country' => 'JP',
        ])->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'city' => 'Tokyo',
            'country' => 'JP',
        ]);
    }

    private function fakeWeatherResponses(): array
    {
        return [
            'https://api.openweathermap.org/data/2.5/weather*' => Http::response([
                'coord' => ['lat' => 14.6, 'lon' => 121.0],
                'weather' => [['main' => 'Clear', 'description' => 'clear sky', 'icon' => '01d']],
                'main' => [
                    'temp' => 29,
                    'feels_like' => 33,
                    'humidity' => 70,
                    'temp_min' => 27,
                    'temp_max' => 31,
                    'pressure' => 1008,
                ],
                'wind' => ['speed' => 4],
                'visibility' => 10000,
                'sys' => ['country' => 'PH', 'sunrise' => now()->subHours(4)->timestamp, 'sunset' => now()->addHours(6)->timestamp],
                'name' => 'Manila',
                'cod' => 200,
            ], 200),
            'https://api.openweathermap.org/data/2.5/forecast*' => Http::response([
                'cod' => '200',
                'list' => collect(range(1, 8))->map(function ($offset) {
                    return [
                        'dt_txt' => now()->addHours($offset * 3)->format('Y-m-d H:i:s'),
                        'main' => [
                            'temp' => 28 + $offset,
                            'temp_min' => 26 + $offset,
                            'temp_max' => 29 + $offset,
                        ],
                        'weather' => [[
                            'description' => $offset % 2 === 0 ? 'scattered clouds' : 'clear sky',
                            'icon' => $offset % 2 === 0 ? '03d' : '01d',
                        ]],
                        'pop' => 0.1 * ($offset % 4),
                    ];
                })->all(),
            ], 200),
            'https://api.openweathermap.org/data/2.5/air_pollution*' => Http::response([
                'list' => [[
                    'main' => ['aqi' => 2],
                ]],
            ], 200),
        ];
    }
}
