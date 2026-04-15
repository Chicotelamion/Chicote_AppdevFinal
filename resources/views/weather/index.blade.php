<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeatherNow ☀️</title>
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

        /* Star animation for night mode */
        .stars {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -2;
            pointer-events: none;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        /* Falling rain effect */
        .rain-container {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            pointer-events: none;
        }

        .rain-drop {
            position: absolute;
            width: 1px;
            height: 10px;
            background: rgba(255, 255, 255, 0.5);
            animation: rainFall linear infinite;
        }

        @keyframes rainFall {
            to {
                transform: translateY(100vh);
            }
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

        .search-input {
            outline: none;
            transition: all 0.3s ease;
            font-size: 1.125rem;
            letter-spacing: 0.5px;
            caret-color: #93c5fd;
            font-weight: 500;
        }

        .search-input::placeholder {
            color: rgba(147, 197, 253, 0.6) !important;
            font-weight: 400;
        }

        .search-input:focus {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        .search-input:focus::placeholder {
            color: rgba(147, 197, 253, 0.4) !important;
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

        .search-btn {
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .search-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .search-btn:hover::before {
            left: 100%;
        }

        .search-btn:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.15);
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

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .fade-in { animation: fadeInUp 0.6s ease 0.1s both; }
        .fade-in-delay { animation: fadeInUp 0.6s ease 0.3s both; }
        .fade-in-delay-2 { animation: fadeInUp 0.6s ease 0.5s both; }
        .slide-in { animation: slideInLeft 0.6s ease both; }

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

        .history-scroll {
            max-height: 400px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        .history-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .history-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .history-scroll::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.4);
            border-radius: 10px;
        }

        .history-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.6);
        }

        .pulse-ring {
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }

        .relative-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .rain-alert {
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .rain-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1a2f4a 40%, #1e3a5f 100%) !important;
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
        .drop:nth-child(7) { left: 70%; height: 115px; animation-duration: 1.05s; animation-delay: 0.2s; }
        .drop:nth-child(8) { left: 80%; height: 120px; animation-duration: 1.2s; animation-delay: 0.4s; }
        .drop:nth-child(9) { left: 90%; height: 110px; animation-duration: 1.1s; animation-delay: 0.1s; }

        .weather-card.raining {
            border: 1px solid rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.2);
        }

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

        /* Suggestions Dropdown */
        .suggestions-list {
            position: absolute;
            top: 100%;
            left: 0;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(25px);
            border: 2px solid rgba(96, 165, 250, 0.4);
            border-radius: 18px;
            margin-top: 8px;
            z-index: 9999;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            width: 100%;
        }

        .suggestion-item {
            padding: 16px 18px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .suggestion-item:first-child {
            padding-top: 12px;
        }

        .suggestion-item:last-child {
            border-bottom: none;
            padding-bottom: 12px;
        }

        .suggestion-item:hover {
            background: rgba(59, 130, 246, 0.3);
            padding-left: 22px;
        }

        .suggestion-city {
            font-weight: 700;
            color: #fff;
            font-size: 1rem;
            letter-spacing: 0.3px;
        }

        .suggestion-country {
            font-size: 0.85rem;
            color: rgba(156, 163, 175, 0.9);
            margin-top: 2px;
        }

        .search-container {
            position: relative;
            z-index: 100;
        }

        /* Favorite Button */
        .favorite-btn {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .favorite-btn:hover {
            transform: scale(1.2);
        }

        .favorite-btn.active {
            filter: drop-shadow(0 0 8px rgba(239, 68, 68, 0.8));
        }
    </style>
</head>
<body class="sky-bg text-white overflow-x-hidden">

<div class="relative-wrapper flex flex-col items-center px-4 py-8 md:py-12 min-h-screen w-full">

    {{-- Animated Header --}}
    <div class="w-full max-w-3xl mb-12 fade-in">
        <div class="text-center">
            <div class="inline-flex items-center gap-2 mb-4">
                <span class="badge">🌍 Real-Time Weather</span>
            </div>
            <h1 class="display text-5xl md:text-6xl font-black mb-2 bg-gradient-to-r from-blue-200 via-blue-300 to-cyan-300 bg-clip-text text-transparent">
                WeatherNow
            </h1>
            <p class="text-blue-200/80 text-lg md:text-xl font-light">Discover global weather conditions instantly</p>
        </div>
    </div>

    {{-- Enhanced Search Form --}}
    <div class="w-full max-w-2xl mb-10 fade-in-delay relative z-50">
        <form action="{{ route('weather.search') }}" method="POST" id="searchForm">
            @csrf
            <div class="search-container relative">
                <div class="flex gap-3 flex-col sm:flex-row">
                    <div class="flex-1 glass rounded-2xl px-6 py-4 flex items-center gap-3 group relative">
                        <svg class="w-6 h-6 text-blue-300 flex-shrink-0 group-focus-within:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <input
                            type="text"
                            name="city"
                            id="cityInput"
                            placeholder="Manila, Tokyo, London..."
                            value="{{ old('city') }}"
                            class="search-input bg-transparent flex-1 text-white placeholder-blue-300/40 text-base font-medium"
                            autocomplete="off"
                        >
                    </div>
                    <button type="submit" class="search-btn glass rounded-2xl px-8 py-4 font-semibold text-white whitespace-nowrap flex items-center gap-2 justify-center min-h-[56px]" id="searchBtn">
                        <span id="searchBtnText">Discover</span>
                        <svg id="searchBtnSpinner" class="w-5 h-5 hidden" fill="none">
                            <circle class="spinner" cx="10" cy="10" r="8"></circle>
                        </svg>
                        <svg id="searchBtnIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </div>
                <div id="suggestionsDropdown" class="suggestions-list hidden"></div>

                @if ($errors->any())
                    <div class="mt-4 p-4 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 text-sm">
                        ⚠️ {{ $errors->first() }}
                    </div>
                @endif
            </div>
        </form>
    </div>

    {{-- Favorites Section --}}
    @if($favorites->isNotEmpty())
    <div class="w-full max-w-2xl mb-10 fade-in-delay-2">
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

    {{-- Weather Result - Enhanced --}}
    {{-- Results now appear on separate page --}}

    {{-- Search History - Enhanced --}}
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

<script>
// City Suggestions
const cityInput = document.getElementById('cityInput');
const suggestionsDropdown = document.getElementById('suggestionsDropdown');
let suggestionTimeout;

function positionDropdown() {
    // Absolute positioning handles this automatically
    // No manual positioning needed
}

cityInput.addEventListener('input', function() {
    clearTimeout(suggestionTimeout);
    const query = this.value.trim();

    if (query.length < 2) {
        suggestionsDropdown.classList.add('hidden');
        return;
    }

    suggestionTimeout = setTimeout(() => {
        fetch(`/api/suggestions?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    suggestionsDropdown.classList.add('hidden');
                    return;
                }

                suggestionsDropdown.innerHTML = data.map(suggestion => `
                    <div class="suggestion-item" onclick="selectSuggestion('${suggestion.city.replace(/'/g, "\\'")}')">
                        <div class="suggestion-city">${suggestion.city}</div>
                        <div class="suggestion-country">${suggestion.country}</div>
                    </div>
                `).join('');

                positionDropdown();
                suggestionsDropdown.classList.remove('hidden');
            })
            .catch(() => suggestionsDropdown.classList.add('hidden'));
    }, 300);
});

// Reposition on input focus
cityInput.addEventListener('focus', function() {
    if (!suggestionsDropdown.classList.contains('hidden')) {
        positionDropdown();
    }
});

// Reposition on window resize
window.addEventListener('resize', function() {
    if (!suggestionsDropdown.classList.contains('hidden')) {
        positionDropdown();
    }
});

function selectSuggestion(city) {
    cityInput.value = city;
    suggestionsDropdown.classList.add('hidden');
    document.getElementById('searchForm').submit();
}

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container') && e.target !== suggestionsDropdown) {
        suggestionsDropdown.classList.add('hidden');
    }
});

// Enter key submission and loading state
document.getElementById('searchForm').addEventListener('submit', function(e) {
    const searchBtn = document.getElementById('searchBtn');
    const searchBtnText = document.getElementById('searchBtnText');
    const searchBtnSpinner = document.getElementById('searchBtnSpinner');
    const searchBtnIcon = document.getElementById('searchBtnIcon');

    searchBtn.disabled = true;
    searchBtnText.textContent = 'Loading...';
    searchBtnSpinner.classList.remove('hidden');
    searchBtnIcon.classList.add('hidden');
});

cityInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('searchForm').submit();
    }
});
</script>

</body>
</html>