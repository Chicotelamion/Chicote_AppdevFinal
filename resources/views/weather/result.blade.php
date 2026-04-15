<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $weather['city'] }} - WeatherNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: auto; overflow-y: auto; }
        body { font-family: 'DM Sans', sans-serif; overflow-x: hidden; height: auto; }
        .mono { font-family: 'Space Mono', monospace; }
        .display { font-family: 'Poppins', sans-serif; }

        .sky-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 40%, #2d6a9f 100%);
            min-height: auto;
            position: relative;
            overflow: visible;
        }

        /* Cloud animations */
        .sky-bg::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(14, 165, 233, 0.1) 0%, transparent 50%);
            animation: moveGradient 15s ease infinite;
            z-index: -1;
            pointer-events: none;
        }

        /* Animated clouds */
        .sky-bg::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse 800px 100px at 20% 20%, rgba(255,255,255,0.15) 0%, transparent 50%),
                radial-gradient(ellipse 600px 80px at 80% 10%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(ellipse 700px 90px at 50% 30%, rgba(255,255,255,0.12) 0%, transparent 50%);
            background-size: 400% 400%, 300% 300%, 350% 350%;
            animation: cloudFloat 20s ease-in-out infinite, cloudFloat2 25s ease-in-out infinite 5s;
            z-index: -1;
            pointer-events: none;
        }

        @keyframes cloudFloat {
            0% { background-position: 0% 0%, 0% 0%, 0% 0%; }
            50% { background-position: 100% 0%, 100% 0%, 100% 0%; }
            100% { background-position: 0% 0%, 0% 0%, 0% 0%; }
        }

        @keyframes cloudFloat2 {
            0% { background-position: 100% 0%, 0% 0%, 50% 0%; }
            50% { background-position: 0% 0%, 100% 0%, -50% 0%; }
            100% { background-position: 100% 0%, 0% 0%, 50% 0%; }
        }

        @keyframes moveGradient {
            0% { transform: translate(0, 0); }
            50% { transform: translate(50px, 50px); }
            100% { transform: translate(0, 0); }
        }

        /* Falling rain effect */
        .rain-container {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .rain-drop {
            position: absolute;
            width: 2px;
            height: 15px;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0));
            animation: rainFall linear infinite;
            opacity: 0.6;
        }

        @keyframes rainFall {
            to {
                transform: translateY(100vh);
                opacity: 0;
            }
        }

        /* Ensure relative wrapper is above rain */
        .relative-wrapper {
            position: relative;
            z-index: 1;
        }

        /* Weather-specific backgrounds */
        .sky-bg.sunny {
            background: linear-gradient(135deg, #FFD89B 0%, #FFA500 50%, #87CEEB 100%);
        }

        .sky-bg.cloudy {
            background: linear-gradient(135deg, #B0C4DE 0%, #708090 50%, #36454F 100%);
        }

        .sky-bg.rainy {
            background: linear-gradient(135deg, #2C3E50 0%, #34495E 50%, #1A252F 100%);
        }

        .sky-bg.night {
            background: linear-gradient(135deg, #0a0e27 0%, #16213e 50%, #0f3460 100%);
        }

        .sky-bg.sunset {
            background: linear-gradient(135deg, #FF6B6B 0%, #FFA07A 50%, #FFD700 100%);
        }

        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
        }

        .glass:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-4px);
        }

        .temp-display {
            font-size: clamp(5rem, 15vw, 9rem);
            line-height: 1;
            font-weight: 800;
            background: linear-gradient(135deg, #60a5fa 0%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: pulse-light 3s ease-in-out infinite;
        }

        @keyframes pulse-light {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .weather-icon {
            animation: float 4s ease-in-out infinite;
            filter: drop-shadow(0 0 30px rgba(100, 200, 255, 0.5));
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .history-item {
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
            border-left: 3px solid transparent;
        }

        .history-item:hover {
            background: rgba(255, 255, 255, 0.12);
            border-left-color: rgba(96, 165, 250, 0.8);
            padding-left: 18px;
            transform: translateX(4px);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            100% { left: 100%; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in { animation: fadeInUp 0.6s ease 0.1s both; }
        .fade-in-delay { animation: fadeInUp 0.6s ease 0.3s both; }
        .fade-in-delay-2 { animation: fadeInUp 0.6s ease 0.5s both; }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-label {
            position: relative;
            z-index: 1;
        }

        .weather-card {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
        }

        .weather-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, transparent 100%);
            pointer-events: none;
        }

        .rain-alert {
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .weather-card.raining {
            border: 1px solid rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.2);
        }

        .relative-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .back-btn {
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateX(-4px);
        }

        .rain-drops {
            position: relative;
            overflow: hidden;
            height: 200px;
            border-radius: 20px;
        }

        .drop {
            position: absolute;
            width: 2px;
            background: linear-gradient(to bottom, rgba(96, 165, 250, 0.8), transparent);
            animation: fall linear infinite;
        }

        @keyframes fall {
            to { transform: translateY(200px); }
        }

        .drop:nth-child(1) { left: 10%; height: 100px; animation-duration: 1s; animation-delay: 0s; }
        .drop:nth-child(2) { left: 20%; height: 120px; animation-duration: 1.2s; animation-delay: 0.2s; }
        .drop:nth-child(3) { left: 30%; height: 110px; animation-duration: 1.1s; animation-delay: 0.4s; }
        .drop:nth-child(4) { left: 40%; height: 130px; animation-duration: 1.3s; animation-delay: 0.1s; }
        .drop:nth-child(5) { left: 50%; height: 105px; animation-duration: 1.15s; animation-delay: 0.3s; }
        .drop:nth-child(6) { left: 60%; height: 125px; animation-duration: 1.25s; animation-delay: 0.5s; }

        /* Loading Spinner Styles */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-top: 3px solid rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden { display: none; }

        /* Favorite Button */
        .favorite-btn {
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1.8rem;
            filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0));
        }

        .favorite-btn:hover {
            transform: scale(1.2);
        }

        .favorite-btn.active {
            filter: drop-shadow(0 0 8px rgba(239, 68, 68, 0.8));
            animation: favoritePopIn 0.3s ease;
        }

        @keyframes favoritePopIn {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1.2); }
        }
    </style>
</head>
<body class="sky-bg {{ $weather['bg_theme'] ?? 'cloudy' }} text-white">

<div class="relative-wrapper flex flex-col items-center px-4 py-8 md:py-12">

    {{-- Back Button --}}
    <div class="w-full max-w-2xl mb-8 fade-in">
        <a href="{{ route('weather.index') }}" class="back-btn inline-flex items-center gap-2 text-blue-300 hover:text-blue-200 font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Search
        </a>
    </div>

    {{-- Weather Result --}}
    <div class="w-full max-w-2xl mb-10 fade-in-delay">
        {{-- Rain Alert --}}
        @if($weather['is_raining'])
        <div class="rain-alert mb-6 p-5 rounded-2xl bg-gradient-to-r from-blue-600/30 to-cyan-600/30 border border-blue-400/50 flex items-center gap-4">
            <div class="text-3xl animate-bounce">🌧️</div>
            <div class="flex-1">
                <p class="font-bold text-blue-100 text-lg">It's currently raining!</p>
                <p class="text-blue-200/80 text-sm">
                    @if($weather['rain_mm'] > 0)
                        Precipitation: <span class="font-semibold">{{ $weather['rain_mm'] }} mm/hour</span>
                    @else
                        Expect wet conditions
                    @endif
                </p>
            </div>
            <div class="rain-drops w-20 h-16">
                <div class="drop"></div>
                <div class="drop"></div>
                <div class="drop"></div>
                <div class="drop"></div>
                <div class="drop"></div>
                <div class="drop"></div>
            </div>
        </div>
        @else
        <div class="rain-alert mb-6 p-4 rounded-2xl bg-gradient-to-r from-green-600/20 to-emerald-600/20 border border-green-400/40 flex items-center gap-3">
            <div class="text-2xl">☀️</div>
            <p class="font-semibold text-green-200">No rain expected - clear skies ahead!</p>
        </div>
        @endif

        <div class="glass weather-card rounded-3xl p-8 md:p-10 @if($weather['is_raining']) raining @endif">
            {{-- Header with City Info --}}
            <div class="flex items-start justify-between mb-8">
                <div>
                    <h2 class="display text-4xl md:text-5xl font-black mb-1">{{ $weather['city'] }}</h2>
                    <div class="flex items-center gap-3">
                        <span class="badge">{{ $weather['country'] }}</span>
                        <p class="text-blue-300/70 text-sm capitalize">{{ $weather['condition'] }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <button
                        type="button"
                        class="favorite-btn @if($weather['is_favorited']) active @endif"
                        id="favoriteBtn"
                        data-city="{{ $weather['city'] }}"
                        data-country="{{ $weather['country'] }}"
                        data-temperature="{{ $weather['temperature'] }}"
                        data-condition="{{ $weather['condition'] }}"
                        data-icon="{{ $weather['icon'] }}"
                        title="Add to favorites"
                    >
                        @if($weather['is_favorited']) ❤️ @else 🤍 @endif
                    </button>
                    <img
                        src="https://openweathermap.org/img/wn/{{ $weather['icon'] }}@4x.png"
                        alt="{{ $weather['condition'] }}"
                        class="weather-icon w-24 h-24 md:w-32 md:h-32 -mt-4"
                    >
                </div>
            </div>

            {{-- Main Temperature Display --}}
            <div class="mb-8">
                <div class="flex items-baseline gap-3 mb-3">
                    <span class="temp-display">{{ $weather['temperature'] }}°</span>
                    <div>
                        <p class="text-blue-200 capitalize text-2xl font-semibold">{{ $weather['condition'] }}</p>
                        <p class="text-blue-300/70 text-sm mt-1">Feels like <span class="font-semibold">{{ $weather['feels_like'] }}°C</span></p>
                    </div>
                </div>
            </div>

            {{-- Enhanced Stats Grid --}}
            <div class="grid grid-cols-3 gap-3 md:gap-4 mb-4">
                <div class="stat-card glass rounded-2xl p-5 text-center group hover:scale-105">
                    <p class="stat-label text-blue-300/60 text-xs mono uppercase tracking-wider mb-2 font-semibold">💧 Humidity</p>
                    <p class="text-2xl md:text-3xl font-bold">{{ $weather['humidity'] }}<span class="text-sm text-blue-300 font-normal">%</span></p>
                </div>
                <div class="stat-card glass rounded-2xl p-5 text-center group hover:scale-105">
                    <p class="stat-label text-blue-300/60 text-xs mono uppercase tracking-wider mb-2 font-semibold">💨 Wind Speed</p>
                    <p class="text-2xl md:text-3xl font-bold">{{ $weather['wind_speed'] }}<span class="text-sm text-blue-300 font-normal"> km/h</span></p>
                </div>
                <div class="stat-card glass rounded-2xl p-5 text-center group hover:scale-105">
                    <p class="stat-label text-blue-300/60 text-xs mono uppercase tracking-wider mb-2 font-semibold">🌡️ Range</p>
                    <p class="text-2xl md:text-3xl font-bold">{{ $weather['temp_max'] }}<span class="text-blue-300 font-normal">/</span>{{ $weather['temp_min'] }}<span class="text-sm text-blue-300 font-normal">°</span></p>
                </div>
            </div>

            {{-- Additional Weather Details --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="stat-card glass rounded-2xl p-4 text-center group hover:scale-105">
                    <p class="stat-label text-blue-300/60 text-xs mono uppercase tracking-wider mb-1 font-semibold">👁️ Visibility</p>
                    <p class="text-xl font-bold">{{ $weather['visibility'] }}<span class="text-sm text-blue-300 font-normal"> km</span></p>
                </div>
                <div class="stat-card glass rounded-2xl p-4 text-center group hover:scale-105">
                    <p class="stat-label text-blue-300/60 text-xs mono uppercase tracking-wider mb-1 font-semibold">🔽 Pressure</p>
                    <p class="text-xl font-bold">{{ $weather['pressure'] }}<span class="text-sm text-blue-300 font-normal"> mb</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Favorites Section --}}
    @if($favorites->isNotEmpty())
    <div class="w-full max-w-2xl mb-10 fade-in-delay">
        <div class="mb-4 flex items-center gap-2">
            <span class="badge">⭐ Favorite Cities</span>
            <p class="text-blue-300/60 text-sm">{{ $favorites->count() }} saved</p>
        </div>
        <div class="glass rounded-3xl overflow-hidden p-2">
            <div class="space-y-1">
                @foreach($favorites as $item)
                <a href="{{ route('weather.view', ['city' => $item->city]) }}" class="history-item flex items-center justify-between px-5 py-4 rounded-xl hover:glass transition-all cursor-pointer group">
                    <div class="flex items-center gap-4 flex-1">
                        <span class="text-xl">⭐</span>
                        <img 
                            src="https://openweathermap.org/img/wn/{{ $item->icon }}@2x.png" 
                            alt="{{ $item->condition }}" 
                            class="w-10 h-10 opacity-90 group-hover:scale-110 transition-transform"
                        >
                        <div>
                            <p class="font-semibold text-base">{{ $item->city }}</p>
                            <p class="text-blue-300/70 text-sm capitalize">{{ $item->condition }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="mono font-bold text-lg">{{ $item->temperature }}°C</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Search History --}}
    @if($history->isNotEmpty())
    <div class="w-full max-w-2xl pb-10 fade-in-delay">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="badge">📍 Recent Searches</span>
                <p class="text-blue-300/60 text-sm">{{ $history->count() }} cities</p>
            </div>
            <form action="{{ route('weather.clearHistory') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all search history?');">
                @csrf
                <button type="submit" class="text-xs px-3 py-1 rounded-full bg-red-500/20 hover:bg-red-500/30 text-red-300 hover:text-red-200 border border-red-400/30 hover:border-red-400/50 transition-all font-semibold">
                    🗑️ Clear
                </button>
            </form>
        </div>
        <div class="glass rounded-3xl overflow-hidden p-2">
            <div class="space-y-1">
                @foreach($history as $item)
                <a href="{{ route('weather.view', ['city' => $item->city]) }}" class="history-item flex items-center justify-between px-5 py-4 rounded-xl hover:glass transition-all cursor-pointer group">
                    <div class="flex items-center gap-4 flex-1">
                        <img 
                            src="https://openweathermap.org/img/wn/{{ $item->icon }}@2x.png" 
                            alt="{{ $item->condition }}" 
                            class="w-10 h-10 opacity-90 group-hover:scale-110 transition-transform"
                        >
                        <div>
                            <p class="font-semibold text-base">{{ $item->city }}, {{ $item->country }}</p>
                            <p class="text-blue-300/70 text-sm capitalize">{{ $item->condition }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="mono font-bold text-lg">{{ $item->temperature }}°C</p>
                        <p class="text-blue-300/50 text-xs">{{ $item->created_at->diffForHumans() }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>

{{-- Rain Animation --}}
@if($weather['is_raining'])
<div class="rain-container" id="rainContainer"></div>
@endif

<script>
    // Favorite button functionality
    const favoriteBtn = document.getElementById('favoriteBtn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', async function() {
            const city = this.dataset.city;
            const country = this.dataset.country;
            const temperature = this.dataset.temperature;
            const condition = this.dataset.condition;
            const icon = this.dataset.icon;

            try {
                const response = await fetch('/toggle-favorite', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        city,
                        country,
                        temperature,
                        condition,
                        icon
                    })
                });

                const result = await response.json();
                
                if (result.is_favorited) {
                    this.textContent = '❤️';
                    this.classList.add('active');
                } else {
                    this.textContent = '🤍';
                    this.classList.remove('active');
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
                alert('Failed to toggle favorite');
            }
        });
    }

    // Add CSRF token meta to head if not present
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = document.querySelector('input[name="_token"]')?.value || '';
        document.head.appendChild(meta);
    }

    // Create animated rain drops
    @if($weather['is_raining'])
    function createRain() {
        const container = document.getElementById('rainContainer');
        const rainDrop = document.createElement('div');
        rainDrop.className = 'rain-drop';
        
        const randomLeft = Math.random() * 100;
        const randomDelay = Math.random() * 0.5;
        const randomDuration = 0.5 + Math.random() * 0.5; // 0.5s to 1s
        
        rainDrop.style.left = randomLeft + '%';
        rainDrop.style.top = '-15px';
        rainDrop.style.animationDuration = randomDuration + 's';
        rainDrop.style.animationDelay = randomDelay + 's';
        
        container.appendChild(rainDrop);
        
        // Remove drop after animation completes
        setTimeout(() => {
            rainDrop.remove();
        }, (randomDuration + randomDelay) * 1000);
    }

    // Create multiple rain drops continuously
    setInterval(createRain, 100);
    
    // Create initial batch
    for (let i = 0; i < 20; i++) {
        setTimeout(createRain, i * 50);
    }
    @endif
</script>

</body>
</html>