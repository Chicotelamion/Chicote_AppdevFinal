<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $weather['city'] }} Weather - WeatherNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@400;500;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }
        /* Dynamic Weather Backgrounds */
        .sunny-bg {
            background:
                radial-gradient(circle at top left, rgba(253, 224, 71, 0.22), transparent 30%),
                radial-gradient(circle at 80% 20%, rgba(249, 115, 22, 0.25), transparent 30%),
                linear-gradient(135deg, #1e1b4b 0%, #4c1d95 45%, #e11d48 100%);
        }
        .rainy-bg {
            background:
                radial-gradient(circle at top left, rgba(148, 163, 184, 0.22), transparent 30%),
                radial-gradient(circle at 80% 20%, rgba(51, 65, 85, 0.25), transparent 30%),
                linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #334155 100%);
        }
        .cloudy-bg {
            background:
                radial-gradient(circle at top left, rgba(226, 232, 240, 0.15), transparent 30%),
                radial-gradient(circle at 80% 20%, rgba(148, 163, 184, 0.15), transparent 30%),
                linear-gradient(135deg, #1e293b 0%, #334155 45%, #475569 100%);
        }
        .clear-bg {
            background:
                radial-gradient(circle at top left, rgba(125, 211, 252, 0.22), transparent 30%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.25), transparent 30%),
                linear-gradient(135deg, #081223 0%, #13325b 45%, #235b92 100%);
        }
        .default-bg {
            background:
                radial-gradient(circle at top left, rgba(125, 211, 252, 0.22), transparent 30%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.25), transparent 30%),
                linear-gradient(135deg, #081223 0%, #13325b 45%, #235b92 100%);
        }
        .display { font-family: 'Poppins', sans-serif; }
        .mono { font-family: 'Space Mono', monospace; }
        .scene {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .scene::before {
            content: '';
            position: absolute;
            inset: -10%;
            background:
                radial-gradient(circle at 18% 45%, rgba(198, 230, 226, 0.18), transparent 22%),
                radial-gradient(circle at 68% 70%, rgba(188, 219, 214, 0.16), transparent 20%),
                radial-gradient(circle at 72% 24%, rgba(255, 255, 255, 0.05), transparent 18%);
            filter: blur(24px);
            animation: mistShift 14s ease-in-out infinite alternate;
        }
        .scene::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(7, 15, 16, 0.1), rgba(7, 15, 16, 0.42));
        }
        .rain-drop {
            position: absolute;
            top: -18vh;
            width: 1.8px;
            height: 88px;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(255,255,255,0), rgba(220, 239, 238, 0.85));
            transform: rotate(13deg);
            animation: rain linear infinite;
            opacity: 0.38;
        }
        .rain-drop::after {
            content: '';
            position: absolute;
            left: -7px;
            bottom: -8px;
            width: 16px;
            height: 9px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(223, 245, 244, 0.22) 0%, rgba(223, 245, 244, 0) 72%);
        }
        .dashboard {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
        }
        .weather-shell, .soft-panel, .strip-card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(18px);
            box-shadow: 0 18px 60px rgba(8, 18, 35, 0.28);
        }
        .weather-shell {
            width: min(1180px, 100%);
            position: relative;
            border-radius: 30px;
            overflow: hidden;
        }
        .weather-shell::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 12% 38%, rgba(255,255,255,0.08), transparent 28%),
                radial-gradient(circle at 82% 65%, rgba(196, 228, 223, 0.08), transparent 24%);
            pointer-events: none;
        }
        .glass-noise {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(255,255,255,0.14) 0 1px, transparent 1px),
                radial-gradient(circle at 40% 70%, rgba(255,255,255,0.08) 0 1px, transparent 1px),
                radial-gradient(circle at 78% 36%, rgba(255,255,255,0.12) 0 1px, transparent 1px),
                radial-gradient(circle at 62% 14%, rgba(255,255,255,0.08) 0 1px, transparent 1px);
            background-size: 120px 120px, 150px 150px, 180px 180px, 130px 130px;
            opacity: 0.45;
            pointer-events: none;
        }
        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.15fr 0.95fr;
            gap: 28px;
            padding: 28px;
            min-height: 72vh;
        }
        .left-panel {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 22px;
        }
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding: 18px;
        }
        .soft-panel {
            border-radius: 22px;
        }
        .temp-number {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(4.5rem, 10vw, 7rem);
            line-height: 0.95;
            font-weight: 800;
            letter-spacing: -0.05em;
        }
        .section-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.28em;
            color: rgba(228, 241, 240, 0.58);
        }
        .forecast-row {
            display: grid;
            grid-template-columns: 48px 1fr 44px;
            gap: 12px;
            align-items: center;
            padding: 10px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .forecast-row:first-child {
            border-top: 0;
            padding-top: 0;
        }
        .mini-hours {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
        }
        .mini-hours::-webkit-scrollbar {
            display: none; /* Chrome/Safari */
        }
        .mini-hour {
            text-align: center;
            padding: 10px 6px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
        }
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .metric-card {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.04);
        }
        .action-btn {
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 0.92rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.08);
            transition: 0.2s ease;
        }
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.14);
        }
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 0.75rem;
            color: rgba(224, 247, 250, 0.9);
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .bottom-strip {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            padding: 0 28px 28px;
        }
        .strip-card {
            padding: 18px;
            border-radius: 22px;
        }
        @keyframes rain {
            0% {
                transform: translate3d(0, 0, 0) rotate(13deg);
                opacity: 0;
            }
            12% {
                opacity: 0.38;
            }
            100% {
                transform: translate3d(-14vw, 132vh, 0) rotate(13deg);
                opacity: 0;
            }
        }
        @keyframes mistShift {
            from { transform: translate3d(0, 0, 0) scale(1); }
            to { transform: translate3d(2%, -2%, 0) scale(1.06); }
        }
        @keyframes fogDrift {
            0% { transform: translate(-10%, 5%); }
            100% { transform: translate(10%, -5%); }
        }
        @keyframes floatUp {
            0% { transform: translateY(110vh) scale(0.8); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-20vh) scale(1.2); opacity: 0; }
        }
        /* Air Quality Gauge */
        .aqi-gauge {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
            margin-right: 6px;
            margin-top: -2px;
        }
        /* Sun Arch */
        .sun-arch-container {
            position: relative;
            height: 65px;
            margin-top: 25px;
            width: 100%;
        }
        .sun-arch-path {
            position: absolute;
            bottom: 0;
            left: 8%;
            right: 8%;
            height: 65px;
            border: 2px dashed rgba(255,255,255,0.25);
            border-radius: 100px 100px 0 0;
            border-bottom: 0;
        }
        .sun-icon {
            position: absolute;
            width: 16px;
            height: 16px;
            background: #fde047;
            border-radius: 50%;
            box-shadow: 0 0 14px 6px rgba(253, 224, 71, 0.4);
            transform: translate(-50%, -50%);
            z-index: 2;
            transition: all 1s ease-out;
        }
        /* Particles */
        .fog-layer {
            position: absolute;
            inset: -50%;
            background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.06), transparent 60%);
            filter: blur(40px);
            animation: fogDrift 30s linear infinite alternate;
        }
        .dust-mote {
            position: absolute;
            background: #fff;
            border-radius: 50%;
            filter: blur(1px);
            opacity: 0.4;
            animation: floatUp 15s linear infinite;
        }

        @media (max-width: 980px) {
            .hero-grid {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .bottom-strip {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 640px) {
            .hero-grid,
            .bottom-strip {
                padding-left: 16px;
                padding-right: 16px;
            }
            .hero-grid {
                padding-top: 16px;
            }
            .left-panel,
            .right-panel {
                padding: 12px 4px;
            }
            .mini-hours {
                grid-auto-flow: column;
                grid-template-columns: unset;
                grid-auto-columns: minmax(70px, 1fr);
            }
        }
    </style>
</head>
<body class="text-white
    @php
        $condition = strtolower($weather['condition']);
        $bgClass = 'default-bg';
        if (str_contains($condition, 'sunny') || str_contains($condition, 'clear sky')) {
            $bgClass = 'sunny-bg';
        } elseif (str_contains($condition, 'rain')) {
            $bgClass = 'rainy-bg';
        } elseif (str_contains($condition, 'cloud')) {
            $bgClass = 'cloudy-bg';
        } elseif (str_contains($condition, 'clear')) {
            $bgClass = 'clear-bg';
        }
    @endphp
    {{ $bgClass }}">
    <div class="scene" id="rainScene" aria-hidden="true"></div>

    <main class="dashboard">
        <div class="weather-shell">
            <div class="glass-noise"></div>

            <section class="hero-grid">
                <div class="left-panel">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="section-label">WeatherNow</p>
                            <p class="mt-3 text-sm text-white/60">{{ $weather['condition'] }}</p>
                            <div class="mt-3 status-chip">
                                <span>{{ $weather['ph_time']['label'] }}</span>
                                <span>&bull;</span>
                                <span>{{ $weather['ph_time']['date'] }}</span>
                                <span>{{ $weather['ph_time']['time'] }} {{ $weather['ph_time']['timezone'] }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('weather.index', ['unit' => $weather['unit']['value']]) }}" class="action-btn flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                  <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                                </svg>
                                Back to Dashboard
                            </a>
                            <div class="ml-2 flex items-center gap-2 border-l border-white/20 pl-4">
                                <a href="{{ route('weather.view', ['city' => $weather['city'], 'unit' => 'metric', 'source' => $viewState['source'] ?? null]) }}" class="action-btn {{ $weather['unit']['value'] === 'metric' ? 'bg-white/18' : '' }}">C</a>
                                <a href="{{ route('weather.view', ['city' => $weather['city'], 'unit' => 'imperial', 'source' => $viewState['source'] ?? null]) }}" class="action-btn {{ $weather['unit']['value'] === 'imperial' ? 'bg-white/18' : '' }}">F</a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-16 md:mt-28">
                        <div class="flex items-end gap-4">
                            <div class="temp-number">{{ $weather['temperature'] }}</div>
                            <div class="pb-3">
                                <div class="text-3xl font-semibold">{{ $weather['unit']['temperature'] }}</div>
                                <img src="https://openweathermap.org/img/wn/{{ $weather['icon'] }}@2x.png" alt="{{ $weather['condition'] }}" class="h-16 w-16 opacity-90">
                            </div>
                        </div>
                        <h1 class="display mt-4 text-3xl font-bold md:text-4xl">{{ $weather['city'] }}</h1>
                        <p class="mt-2 text-white/68">{{ $weather['country'] }} &bull; Feels like {{ $weather['feels_like'] }}&deg;{{ $weather['unit']['temperature'] }}</p>
                        <p class="mono mt-3 text-xs text-white/52 flex items-center">
                            Comfort {{ $weather['comfort_score'] }}/100 &bull; 
                            <span class="ml-2 mr-2">
                                @php
                                    $aqiColor = match($weather['air_quality']['index'] ?? 0) {
                                        1 => '#34d399', // Green
                                        2 => '#facc15', // Yellow
                                        3 => '#fb923c', // Orange
                                        4 => '#f87171', // Red
                                        5 => '#a78bfa', // Purple
                                        default => '#94a3b8'
                                    };
                                @endphp
                                <span class="aqi-gauge" style="background: conic-gradient({{ $aqiColor }} {{ (($weather['air_quality']['index'] ?? 1) / 5) * 100 }}%, rgba(255,255,255,0.1) 0);"></span>
                                AQ {{ $weather['air_quality']['label'] }}
                            </span> &bull; 
                            Best around {{ $weather['best_time_out']['label'] }}
                        </p>
                    </div>

                    <div class="mt-10 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            id="favoriteBtn"
                            class="action-btn {{ $weather['is_favorited'] ? 'text-rose-100' : '' }}"
                            data-city="{{ $weather['city'] }}"
                            data-country="{{ $weather['country'] }}"
                            data-temperature="{{ $weather['temperature'] }}"
                            data-condition="{{ $weather['condition'] }}"
                            data-icon="{{ $weather['icon'] }}"
                            data-favorited="{{ $weather['is_favorited'] ? 'true' : 'false' }}"
                        >
                            {{ $weather['is_favorited'] ? 'Remove Favorite' : 'Save Favorite' }}
                        </button>
                        <div class="rounded-full border border-white/12 bg-white/6 px-4 py-2 text-sm text-white/72">
                            {{ $weather['what_to_wear'] }}
                        </div>
                    </div>
                </div>

                <div class="right-panel">
                    <div class="soft-panel p-5">
                        <div class="flex items-center justify-between gap-3">
                            <p class="section-label">Hourly Outlook</p>
                            <p class="text-[11px] text-white/50">Shown in PH time</p>
                        </div>
                        <div class="mt-4 h-[120px] w-full">
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>

                    <div class="soft-panel p-5">
                        <div class="flex items-center justify-between gap-4">
                            <p class="section-label">5-Day Forecast</p>
                            <p class="text-xs text-white/48">{{ $weather['rain_summary']['message'] }}</p>
                        </div>
                        <div class="mt-4">
                            @foreach ($weather['forecast_daily'] as $day)
                                <div class="forecast-row">
                                    <div>
                                        <p class="text-sm font-semibold">{{ $day['day'] }}</p>
                                        <p class="text-[11px] text-white/48">{{ $day['date'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <img src="https://openweathermap.org/img/wn/{{ $day['icon'] }}@2x.png" alt="{{ $day['condition'] }}" class="h-9 w-9">
                                        <div class="h-[2px] flex-1 rounded-full bg-white/10">
                                            <div class="h-[2px] rounded-full bg-cyan-200/80" style="width: {{ max(18, min(100, $day['rain_chance'])) }}%;"></div>
                                        </div>
                                    </div>
                                    <div class="text-right text-sm font-semibold text-white/82">
                                        {{ $day['temp_max'] }}&deg;
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="soft-panel p-5">
                        <div class="flex items-center justify-between gap-3">
                            <p class="section-label">Details</p>
                            <p class="text-[11px] text-white/50">API values formatted clearly</p>
                        </div>
                        <div class="metric-grid mt-4">
                            <div class="metric-card">
                                <p class="text-xs text-white/52">Humidity</p>
                                <p class="mt-2 text-xl font-semibold">{{ $weather['humidity'] }}%</p>
                            </div>
                            <div class="metric-card">
                                <p class="text-xs text-white/52">Wind</p>
                                <p class="mt-2 text-xl font-semibold">{{ $weather['wind_speed'] }} {{ $weather['unit']['speed'] }}</p>
                            </div>
                            <div class="metric-card col-span-2 relative">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-white/52">Sunrise</p>
                                        <p class="mt-1 text-sm font-semibold">{{ $weather['sunrise'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-white/52">Sunset</p>
                                        <p class="mt-1 text-sm font-semibold">{{ $weather['sunset'] }}</p>
                                    </div>
                                </div>
                                <div class="sun-arch-container">
                                    <div class="sun-arch-path" id="sunPathBox"></div>
                                    <div class="sun-icon" id="sunIcon"></div>
                                </div>
                            </div>
                            <div class="metric-card">
                                <p class="text-xs text-white/52">Visibility</p>
                                <p class="mt-2 text-xl font-semibold">{{ $weather['visibility'] }} {{ $weather['unit']['distance'] }}</p>
                            </div>
                            <div class="metric-card">
                                <p class="text-xs text-white/52">Pressure</p>
                                <p class="mt-2 text-xl font-semibold">{{ $weather['pressure'] }} mb</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bottom-strip">
                <div class="strip-card">
                    <p class="section-label">Alerts</p>
                    <div class="mt-4 space-y-3">
                        @foreach (collect($weather['smart_alerts'])->take(2) as $alert)
                            <div>
                                <p class="font-semibold">{{ $alert['title'] }}</p>
                                <p class="mt-1 text-sm text-white/62">{{ $alert['message'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="strip-card">
                    <p class="section-label">Saved Places</p>
                    <div class="mt-4 space-y-3">
                        @forelse (collect($favoriteSnapshots)->take(2) as $snapshot)
                            <a href="{{ route('weather.view', ['city' => $snapshot['city'], 'source' => 'favorites', 'unit' => $weather['unit']['value']]) }}" class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <img src="https://openweathermap.org/img/wn/{{ $snapshot['icon'] }}@2x.png" alt="{{ $snapshot['condition'] }}" class="h-10 w-10">
                                    <div>
                                        <p class="font-semibold">{{ $snapshot['city'] }}</p>
                                        <p class="text-xs text-white/54">{{ $snapshot['condition'] }}</p>
                                    </div>
                                </div>
                                <p class="text-sm font-semibold">{{ round($snapshot['temperature']) }}&deg;</p>
                            </a>
                        @empty
                            <p class="text-sm text-white/56">No saved cities yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="strip-card">
                    <p class="section-label">Recent Searches</p>
                    <div class="mt-4 space-y-3">
                        @forelse (collect($history)->take(2) as $item)
                            <a href="{{ route('weather.view', ['city' => $item->city, 'unit' => $weather['unit']['value']]) }}" class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ $item->city }}</p>
                                    <p class="text-xs text-white/54">{{ $item->updated_at->diffForHumans() }}</p>
                                </div>
                                <p class="text-sm font-semibold">{{ round($item->temperature) }}&deg;</p>
                            </a>
                        @empty
                            <p class="text-sm text-white/56">No search history yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        const openedFromFavorites = @json(($viewState['source'] ?? null) === 'favorites');
        const favoriteBtn = document.getElementById('favoriteBtn');

        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', async function () {
                if (this.disabled) {
                    return;
                }

                try {
                    this.disabled = true;
                    const response = await fetch('{{ route('weather.toggleFavorite') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            city: this.dataset.city,
                            country: this.dataset.country,
                            temperature: this.dataset.temperature,
                            condition: this.dataset.condition,
                            icon: this.dataset.icon
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Favorite request failed.');
                    }

                    const result = await response.json();
                    localStorage.setItem('weather-toast', result.is_favorited ? 'Added to favorites.' : 'Removed from favorites.');

                    if (!result.is_favorited && openedFromFavorites) {
                        window.location.href = '{{ route('weather.index', ['unit' => $weather['unit']['value']]) }}';
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    alert('We could not update favorites right now.');
                } finally {
                    this.disabled = false;
                }
            });
        }

        const scene = document.getElementById('rainScene');
        const bgClass = '{{ $bgClass }}';
        
        if (scene) {
            if (bgClass === 'rainy-bg') {
                const count = window.innerWidth < 768 ? 55 : 95;
                for (let index = 0; index < count; index += 1) {
                    const drop = document.createElement('span');
                    drop.className = 'rain-drop';
                    drop.style.left = `${Math.random() * 118}%`;
                    drop.style.height = `${55 + Math.random() * 55}px`;
                    drop.style.animationDuration = `${0.65 + Math.random() * 0.75}s`;
                    drop.style.animationDelay = `${Math.random() * -2.2}s`;
                    drop.style.opacity = `${0.16 + Math.random() * 0.3}`;
                    scene.appendChild(drop);
                }
            } else if (bgClass === 'cloudy-bg') {
                for (let i = 0; i < 3; i++) {
                    const fog = document.createElement('div');
                    fog.className = 'fog-layer';
                    fog.style.animationDelay = `${i * -10}s`;
                    scene.appendChild(fog);
                }
            } else if (bgClass === 'sunny-bg' || bgClass === 'clear-bg') {
                const count = 30;
                for (let i = 0; i < count; i++) {
                    const mote = document.createElement('div');
                    mote.className = 'dust-mote';
                    const size = Math.random() * 4 + 2;
                    mote.style.width = `${size}px`;
                    mote.style.height = `${size}px`;
                    mote.style.left = `${Math.random() * 100}%`;
                    mote.style.animationDuration = `${10 + Math.random() * 20}s`;
                    mote.style.animationDelay = `${Math.random() * -20}s`;
                    scene.appendChild(mote);
                }
            }
        }

        // Initialize Chart.js Hourly Outlook
        const hourlyData = @json($weather['forecast_hourly']);
        const ctx = document.getElementById('hourlyChart').getContext('2d');
        
        let gradient = ctx.createLinearGradient(0, 0, 0, 150);
        gradient.addColorStop(0, 'rgba(255, 255, 255, 0.25)');
        gradient.addColorStop(1, 'rgba(255, 255, 255, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: hourlyData.map((d, i) => i === 0 ? 'Now' : d.time),
                datasets: [{
                    data: hourlyData.map(d => d.temperature),
                    borderColor: 'rgba(255, 255, 255, 0.8)',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgba(255,255,255,0.8)',
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                let index = context.dataIndex;
                                return ` ${context.raw}° (Rain: ${hourlyData[index].rain_chance}%)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.6)', font: { size: 10, family: "'DM Sans', sans-serif" } }
                    },
                    y: {
                        display: false,
                        min: Math.min(...hourlyData.map(d => d.temperature)) - 2,
                        max: Math.max(...hourlyData.map(d => d.temperature)) + 2
                    }
                }
            }
        });

        // Initialize Sun Progress Arch
        const sunriseStr = '{{ $weather['sunrise'] }}';
        const sunsetStr = '{{ $weather['sunset'] }}';
        
        function parseTimeStr(str) {
            const [time, period] = str.split(' ');
            let [h, m] = time.split(':').map(Number);
            if (period === 'PM' && h !== 12) h += 12;
            if (period === 'AM' && h === 12) h = 0;
            return h * 60 + m;
        }
        
        const riseMins = parseTimeStr(sunriseStr);
        const setMins = parseTimeStr(sunsetStr);
        
        const now = new Date();
        const phTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
        const currentMins = phTime.getHours() * 60 + phTime.getMinutes();
        
        let progress = 0;
        if (currentMins <= riseMins) progress = 0;
        else if (currentMins >= setMins) progress = 1;
        else progress = (currentMins - riseMins) / (setMins - riseMins);
        
        const sunIcon = document.getElementById('sunIcon');
        const sunPathBox = document.getElementById('sunPathBox');
        if (sunIcon && sunPathBox) {
            // Arc is a half ellipse. width is path width, height is path height.
            // Using percentages for absolute positioning.
            // Angle goes from 180 (pi) to 0.
            const theta = Math.PI - (progress * Math.PI);
            
            // Adjust X to be bounded within the path boundaries
            // Path is at left 8%, right 8% so it's 84% wide.
            // Let's position relative to the container. The path starts at 8% and ends at 92%.
            const xPercent = 8 + 84 * (1 - (theta / Math.PI)); 
            
            // Y is a sin curve, 100% height = 65px. 
            // When theta=pi/2 (noon), sin=1, height offset = 0 (top of container).
            // When theta=0 or pi, sin=0, height offset = 65px (bottom).
            // Since the path is 65px tall and sits at bottom 0, we can use percentage of height from bottom.
            const yPercent = 100 - (Math.sin(theta) * 100); 

            sunIcon.style.left = `${xPercent}%`;
            // Add 10px padding for the top of the container so sun doesn't clip
            sunIcon.style.top = `calc(${yPercent}% - 8px)`;
        }
    </script>
</body>
</html>
