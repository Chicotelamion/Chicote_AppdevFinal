<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\SearchHistory;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function __construct(private WeatherService $weatherService)
    {
    }

    public function index()
    {
        $unit = $this->currentUnit(request());
        $history = SearchHistory::latest()->take(10)->get();
        $favorites = Favorite::latest()->get();
        $favoriteSnapshots = $this->weatherService->getFavoriteSnapshots($favorites, $unit);
        $compareSnapshots = $favoriteSnapshots->take(3)->values();

        return view('weather.index', [
            'history' => $history,
            'favorites' => $favorites,
            'favoriteSnapshots' => $favoriteSnapshots,
            'compareSnapshots' => $compareSnapshots,
            'unit' => $this->weatherService->getUnitMeta($unit),
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'city' => 'nullable|string|max:100|required_without_all:lat,lon',
            'lat' => 'nullable|numeric|required_with:lon',
            'lon' => 'nullable|numeric|required_with:lat',
        ]);

        $unit = $this->currentUnit($request);

        try {
            if ($request->filled('lat') && $request->filled('lon')) {
                $weather = $this->weatherService->getByCoordinates(
                    (float) $request->input('lat'),
                    (float) $request->input('lon'),
                    $unit
                );
            } else {
                $weather = $this->weatherService->getByCity((string) $request->input('city'), $unit);
            }
        } catch (\Throwable $exception) {
            return back()->withErrors(['city' => 'City not found. Please try again.'])->withInput();
        }

        return $this->renderWeatherResult($weather, ['source' => $request->input('source')]);
    }

    public function viewCity(Request $request, $city)
    {
        $city = urldecode($city);

        try {
            $weather = $this->weatherService->getByCity($city, $this->currentUnit($request));
        } catch (\Throwable $exception) {
            return redirect()->route('weather.index')->withErrors(['city' => 'City not found. Please try again.']);
        }

        return $this->renderWeatherResult($weather, ['source' => $request->query('source')]);
    }

    public function clearHistory()
    {
        SearchHistory::query()->delete();

        return redirect()->route('weather.index')->with('status', 'Search history cleared successfully.');
    }

    public function toggleFavorite(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            \Log::error('Toggle Favorite Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to toggle favorite',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeFavorite(Request $request)
    {
        $request->validate([
            'city' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        Favorite::where('city', trim((string) $request->input('city')))
            ->where('country', $request->input('country'))
            ->delete();

        return back()->with('status', 'City removed from favorites.');
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

    private function renderWeatherResult(array $weather, array $viewState = [])
    {
        $weather['is_favorited'] = Favorite::where('city', $weather['city'])
            ->where('country', $weather['country'])
            ->exists();

        $this->storeSearchHistory($weather);

        $history = SearchHistory::latest()->take(10)->get();
        $favorites = Favorite::latest()->get();
        $favoriteSnapshots = $this->weatherService->getFavoriteSnapshots($favorites, $weather['unit']['value']);

        return view('weather.result', [
            'weather' => $weather,
            'history' => $history,
            'favorites' => $favorites,
            'favoriteSnapshots' => $favoriteSnapshots,
            'viewState' => $viewState,
        ]);
    }

    private function storeSearchHistory(array $weather): void
    {
        $latest = SearchHistory::latest()->first();

        if ($latest && $latest->city === $weather['city'] && $latest->country === $weather['country']) {
            $latest->update([
                'temperature' => $weather['temperature'],
                'condition' => $weather['condition'],
                'icon' => $weather['icon'],
                'updated_at' => now(),
            ]);

            return;
        }

        SearchHistory::create([
            'city' => $weather['city'],
            'country' => $weather['country'],
            'temperature' => $weather['temperature'],
            'condition' => $weather['condition'],
            'icon' => $weather['icon'],
        ]);
    }

    private function currentUnit(Request $request): string
    {
        $requested = $request->input('unit', $request->query('unit', session('weather_unit', 'metric')));
        $unit = $this->weatherService->resolveUnit($requested);
        session(['weather_unit' => $unit]);

        return $unit;
    }
}
