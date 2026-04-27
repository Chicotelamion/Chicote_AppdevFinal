<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WeatherNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@400;500;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .display { font-family: 'Poppins', sans-serif; }
        .mono { font-family: 'Space Mono', monospace; }
        .sky-bg {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(125, 211, 252, 0.22), transparent 30%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.25), transparent 30%),
                linear-gradient(135deg, #081223 0%, #13325b 45%, #235b92 100%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(18px);
            box-shadow: 0 18px 60px rgba(8, 18, 35, 0.28);
        }
        .leaflet-container { z-index: 10 !important; }
        .search-input:focus { outline: none; }
        .suggestions-list {
            position: absolute;
            inset: auto 0 0 0;
            transform: translateY(calc(100% + 8px));
            background: rgba(5, 15, 28, 0.95);
            border: 1px solid rgba(147, 197, 253, 0.2);
            border-radius: 1.25rem;
            overflow: hidden;
            z-index: 40;
        }
        .suggestion-item:hover { background: rgba(59, 130, 246, 0.18); }
        .soft-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="sky-bg text-white">
    <div class="mx-auto max-w-6xl px-4 py-8 md:px-6 md:py-10">
        <header class="mb-8">
            <div class="glass rounded-[2rem] px-6 py-6 md:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <p class="text-xs uppercase tracking-[0.35em] text-blue-200/70" id="dynamic-greeting">Smart Weather Hub</p>
                            <span class="rounded-full border border-sky-400/30 bg-sky-400/10 px-2.5 py-0.5 text-xs font-semibold text-sky-300" id="ph-clock">--:--:--</span>
                        </div>
                        <h1 class="display mt-3 text-4xl font-black tracking-tight md:text-6xl">WeatherNow</h1>
                        <p class="mt-3 max-w-2xl text-base text-blue-100/80 md:text-lg">
                            Search any city, compare saved places, use your current location, and plan the best time to head out.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('weather.index', ['unit' => 'metric']) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $unit['value'] === 'metric' ? 'border-white/40 bg-white/20 text-white' : 'border-white/20 text-blue-100/70' }}">
                            Celsius
                        </a>
                        <a href="{{ route('weather.index', ['unit' => 'imperial']) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $unit['value'] === 'imperial' ? 'border-white/40 bg-white/20 text-white' : 'border-white/20 text-blue-100/70' }}">
                            Fahrenheit
                        </a>
                        <button type="button" id="currentLocationBtn" class="rounded-full bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                            Use My Location
                        </button>
                    </div>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-5 py-4 text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        <section class="mb-8">
            <form action="{{ route('weather.search') }}" method="POST" id="searchForm" class="glass rounded-[2rem] p-4 md:p-5">
                @csrf
                <input type="hidden" name="unit" value="{{ $unit['value'] }}">
                <input type="hidden" name="lat" id="latInput">
                <input type="hidden" name="lon" id="lonInput">
                <input type="hidden" name="source" value="search">
                <div class="relative mb-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center">
                        <div class="flex flex-1 items-center gap-3 rounded-[1.4rem] border border-white/10 bg-white/5 px-5 py-4">
                            <svg class="h-6 w-6 flex-shrink-0 text-blue-200/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <input
                                type="text"
                                name="city"
                                id="cityInput"
                                value="{{ old('city') }}"
                                placeholder="Search Manila, Tokyo, London..."
                                autocomplete="off"
                                class="search-input w-full bg-transparent text-lg text-white placeholder:text-blue-100/45"
                            >
                        </div>
                        <button type="submit" id="searchBtn" class="rounded-[1.4rem] bg-white px-6 py-4 text-base font-semibold text-slate-900 transition hover:bg-blue-50 md:ml-3 mt-3 md:mt-0">
                            <span id="searchBtnText">Discover</span>
                        </button>
                    </div>
                    <div id="suggestionsDropdown" class="suggestions-list hidden"></div>
                </div>
                @if ($errors->any())
                    <div class="mt-4 rounded-2xl border border-red-300/30 bg-red-500/15 px-4 py-3 text-sm text-red-100">
                        {{ $errors->first() }}
                    </div>
                @endif
            </form>
        </section>

        <section class="mb-8 grid gap-6 lg:grid-cols-[1.45fr,0.95fr] mt-6">
            <div class="glass rounded-[2rem] p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-blue-200/70">Favorites Dashboard</p>
                        <h2 class="display mt-2 text-2xl font-bold">Saved places at a glance</h2>
                    </div>
                    <p class="text-sm text-blue-100/70">{{ $favorites->count() }} saved</p>
                </div>

                @if ($favoriteSnapshots->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($favoriteSnapshots as $snapshot)
                            <article class="soft-card rounded-[1.6rem] border border-white/10 bg-white/6 p-5 transition">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="display text-2xl font-bold">{{ $snapshot['city'] }}</p>
                                        <p class="text-sm text-blue-100/65">{{ $snapshot['country'] ?: 'Saved city' }}</p>
                                    </div>
                                    <img src="https://openweathermap.org/img/wn/{{ $snapshot['icon'] }}@2x.png" alt="{{ $snapshot['condition'] }}" class="h-16 w-16">
                                </div>
                                <div class="mt-4 flex items-end justify-between gap-4">
                                    <div>
                                        <p class="display text-4xl font-black">{{ round($snapshot['temperature']) }}&deg;{{ $unit['temperature'] }}</p>
                                        <p class="capitalize text-blue-50/90">{{ $snapshot['condition'] }}</p>
                                    </div>
                                    <div class="text-right text-sm text-blue-100/75">
                                        <p>Rain {{ $snapshot['rain_chance'] ?? 0 }}%</p>
                                        <p class="{{ $snapshot['air_quality']['tone'] }}">{{ $snapshot['air_quality']['label'] }}</p>
                                        <p>Comfort {{ $snapshot['comfort_score'] ?? '--' }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <a href="{{ route('weather.view', ['city' => $snapshot['city'], 'source' => 'favorites', 'unit' => $unit['value']]) }}" class="text-sm font-semibold text-sky-200 hover:text-white">
                                        Open details
                                    </a>
                                    <form action="{{ route('weather.removeFavorite') }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="city" value="{{ $snapshot['city'] }}">
                                        <input type="hidden" name="country" value="{{ $snapshot['country'] }}">
                                        <button type="submit" class="rounded-full border border-red-300/35 px-3 py-1 text-sm text-red-100 hover:bg-red-500/20">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-[1.6rem] border border-dashed border-white/20 px-6 py-10 text-center text-blue-100/75">
                        Save a city after searching and it will become a live dashboard card here.
                    </div>
                @endif
            </div>

            <div class="glass rounded-[2rem] p-6">
                <p class="text-xs uppercase tracking-[0.3em] text-blue-200/70">Travel Compare</p>
                <h2 class="display mt-2 text-2xl font-bold">Best quick comparison</h2>
                <p class="mt-2 text-sm text-blue-100/70">See which saved cities look cooler, drier, or easier to enjoy right now.</p>

                @if ($compareSnapshots->count() >= 2)
                    <div class="mt-5 space-y-3">
                        @foreach ($compareSnapshots as $snapshot)
                            <div class="rounded-[1.4rem] border border-white/10 bg-white/6 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-semibold">{{ $snapshot['city'] }}</p>
                                        <p class="text-xs text-blue-100/60">{{ $snapshot['country'] ?: 'Saved city' }}</p>
                                    </div>
                                    <p class="display text-2xl font-bold">{{ round($snapshot['temperature']) }}&deg;{{ $unit['temperature'] }}</p>
                                </div>
                                <div class="mt-3 grid grid-cols-3 gap-2 text-xs text-blue-50/80">
                                    <div class="rounded-xl bg-white/6 px-3 py-2">Rain {{ $snapshot['rain_chance'] ?? 0 }}%</div>
                                    <div class="rounded-xl bg-white/6 px-3 py-2">Air {{ $snapshot['air_quality']['label'] }}</div>
                                    <div class="rounded-xl bg-white/6 px-3 py-2">Comfort {{ $snapshot['comfort_score'] ?? '--' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-[1.6rem] border border-dashed border-white/20 px-5 py-8 text-sm text-blue-100/75">
                        Add at least two favorites to unlock side-by-side travel compare cards.
                    </div>
                @endif
            </div>

            <!-- Live Radar Map -->
            <div class="glass rounded-[2rem] p-6 lg:col-span-2">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-blue-200/70">Live Radar</p>
                        <h2 class="display mt-2 text-2xl font-bold">Precipitation & Clouds</h2>
                    </div>
                </div>
                <div id="weather-map" class="h-[400px] w-full rounded-2xl border border-white/10 bg-black/20"></div>
            </div>
        </section>

        <section class="glass rounded-[2rem] p-6">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-blue-200/70">Recent Searches</p>
                    <h2 class="display mt-2 text-2xl font-bold">Jump back into a city</h2>
                </div>
                @if ($history->isNotEmpty())
                    <form action="{{ route('weather.clearHistory') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all search history?');">
                        @csrf
                        <button type="submit" class="rounded-full border border-red-300/30 px-4 py-2 text-sm text-red-100 hover:bg-red-500/20">
                            Clear
                        </button>
                    </form>
                @endif
            </div>

            @if ($history->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($history as $item)
                        <a href="{{ route('weather.view', ['city' => $item->city, 'unit' => $unit['value']]) }}" class="block rounded-[1.35rem] border border-white/10 bg-white/6 px-5 py-4 transition hover:bg-white/10">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <img src="https://openweathermap.org/img/wn/{{ $item->icon }}@2x.png" alt="{{ $item->condition }}" class="h-12 w-12">
                                    <div>
                                        <p class="font-semibold">{{ $item->city }}, {{ $item->country }}</p>
                                        <p class="text-sm capitalize text-blue-100/65">{{ $item->condition }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="mono text-lg font-bold">{{ round($item->temperature) }}&deg;{{ $unit['temperature'] }}</p>
                                    <p class="text-xs text-blue-100/50">{{ $item->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-[1.6rem] border border-dashed border-white/20 px-6 py-10 text-center text-blue-100/75">
                    Your recent searches will show up here after you explore a city.
                </div>
            @endif
        </section>
    </div>

    <!-- Alert Toast Container -->
    <div id="toast-container" class="fixed top-6 right-6 z-50 flex flex-col gap-3"></div>

    <script>
        const cityInput = document.getElementById('cityInput');
        const suggestionsDropdown = document.getElementById('suggestionsDropdown');
        const searchForm = document.getElementById('searchForm');
        const latInput = document.getElementById('latInput');
        const lonInput = document.getElementById('lonInput');
        let suggestionTimeout;

        cityInput.addEventListener('input', function () {
            clearTimeout(suggestionTimeout);
            latInput.value = '';
            lonInput.value = '';

            const query = this.value.trim();
            if (query.length < 2) {
                suggestionsDropdown.classList.add('hidden');
                return;
            }

            suggestionTimeout = setTimeout(() => {
                fetch(`/api/suggestions?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.length) {
                            suggestionsDropdown.classList.add('hidden');
                            return;
                        }

                        suggestionsDropdown.innerHTML = data.map((suggestion) => `
                            <button type="button" class="suggestion-item block w-full px-4 py-3 text-left">
                                <div class="font-semibold text-white">${suggestion.city}</div>
                                <div class="text-sm text-blue-100/60">${suggestion.country}</div>
                            </button>
                        `).join('');

                        Array.from(suggestionsDropdown.children).forEach((item, index) => {
                            item.addEventListener('click', () => {
                                cityInput.value = data[index].city;
                                suggestionsDropdown.classList.add('hidden');
                                searchForm.submit();
                            });
                        });

                        suggestionsDropdown.classList.remove('hidden');
                    })
                    .catch(() => suggestionsDropdown.classList.add('hidden'));
            }, 250);
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('#searchForm')) {
                suggestionsDropdown.classList.add('hidden');
            }
        });

        searchForm.addEventListener('submit', function () {
            document.getElementById('searchBtnText').textContent = 'Loading...';
            document.getElementById('searchBtn').disabled = true;
        });

        document.getElementById('currentLocationBtn').addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('Geolocation is not available on this browser.');
                return;
            }

            this.disabled = true;
            this.textContent = 'Locating...';

            navigator.geolocation.getCurrentPosition((position) => {
                latInput.value = position.coords.latitude;
                lonInput.value = position.coords.longitude;
                cityInput.value = '';
                searchForm.submit();
            }, () => {
                this.disabled = false;
                this.textContent = 'Use My Location';
                alert('We could not access your current location.');
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        const toastContainer = document.getElementById('toast-container');
        function showToast(title, message, isError = false) {
            const toast = document.createElement('div');
            toast.className = `flex max-w-sm transform items-start gap-3 rounded-2xl border ${isError ? 'border-red-500/30 bg-red-500/90 text-white' : 'border-blue-300/30 bg-blue-600/90 text-white'} p-4 shadow-2xl backdrop-blur-md transition-all duration-300 translate-y-[-100%] opacity-0`;
            toast.innerHTML = `
                <div class="flex-1">
                    <p class="font-semibold text-sm">${title}</p>
                    <p class="mt-1 text-xs opacity-90">${message}</p>
                </div>
            `;
            toastContainer.appendChild(toast);
            
            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-[-100%]', 'opacity-0');
            });

            // Remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        const storedToast = localStorage.getItem('weather-toast');
        if (storedToast) {
            showToast('Update', storedToast);
            localStorage.removeItem('weather-toast');
        }

        // --- Real-Time Features ---

        // 1. Live Ticking Clock (PH Time)
        const clockEl = document.getElementById('ph-clock');
        const greetingEl = document.getElementById('dynamic-greeting');
        
        function updateClock() {
            const now = new Date();
            const options = { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const timeString = now.toLocaleTimeString('en-US', options);
            clockEl.textContent = `${timeString} PHT`;

            const hour = parseInt(now.toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour: '2-digit', hour12: false }), 10);
            if (hour >= 5 && hour < 12) greetingEl.textContent = 'Good Morning';
            else if (hour >= 12 && hour < 18) greetingEl.textContent = 'Good Afternoon';
            else greetingEl.textContent = 'Good Evening';
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 2. Auto-Refreshing Dashboard
        function fetchLiveDashboard() {
            fetch('/api/live-dashboard?unit={{ $unit['value'] }}')
                .then(res => res.json())
                .then(data => {
                    // Alert if high rain chance
                    if (data.favoriteSnapshots && data.favoriteSnapshots.length > 0) {
                        const highestRain = data.favoriteSnapshots.reduce((max, city) => Math.max(max, city.rain_chance || 0), 0);
                        if (highestRain >= 60) {
                            showToast('Weather Alert', 'High chance of rain detected in one of your saved locations.');
                        }
                    }
                    // For a full seamless update, we would use Alpine.js or React here. 
                    // Since it's Blade, we can silently reload the page data if we detect changes 
                    // without scrolling. But for now, the toast handles the "alert" part.
                    console.log('Dashboard data silently refreshed.');
                })
                .catch(err => console.error('Silent refresh failed', err));
        }
        setInterval(fetchLiveDashboard, 5 * 60 * 1000); // 5 minutes

        // 3. Live Radar Map (Leaflet + OpenWeatherMap)
        const map = L.map('weather-map', {
            maxBounds: [
                [-90, -180],
                [90, 180]
            ],
            maxBoundsViscosity: 1.0,
            minZoom: 5
        }).setView([12.8797, 121.7740], 6); // Default to Philippines, zoomed in
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">Carto</a>',
            noWrap: true
        }).addTo(map);

        const openWeatherKey = '{{ config('services.openweather.key') }}';
        L.tileLayer(`https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=${openWeatherKey}`, {
            opacity: 0.7,
            attribution: '&copy; OpenWeatherMap',
            noWrap: true
        }).addTo(map);
        
        // Add Wind Speed Layer for Typhoon visualization
        L.tileLayer(`https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid=${openWeatherKey}`, {
            opacity: 0.5,
            noWrap: true
        }).addTo(map);

        // Fetch and Plot Active Typhoons
        let typhoonMarkers = [];
        
        function updateTyphoons() {
            fetch('/api/typhoons')
                .then(res => res.json())
                .then(typhoons => {
                    // Clear old markers
                    typhoonMarkers.forEach(m => map.removeLayer(m));
                    typhoonMarkers = [];

                    typhoons.forEach(typhoon => {
                        // Plot on map
                        const marker = L.circleMarker([typhoon.lat, typhoon.lon], {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.5,
                            radius: 12
                        }).addTo(map);
                        
                        marker.bindPopup(`<b>${typhoon.name}</b><br>${typhoon.severity}<br>Wind: ${typhoon.wind_speed} km/h`);
                        typhoonMarkers.push(marker);

                        // Proximity Check (Mock logic: alert if within 500km of a known center)
                        // In a real app, we would cross-reference with actual saved city coords.
                        // For demonstration, we'll just trigger the alert if a typhoon exists.
                        showToast(
                            `CRITICAL: ${typhoon.severity} APPROACHING`, 
                            `${typhoon.name} detected near lat ${typhoon.lat}. Winds at ${typhoon.wind_speed} km/h. Please stay vigilant.`, 
                            true
                        );
                    });
                })
                .catch(err => console.error('Failed to fetch typhoons', err));
        }

        // Fetch immediately and then every 5 mins
        updateTyphoons();
        setInterval(updateTyphoons, 5 * 60 * 1000);
    </script>
</body>
</html>
