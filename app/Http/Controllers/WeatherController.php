<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SearchHistory;
use App\Models\Favorite;

class WeatherController extends Controller
{
    public function index()
    {
        $history = SearchHistory::latest()->take(10)->get();
        $favorites = Favorite::latest()->get();
        return view('weather.index', compact('history', 'favorites'));
    }

    public function search(Request $request)
    {
        $request->validate([
            'city' => 'required|string|max:100',
        ]);

        $city    = trim($request->input('city'));
        $apiKey  = env('OPENWEATHER_API_KEY');
        $url     = "https://api.openweathermap.org/data/2.5/weather";

        $response = Http::get($url, [
            'q'     => $city,
            'appid' => $apiKey,
            'units' => 'metric',
        ]);

        if ($response->failed() || $response->json('cod') !== 200) {
            return back()->withErrors(['city' => 'City not found. Please try again.'])->withInput();
        }

        $data = $response->json();

        // Detect if it's raining
        $mainWeather = $data['weather'][0]['main'];
        $weatherCode = $data['weather'][0]['icon'];
        $isRaining = in_array($mainWeather, ['Rain', 'Thunderstorm', 'Drizzle']);
        $rainChance = isset($data['rain']) ? ($data['rain']['1h'] ?? 0) : 0;
        $precipitation = isset($data['rain']) ? ($data['rain']['1h'] ?? 0) : (isset($data['snow']) ? ($data['snow']['1h'] ?? 0) : 0);

        $weather = [
            'city'        => $data['name'],
            'country'     => $data['sys']['country'],
            'temperature' => round($data['main']['temp']),
            'feels_like'  => round($data['main']['feels_like']),
            'humidity'    => $data['main']['humidity'],
            'wind_speed'  => round($data['wind']['speed'] * 3.6), // m/s to km/h
            'condition'   => $data['weather'][0]['description'],
            'icon'        => $weatherCode,
            'temp_min'    => round($data['main']['temp_min']),
            'temp_max'    => round($data['main']['temp_max']),
            'is_raining'  => $isRaining,
            'main_weather' => $mainWeather,
            'rain_mm'     => round($precipitation, 1),
            'visibility'  => round(($data['visibility'] ?? 10000) / 1000, 1), // Convert to km
            'pressure'    => $data['main']['pressure'] ?? 1013,
            'bg_theme'    => $this->getBackgroundTheme($mainWeather, $data['main']['temp']),
            'is_favorited' => Favorite::where('city', $data['name'])->where('country', $data['sys']['country'])->exists(),
        ];

        // Save to search history
        SearchHistory::create([
            'city'        => $weather['city'],
            'country'     => $weather['country'],
            'temperature' => $weather['temperature'],
            'condition'   => $weather['condition'],
            'icon'        => $weather['icon'],
        ]);

        $history = SearchHistory::latest()->take(10)->get();
        $favorites = Favorite::latest()->get();

        return view('weather.result', compact('weather', 'history', 'favorites'));
    }

    public function viewCity($city)
    {
        $city    = urldecode($city);
        $apiKey  = env('OPENWEATHER_API_KEY');
        $url     = "https://api.openweathermap.org/data/2.5/weather";

        $response = Http::get($url, [
            'q'     => $city,
            'appid' => $apiKey,
            'units' => 'metric',
        ]);

        if ($response->failed() || $response->json('cod') !== 200) {
            return redirect()->route('weather.index')->withErrors(['city' => 'City not found. Please try again.']);
        }

        $data = $response->json();

        // Detect if it's raining
        $mainWeather = $data['weather'][0]['main'];
        $weatherCode = $data['weather'][0]['icon'];
        $isRaining = in_array($mainWeather, ['Rain', 'Thunderstorm', 'Drizzle']);
        $precipitation = isset($data['rain']) ? ($data['rain']['1h'] ?? 0) : (isset($data['snow']) ? ($data['snow']['1h'] ?? 0) : 0);

        $weather = [
            'city'        => $data['name'],
            'country'     => $data['sys']['country'],
            'temperature' => round($data['main']['temp']),
            'feels_like'  => round($data['main']['feels_like']),
            'humidity'    => $data['main']['humidity'],
            'wind_speed'  => round($data['wind']['speed'] * 3.6),
            'condition'   => $data['weather'][0]['description'],
            'icon'        => $weatherCode,
            'temp_min'    => round($data['main']['temp_min']),
            'temp_max'    => round($data['main']['temp_max']),
            'is_raining'  => $isRaining,
            'main_weather' => $mainWeather,
            'rain_mm'     => round($precipitation, 1),
            'visibility'  => round(($data['visibility'] ?? 10000) / 1000, 1),
            'pressure'    => $data['main']['pressure'] ?? 1013,
            'bg_theme'    => $this->getBackgroundTheme($mainWeather, $data['main']['temp']),
            'is_favorited' => Favorite::where('city', $data['name'])->where('country', $data['sys']['country'])->exists(),
        ];

        $history = SearchHistory::latest()->take(10)->get();
        $favorites = Favorite::latest()->get();

        return view('weather.result', compact('weather', 'history', 'favorites'));
    }

    private function getBackgroundTheme($mainWeather, $temperature)
    {
        if (in_array($mainWeather, ['Rain', 'Thunderstorm', 'Drizzle'])) {
            return 'rainy';
        } elseif (in_array($mainWeather, ['Clouds', 'Mist', 'Smoke', 'Haze', 'Dust', 'Fog', 'Sand', 'Ash', 'Squall', 'Tornado'])) {
            return 'cloudy';
        } elseif ($mainWeather === 'Clear') {
            return $temperature > 20 ? 'sunny' : 'night';
        } elseif (in_array($mainWeather, ['Snow'])) {
            return 'cloudy';
        } else {
            return 'cloudy';
        }
    }

    public function clearHistory()
    {
        SearchHistory::truncate();
        return redirect()->route('weather.index')->with('success', 'Search history cleared successfully!');
    }

    public function toggleFavorite(Request $request)
    {
        $request->validate([
            'city' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
            'temperature' => 'nullable|numeric',
            'condition' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $city = trim($request->input('city'));
        $country = $request->input('country');

        $existing = Favorite::where('city', $city)->where('country', $country)->first();

        if ($existing) {
            $existing->delete();
            $isFavorited = false;
        } else {
            Favorite::create([
                'city' => $city,
                'country' => $country,
                'temperature' => $request->input('temperature'),
                'condition' => $request->input('condition'),
                'icon' => $request->input('icon'),
            ]);
            $isFavorited = true;
        }

        return response()->json(['is_favorited' => $isFavorited]);
    }

    public function citySuggestions(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Use Nominatim API for city suggestions (free, no API key needed)
        try {
            $response = Http::timeout(5)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 8,
                'addresstype' => 'city',
            ]);

            $suggestions = array_map(function ($item) {
                $parts = explode(',', $item['address'] ?? '');
                $country = trim(end($parts));
                
                return [
                    'city' => $item['name'] ?? '',
                    'country' => $country,
                    'display' => $item['display_name'] ?? $item['name'] ?? '',
                ];
            }, $response->json() ?? []);

            // Remove duplicates based on city name
            $unique = [];
            $seen = [];
            foreach ($suggestions as $suggestion) {
                $key = strtolower($suggestion['city']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique[] = $suggestion;
                }
            }

            return response()->json(array_slice($unique, 0, 8));
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }
}
