<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeatherService
{
    private const MANILA_TIMEZONE = 'Asia/Manila';
    private const CURRENT_ENDPOINT = 'https://api.openweathermap.org/data/2.5/weather';
    private const FORECAST_ENDPOINT = 'https://api.openweathermap.org/data/2.5/forecast';
    private const AIR_QUALITY_ENDPOINT = 'https://api.openweathermap.org/data/2.5/air_pollution';

    public function resolveUnit(?string $unit): string
    {
        return in_array($unit, ['metric', 'imperial'], true) ? $unit : 'metric';
    }

    public function getUnitMeta(string $unit): array
    {
        return [
            'value' => $this->resolveUnit($unit),
            'temperature' => $unit === 'imperial' ? 'F' : 'C',
            'speed' => $unit === 'imperial' ? 'mph' : 'km/h',
            'distance' => $unit === 'imperial' ? 'mi' : 'km',
        ];
    }

    public function getByCity(string $city, string $unit = 'metric'): array
    {
        $unit = $this->resolveUnit($unit);
        $city = trim($city);

        if ($city === '') {
            throw new RuntimeException('City is required.');
        }

        $current = $this->rememberWeatherData(
            "weather.current.city.{$unit}." . md5(mb_strtolower($city)),
            fn () => $this->request(self::CURRENT_ENDPOINT, [
                'q' => $city,
                'units' => $unit,
            ])
        );

        return $this->buildPayload($current, $unit);
    }

    public function getByCoordinates(float $lat, float $lon, string $unit = 'metric'): array
    {
        $unit = $this->resolveUnit($unit);
        $keyBase = $this->coordinateCacheKey($lat, $lon);

        $current = $this->rememberWeatherData(
            "weather.current.coords.{$unit}.{$keyBase}",
            fn () => $this->request(self::CURRENT_ENDPOINT, [
                'lat' => $lat,
                'lon' => $lon,
                'units' => $unit,
            ])
        );

        return $this->buildPayload($current, $unit);
    }

    public function getFavoriteSnapshots(Collection $favorites, string $unit = 'metric'): Collection
    {
        return $favorites->map(function ($favorite) use ($unit) {
            try {
                $payload = $this->getByCity($this->cityQuery($favorite->city, $favorite->country), $unit);

                return [
                    'city' => $payload['city'],
                    'country' => $payload['country'],
                    'temperature' => $payload['temperature'],
                    'condition' => $payload['condition'],
                    'icon' => $payload['icon'],
                    'air_quality' => $payload['air_quality'],
                    'rain_chance' => $payload['rain_summary']['next_hours_chance'],
                    'comfort_score' => $payload['comfort_score'],
                    'link' => route('weather.view', ['city' => $payload['city']]),
                ];
            } catch (\Throwable $exception) {
                return [
                    'city' => $favorite->city,
                    'country' => $favorite->country,
                    'temperature' => $favorite->temperature,
                    'condition' => $favorite->condition,
                    'icon' => $favorite->icon,
                    'air_quality' => [
                        'index' => null,
                        'label' => 'Unavailable',
                        'tone' => 'text-slate-300',
                        'description' => 'Live air quality is unavailable right now.',
                    ],
                    'rain_chance' => null,
                    'comfort_score' => null,
                    'link' => route('weather.view', ['city' => $favorite->city]),
                ];
            }
        })->values();
    }

    private function buildPayload(array $current, string $unit): array
    {
        $lat = (float) data_get($current, 'coord.lat', 0);
        $lon = (float) data_get($current, 'coord.lon', 0);
        $forecast = $this->rememberWeatherData(
            "weather.forecast.{$unit}." . $this->coordinateCacheKey($lat, $lon),
            fn () => $this->request(self::FORECAST_ENDPOINT, [
                'lat' => $lat,
                'lon' => $lon,
                'units' => $unit,
            ])
        );
        $airQuality = $this->rememberWeatherData(
            'weather.air.' . $this->coordinateCacheKey($lat, $lon),
            fn () => $this->request(self::AIR_QUALITY_ENDPOINT, [
                'lat' => $lat,
                'lon' => $lon,
            ])
        );

        $unitMeta = $this->getUnitMeta($unit);
        $forecastItems = collect(data_get($forecast, 'list', []));
        $mainWeather = (string) data_get($current, 'weather.0.main', 'Clouds');
        $currentTemp = round((float) data_get($current, 'main.temp', 0));
        $rainAmount = (float) (data_get($current, 'rain.1h') ?? data_get($current, 'snow.1h', 0));
        $apiTimezoneOffset = (int) (data_get($current, 'timezone') ?? data_get($forecast, 'city.timezone', 0));
        $nextHoursRainChance = (int) round(($forecastItems->take(4)->max('pop') ?? 0) * 100);
        $airQualitySummary = $this->formatAirQuality((int) data_get($airQuality, 'list.0.main.aqi', 0));
        $hourlyForecast = $this->formatHourlyForecast($forecastItems, $unitMeta, $apiTimezoneOffset);
        $dailyForecast = $this->formatDailyForecast($forecastItems, $unitMeta, $apiTimezoneOffset);
        $bestTime = $this->bestTimeToGoOut($hourlyForecast);
        $comfortScore = $this->comfortScore($currentTemp, $nextHoursRainChance, $airQualitySummary['index']);
        $nowInManila = Carbon::now(self::MANILA_TIMEZONE);

        return [
            'city' => (string) data_get($current, 'name', ''),
            'country' => (string) data_get($current, 'sys.country', ''),
            'temperature' => $currentTemp,
            'feels_like' => round((float) data_get($current, 'main.feels_like', 0)),
            'humidity' => (int) data_get($current, 'main.humidity', 0),
            'wind_speed' => $this->formatWindSpeed((float) data_get($current, 'wind.speed', 0), $unit),
            'condition' => (string) data_get($current, 'weather.0.description', 'Unknown'),
            'icon' => (string) data_get($current, 'weather.0.icon', '01d'),
            'temp_min' => round((float) data_get($current, 'main.temp_min', 0)),
            'temp_max' => round((float) data_get($current, 'main.temp_max', 0)),
            'is_raining' => in_array($mainWeather, ['Rain', 'Thunderstorm', 'Drizzle'], true),
            'main_weather' => $mainWeather,
            'rain_mm' => round($rainAmount, 1),
            'visibility' => $this->formatVisibility((float) data_get($current, 'visibility', 10000), $unit),
            'pressure' => (int) data_get($current, 'main.pressure', 1013),
            'bg_theme' => $this->backgroundTheme($mainWeather, $currentTemp),
            'coordinates' => ['lat' => $lat, 'lon' => $lon],
            'sunrise' => $this->formatTimestampInManila((int) data_get($current, 'sys.sunrise', now()->timestamp)),
            'sunset' => $this->formatTimestampInManila((int) data_get($current, 'sys.sunset', now()->timestamp)),
            'unit' => $unitMeta,
            'forecast_hourly' => $hourlyForecast,
            'forecast_daily' => $dailyForecast,
            'air_quality' => $airQualitySummary,
            'smart_alerts' => $this->smartAlerts($currentTemp, $nextHoursRainChance, $airQualitySummary, (int) round($this->formatWindSpeed((float) data_get($current, 'wind.speed', 0), $unit))),
            'what_to_wear' => $this->whatToWear($currentTemp, $nextHoursRainChance, $mainWeather),
            'best_time_out' => $bestTime,
            'comfort_score' => $comfortScore,
            'ph_time' => [
                'time' => $nowInManila->format('g:i A'),
                'date' => $nowInManila->format('D, M j'),
                'timezone' => 'PHT',
                'label' => 'Philippine Time',
            ],
            'rain_summary' => [
                'next_hours_chance' => $nextHoursRainChance,
                'message' => $nextHoursRainChance >= 60
                    ? 'High chance of rain later today.'
                    : ($nextHoursRainChance >= 30 ? 'A passing shower is possible today.' : 'Rain looks unlikely for the next few hours.'),
            ],
        ];
    }

    private function rememberWeatherData(string $key, callable $callback): array
    {
        return Cache::remember($key, now()->addMinutes(10), $callback);
    }

    private function request(string $url, array $params): array
    {
        $response = Http::timeout(10)->get($url, array_merge($params, [
            'appid' => config('services.openweather.key'),
        ]));

        if ($response->failed() || (int) $response->json('cod', 200) >= 400) {
            throw new RuntimeException((string) ($response->json('message') ?: 'Weather request failed.'));
        }

        return $response->json();
    }

    private function formatHourlyForecast(Collection $forecastItems, array $unitMeta, int $apiTimezoneOffset): array
    {
        return $forecastItems
            ->take(8)
            ->map(function (array $item) use ($unitMeta, $apiTimezoneOffset) {
                return [
                    'time' => $this->formatForecastSlotTime($item, $apiTimezoneOffset),
                    'temperature' => round((float) data_get($item, 'main.temp', 0)),
                    'icon' => (string) data_get($item, 'weather.0.icon', '01d'),
                    'condition' => (string) data_get($item, 'weather.0.description', ''),
                    'rain_chance' => (int) round(((float) data_get($item, 'pop', 0)) * 100),
                    'unit' => $unitMeta['temperature'],
                ];
            })
            ->all();
    }

    private function formatDailyForecast(Collection $forecastItems, array $unitMeta, int $apiTimezoneOffset): array
    {
        return $forecastItems
            ->groupBy(fn (array $item) => $this->forecastDateKey($item, $apiTimezoneOffset))
            ->take(5)
            ->map(function (Collection $items, string $date) use ($unitMeta, $apiTimezoneOffset) {
                $midday = $items->sortBy(fn (array $item) => abs($this->forecastHour($item, $apiTimezoneOffset) - 12))->first();

                return [
                    'day' => Carbon::parse($date, self::MANILA_TIMEZONE)->format('D'),
                    'date' => Carbon::parse($date, self::MANILA_TIMEZONE)->format('M j'),
                    'temp_max' => round((float) $items->max('main.temp_max')),
                    'temp_min' => round((float) $items->min('main.temp_min')),
                    'icon' => (string) data_get($midday, 'weather.0.icon', '01d'),
                    'condition' => (string) data_get($midday, 'weather.0.description', ''),
                    'rain_chance' => (int) round(((float) $items->max('pop')) * 100),
                    'unit' => $unitMeta['temperature'],
                ];
            })
            ->values()
            ->all();
    }

    private function smartAlerts(int $temperature, int $rainChance, array $airQuality, int $windSpeed): array
    {
        $alerts = [];

        if ($rainChance >= 60) {
            $alerts[] = [
                'title' => 'Umbrella recommended',
                'message' => 'Rain looks likely within the next few hours.',
            ];
        }

        if ($temperature >= 33) {
            $alerts[] = [
                'title' => 'Heat advisory',
                'message' => 'Stay hydrated and avoid long exposure around midday.',
            ];
        }

        if ($windSpeed >= 12) {
            $alerts[] = [
                'title' => 'Breezy conditions',
                'message' => 'Expect stronger wind than usual when you head out.',
            ];
        }

        if (($airQuality['index'] ?? 0) >= 4) {
            $alerts[] = [
                'title' => 'Poor air quality',
                'message' => 'Sensitive groups should consider reducing outdoor activity.',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'title' => 'Comfortable weather',
                'message' => 'Conditions look friendly for errands, walks, and commute time.',
            ];
        }

        return $alerts;
    }

    private function whatToWear(int $temperature, int $rainChance, string $mainWeather): string
    {
        if ($rainChance >= 60 || in_array($mainWeather, ['Rain', 'Drizzle', 'Thunderstorm'], true)) {
            return 'Bring a light rain layer and shoes you do not mind getting wet.';
        }

        if ($temperature <= 16) {
            return 'A light jacket or hoodie will make this much more comfortable.';
        }

        if ($temperature >= 30) {
            return 'Wear breathable clothes, keep water nearby, and look for shade when you can.';
        }

        return 'A casual tee or light top should feel comfortable for most of the day.';
    }

    private function bestTimeToGoOut(array $hourlyForecast): array
    {
        if ($hourlyForecast === []) {
            return [
                'label' => 'Now',
                'message' => 'Current conditions are the best guide at the moment.',
            ];
        }

        $best = collect($hourlyForecast)
            ->map(function (array $hour) {
                $temperature = (int) $hour['temperature'];
                $temperaturePenalty = abs(24 - $temperature);
                $score = 100 - ((int) $hour['rain_chance']) - ($temperaturePenalty * 2);

                return $hour + ['score' => $score];
            })
            ->sortByDesc('score')
            ->first();

        return [
            'label' => $best['time'],
            'message' => "Around {$best['time']} looks like the most comfortable window to head out.",
        ];
    }

    private function comfortScore(int $temperature, int $rainChance, ?int $airIndex): int
    {
        $score = 100;
        $score -= min(40, abs(24 - $temperature) * 2);
        $score -= (int) round($rainChance / 2);
        $score -= max(0, (($airIndex ?? 1) - 1) * 8);

        return max(5, min(100, $score));
    }

    private function formatAirQuality(int $aqi): array
    {
        return match ($aqi) {
            1 => ['index' => 1, 'label' => 'Excellent', 'tone' => 'text-emerald-200', 'description' => 'Air is clean and comfortable for outdoor activity.'],
            2 => ['index' => 2, 'label' => 'Fair', 'tone' => 'text-green-200', 'description' => 'Air quality is generally good for most people.'],
            3 => ['index' => 3, 'label' => 'Moderate', 'tone' => 'text-yellow-200', 'description' => 'Most people are fine, but sensitive groups may notice it.'],
            4 => ['index' => 4, 'label' => 'Poor', 'tone' => 'text-orange-200', 'description' => 'Consider shorter outdoor exposure if you are sensitive.'],
            5 => ['index' => 5, 'label' => 'Very Poor', 'tone' => 'text-red-200', 'description' => 'Outdoor activity should be limited where possible.'],
            default => ['index' => null, 'label' => 'Unavailable', 'tone' => 'text-slate-200', 'description' => 'Air quality data is not available right now.'],
        };
    }

    private function backgroundTheme(string $mainWeather, int $temperature): string
    {
        if (in_array($mainWeather, ['Rain', 'Thunderstorm', 'Drizzle'], true)) {
            return 'rainy';
        }

        if (in_array($mainWeather, ['Clouds', 'Mist', 'Smoke', 'Haze', 'Dust', 'Fog', 'Sand', 'Ash', 'Squall', 'Tornado'], true)) {
            return 'cloudy';
        }

        if ($mainWeather === 'Clear') {
            return $temperature > 20 ? 'sunny' : 'night';
        }

        return 'cloudy';
    }

    private function cityQuery(string $city, ?string $country): string
    {
        return trim($city . ($country ? ", {$country}" : ''));
    }

    private function coordinateCacheKey(float $lat, float $lon): string
    {
        return number_format($lat, 2, '.', '') . '.' . number_format($lon, 2, '.', '');
    }

    private function formatWindSpeed(float $speed, string $unit): float|int
    {
        if ($unit === 'imperial') {
            return round($speed, 1);
        }

        return round($speed * 3.6, 1);
    }

    private function formatVisibility(float $visibilityMeters, string $unit): float
    {
        if ($unit === 'imperial') {
            return round($visibilityMeters / 1609.344, 1);
        }

        return round($visibilityMeters / 1000, 1);
    }

    private function formatTimestampInManila(int $timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp, 'UTC')
            ->setTimezone(self::MANILA_TIMEZONE)
            ->format('g:i A');
    }

    private function formatForecastSlotTime(array $item, int $apiTimezoneOffset): string
    {
        return $this->forecastDateTime($item, $apiTimezoneOffset)->format('g A');
    }

    private function forecastDateKey(array $item, int $apiTimezoneOffset): string
    {
        return $this->forecastDateTime($item, $apiTimezoneOffset)->toDateString();
    }

    private function forecastHour(array $item, int $apiTimezoneOffset): int
    {
        return (int) $this->forecastDateTime($item, $apiTimezoneOffset)->format('G');
    }

    private function forecastDateTime(array $item, int $apiTimezoneOffset): Carbon
    {
        $timestamp = (int) data_get($item, 'dt', 0);

        if ($timestamp > 0) {
            return Carbon::createFromTimestamp($timestamp, 'UTC')
                ->setTimezone(self::MANILA_TIMEZONE);
        }

        return Carbon::parse((string) data_get($item, 'dt_txt', now()->toDateTimeString()), 'UTC')
            ->setTimezone(self::MANILA_TIMEZONE);
    }
}
