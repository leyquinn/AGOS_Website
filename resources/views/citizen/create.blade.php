<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGOS Citizen</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="citizen-body">
    <div class="citizen-app" data-citizen-app data-csrf-token="{{ csrf_token() }}">
        <header class="citizen-header">
            <button type="button" class="citizen-header-button" data-citizen-menu aria-label="Open menu">
                <span class="sr-only">Menu</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <a href="{{ route('citizen.create') }}" class="citizen-brand" data-citizen-nav="home">
                <img src="{{ asset('images/agos-logo.png') }}" alt="AGOS">
            </a>
            <div class="citizen-header-actions">
                <button type="button" class="citizen-header-button" data-theme-toggle aria-label="Switch to light mode">
                    <svg data-theme-icon="moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    <svg data-theme-icon="sun" class="hidden" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                </button>
                <button type="button" class="citizen-header-button" data-citizen-alerts aria-label="Open alerts">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                </button>
            </div>
        </header>

        <main class="citizen-main">
            @if (session('status'))
                <div class="citizen-toast citizen-toast-success" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="citizen-toast citizen-toast-error" role="alert">Please check the highlighted fields and try again.</div>
            @endif

            <section class="citizen-screen is-active" data-citizen-screen="home">
                <div class="citizen-location">
                    <div>
                        <strong>Davao City</strong>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg> Current Location</span>
                    </div>
                </div>
                <div class="citizen-weather">
                    <div><span class="weather-icon">☁</span><strong>32°C</strong><small>Mostly Cloudy</small></div>
                    <div><span class="weather-icon">◐</span><strong>20%</strong><small>Rain Chance</small></div>
                </div>
                <article class="citizen-risk-card">
                    <div class="citizen-card-heading"><span class="risk-symbol">▲</span><strong>Flood Risk Near You</strong><span class="citizen-pill pill-green">LOW RISK</span></div>
                    <p>Water level in major river systems remain normal. No immediate threat of flooding in your registered area.</p>
                    <div class="risk-scale"><i></i><i></i><i></i><i></i><div><span>Normal</span><span>Alert</span><span>Alarm</span><span>Critical</span></div></div>
                </article>
                <button type="button" class="citizen-report-cta" data-citizen-nav="report">
                    <span class="cta-icon">▣</span><span><strong>See Flooding in Your Area</strong><small>Help others by reporting current water levels</small></span><b>→</b>
                </button>
                <div class="citizen-map-preview">
                    <div class="map-pin">●</div><span class="map-label">Open Live Map <b>›</b></span>
                </div>
                <div class="citizen-section-title"><strong>Nearby Activity</strong><button type="button" data-citizen-nav="map">View All</button></div>
                <div class="citizen-activity-list">
                    <article><div><span class="citizen-pill pill-green">● VALIDATED</span><small>10 min ago</small></div><strong>Rizal Street</strong><p>Gutter-deep water reported near the train intersection. Passable for light vehicles but...</p></article>
                    <article><div><span class="citizen-pill pill-orange">◉ MONITORING</span><small>45 min ago</small></div><strong>Brgy. Matina</strong><p>Water level rising at Matina Crossing rear sensor.</p></article>
                    <article><div><span class="citizen-pill pill-blue">⚑ OFFICIAL ADVISORY</span><small>2h ago</small></div><strong>Brgy. Talomo</strong><p>Scheduled canal clearing operations today. Expect minor traffic build-up along the...</p></article>
                </div>
            </section>

            <section class="citizen-screen" data-citizen-screen="map">
                <div class="citizen-screen-title"><button type="button" data-citizen-nav="home">←</button><h1>LIVE FLOOD MAP</h1><button type="button" data-citizen-profile>◎</button></div>
                <div class="citizen-map-large">
                    <div class="map-road road-one"></div><div class="map-road road-two"></div><div class="map-road road-three"></div><div class="map-water"></div>
                    <label class="map-search">⌕ <input type="text" placeholder="Search location or area..." aria-label="Search location or area"><b>♟</b></label>
                    <i class="map-dot dot-red"></i><i class="map-dot dot-orange"></i><i class="map-dot dot-blue"></i><i class="map-dot dot-yellow"></i>
                </div>
                <div class="citizen-section-title"><strong>Nearby Activity</strong><button type="button" data-citizen-nav="map">View All</button></div>
                <div class="map-activity-list">
                    <article><div><strong>Main St. Underpass</strong><small>0.5 km</small></div><p>♦ &nbsp; Severe flooding reported. Impassable fo...</p><span class="status-critical">Critical</span><small>◷ &nbsp; 10 min ago</small></article>
                    <article><div><strong>Rizal Ave. Extension</strong><small>1.2 km</small></div><p>▲ &nbsp; Gutter deep water. Drive with caution.</p><span class="status-monitoring">Monitoring</span><small>◷ &nbsp; 35 min ago</small></article>
                    <article><div><strong>Quezon Boulevard</strong><small>2.0 km</small></div><p>✓ &nbsp; Water has subsided. Road is now clear.</p><span class="status-safe">Safe</span><small>◷ &nbsp; 1h ago</small></article>
                </div>
            </section>

            <section class="citizen-screen" data-citizen-screen="report">
                <div class="citizen-screen-title report-title"><button type="button" data-citizen-nav="home">←</button><h1>REPORT FLOODING</h1><span></span></div>
                <div class="citizen-report-intro">Help your community stay safe.<span>◷ &nbsp; Takes 2 mins</span></div>
                <div class="validation-card"><strong>♣ &nbsp; Collaborative Validation</strong><p>Your report will be combined with other nearby to verify flood levels accurately.</p></div>
                <form action="{{ route('citizen.reports.store') }}" method="POST" class="citizen-form">
                    @csrf
                    <input type="hidden" name="latitude" data-report-latitude>
                    <input type="hidden" name="longitude" data-report-longitude>
                    <h2>Location</h2>
                    <input type="hidden" name="barangay_id" value="{{ auth()->user()->barangay_id }}">
                    <label class="current-location-field" data-location-control>
                        <span>◉</span><span>Current Location<small data-location-label>{{ auth()->user()->barangay?->name ?? 'Location permission not granted' }}</small></span>
                        <button type="button" data-use-current-location>Edit</button>
                    </label>
                    <small class="citizen-location-help" data-location-status>Tap Edit to allow device location and pin your exact position.</small>
                    <label class="citizen-field"><span>Nearest Landmark (Optional)</span><input name="location" value="{{ old('location') }}" required placeholder="e.g San Jose Church"></label>
                    @error('location')<small class="citizen-error">{{ $message }}</small>@enderror
                    <h2>Water Level</h2>
                    <div class="water-levels">
                        @foreach ([['value' => 'ankle', 'label' => 'Ankle', 'icon' => '◕'], ['value' => 'knee', 'label' => 'Knee-Deep', 'icon' => '≋'], ['value' => 'waist', 'label' => 'Waist-Deep', 'icon' => '≋'], ['value' => 'chest', 'label' => 'Chest-Deep', 'icon' => '≋'], ['value' => 'roof', 'label' => 'Roof-Top', 'icon' => '≋'], ['value' => 'impassable', 'label' => 'Impassable', 'icon' => '≋']] as $level)
                            <label><input type="radio" name="water_level" value="{{ $level['value'] }}" @checked(old('water_level') === $level['value']) required><span>{{ $level['icon'] }}</span>{{ $level['label'] }}</label>
                        @endforeach
                    </div>
                    @error('water_level')<small class="citizen-error">{{ $message }}</small>@enderror
                    <h2>Details</h2>
                    <label class="citizen-field"><span>Describe What You See</span><textarea name="description" required maxlength="2000" placeholder="e.g Water is rising quickly, road is completely covered">{{ old('description') }}</textarea></label>
                    @error('description')<small class="citizen-error">{{ $message }}</small>@enderror
                    <div class="citizen-extra-fields">
                        <label class="citizen-field"><span>Your name (optional)</span><input name="reporter_name" value="{{ old('reporter_name') }}"></label>
                        <label class="citizen-field"><span>Contact number (optional)</span><input name="reporter_contact" value="{{ old('reporter_contact') }}"></label>
                    </div>
                    <button type="submit" class="submit-report">Submit Report</button>
                </form>
            </section>

            <section class="citizen-screen" data-citizen-screen="alerts">
                <div class="citizen-screen-title"><button type="button" data-citizen-nav="home">←</button><h1>ALERT / NOTIFICATION</h1><span></span></div>
                <div class="barangay-title">MANAGE YOUR BARANGAYS</div>
                <div class="barangay-tags">
                    <span data-barangay-tag>Poblacion <button type="button" data-remove-barangay aria-label="Remove Poblacion">×</button></span>
                    <span data-barangay-tag>Matina <button type="button" data-remove-barangay aria-label="Remove Matina">×</button></span>
                    <span data-barangay-tag>Talomo <button type="button" data-remove-barangay aria-label="Remove Talomo">×</button></span>
                    <button type="button">＋ Add Barangay</button>
                </div>
                <div class="alert-list">
                    <article><div><span class="alert-badge badge-red">● VALIDATED</span><small>4 min ago</small></div><strong>Flash Flood Warning: Poblacion</strong><p>Water level is rising rapidly in downtown area. Immediate evacuation recommended for all low-lying zones near river.</p></article>
                    <article><div><span class="alert-badge badge-blue">⚑ OFFICIAL NOTICE</span><small>22 min ago</small></div><strong>Road Closure: Matina Crossing</strong><p>McArthur Highway is temporarily closed due to severe water pooling. Please use alternate routes via Diversion Road.</p></article>
                    <article><div><span class="alert-badge badge-orange">◉ MONITORING</span><small>1h ago</small></div><strong>Heavy Rainfall: Talomo District</strong><p>Continuous moderate to heavy rainfall observed. Localized flooding is possible in urbanized areas. Remain vigilant.</p></article>
                    <article><div><span class="alert-badge badge-green">● ALL CLEAR</span><small>3 min ago</small></div><strong>Storm Signal Lifted: Citywide</strong><p>Weather conditions have normalized. All public transport services and municipal offices have resumed regular operations.</p></article>
                </div>
            </section>

            <section class="citizen-screen" data-citizen-screen="profile">
                <div class="citizen-screen-title"><button type="button" data-citizen-nav="home">←</button><h1>PROFILE</h1><span></span></div>
                <div class="profile-card" data-citizen-profile-card>
                    <div class="profile-avatar" data-profile-initials>●</div>
                    <form action="{{ route('citizen.profile.update') }}" method="POST" class="citizen-profile-form">
                        @csrf
                        <label class="citizen-field"><span>Full name</span><input name="name" value="{{ trim(auth()->user()->first_name.' '.auth()->user()->last_name) }}" required></label>
                        <label class="citizen-field"><span>Assigned barangay</span>
                            <select name="barangay_id" required>
                                <option value="">Select your barangay</option>
                                @foreach ($barangays as $barangay)
                                    <option value="{{ $barangay->id }}" @selected(auth()->user()->barangay_id === $barangay->id)>{{ $barangay->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit">Save Profile</button>
                    </form>
                    <p data-profile-location>⌖ &nbsp; {{ auth()->user()->barangay?->name ?? 'Barangay not assigned' }}</p>
                    <p data-profile-email>Loading email…</p>
                    <p data-profile-phone>Loading phone number…</p>
                    <span class="contributor-badge">♟ &nbsp; <span data-profile-role>CONTRIBUTOR</span></span>
                    <p class="profile-copy">Committed to community safety. Active reporter in flood-prone areas of Davao City.</p>
                </div>
                <div class="citizen-section-title profile-reports-title"><strong>My Reports</strong><span>{{ $citizenReports->count() }} total</span></div>
                @forelse ($citizenReports as $report)
                    <article class="my-report"><span>♦</span><div><strong>{{ str_replace('_', ' ', ucfirst($report->water_level_status)) }} Flooding</strong><small>{{ $report->barangay?->name ?? 'Barangay pending' }}</small></div><small>{{ $report->created_at->diffForHumans() }}</small></article>
                @empty
                    <article class="my-report"><div><strong>No reports yet</strong><small>Your submitted reports will appear here.</small></div></article>
                @endforelse
                <form action="{{ route('citizen.logout.get') }}" method="GET" class="citizen-logout-form"><button type="submit">Log Out</button></form>
            </section>
        </main>

        <nav class="citizen-bottom-nav">
            <button type="button" data-citizen-nav="home" class="is-active"><span>⌂</span>Home</button>
            <button type="button" data-citizen-nav="map"><span>▰</span>Map</button>
            <button type="button" data-citizen-nav="report"><span>＋</span>Report</button>
            <button type="button" data-citizen-nav="alerts"><span>♟</span>Alerts</button>
            <button type="button" data-citizen-nav="profile"><span>☻</span>Profile</button>
        </nav>
    </div>
    <script>
        window.addEventListener('pageshow', async (event) => {
            if (!event.persisted) {
                return;
            }

            try {
                const response = await fetch('{{ url('/api/v1/user') }}', {
                    headers: { Accept: 'application/json' },
                    credentials: 'include',
                    cache: 'no-store',
                });

                if (response.status === 401) {
                    window.location.replace('{{ route('landing') }}');
                }
            } catch {
                window.location.replace('{{ route('landing') }}');
            }
        });
    </script>
</body>
</html>
