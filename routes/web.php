<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/', [WeatherController::class, 'index'])->name('weather.index');
Route::post('/search', [WeatherController::class, 'search'])->middleware('throttle:15,1')->name('weather.search');
Route::get('/city/{city}', [WeatherController::class, 'viewCity'])->name('weather.view');
Route::post('/clear-history', [WeatherController::class, 'clearHistory'])->name('weather.clearHistory');
Route::post('/toggle-favorite', [WeatherController::class, 'toggleFavorite'])->name('weather.toggleFavorite');
Route::delete('/favorites', [WeatherController::class, 'removeFavorite'])->name('weather.removeFavorite');
Route::get('/api/suggestions', [WeatherController::class, 'citySuggestions'])->name('weather.suggestions');
Route::get('/api/live-dashboard', [WeatherController::class, 'liveDashboard'])->name('weather.liveDashboard');
