<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGOS Citizen</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/agos-favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/agos-favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="citizen-body">
    <div class="citizen-app" data-citizen-app data-initial-screen="{{ $screen ?? 'home' }}" data-csrf-token="{{ csrf_token() }}">
        <div class="citizen-device-notch" aria-hidden="true"></div>
        <div class="citizen-viewport">
        <header class="citizen-header">
            <div class="citizen-header-slot citizen-header-slot-left">
                <button type="button" class="citizen-header-button" data-citizen-menu aria-label="Open menu">
                    <span class="sr-only">Menu</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
            <a href="{{ route('citizen.home') }}" class="citizen-brand" data-citizen-nav="home">
                <img src="{{ asset('images/agos-logo.png') }}" alt="AGOS - Bantay Agos, Ligtas Barangay" class="agos-brand-logo">
            </a>
            <div class="citizen-header-actions citizen-header-slot citizen-header-slot-right">
                <button type="button" class="citizen-header-button" data-theme-toggle aria-label="Switch to light mode">
                    <svg data-theme-icon="moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    <svg data-theme-icon="sun" class="hidden" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                </button>
                <a href="{{ route('citizen.alerts') }}" class="citizen-header-button" data-citizen-alerts aria-label="Open alerts">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                @if (session('status') || (! empty($broadcastAlerts) && $broadcastAlerts->isNotEmpty()))
                    <i class="citizen-notification-dot" aria-label="New notification"></i>
                @endif
                </a>
            </div>
        </header>
        <div class="citizen-sidebar-overlay" data-citizen-sidebar-overlay></div>
        <aside class="citizen-sidebar" data-citizen-sidebar aria-hidden="true">
            <div class="citizen-sidebar-heading"><strong>AGOS Citizen</strong><button type="button" data-citizen-menu-close aria-label="Close menu">×</button></div>
            <nav>
                <a href="{{ route('citizen.home') }}">⌂ <span>Home</span></a>
                <a href="{{ route('citizen.map') }}">▰ <span>Map</span></a>
                <a href="{{ route('citizen.report') }}">＋ <span>Report</span></a>
                <a href="{{ route('citizen.alerts') }}">♟ <span>Alerts</span></a>
                <a href="{{ route('citizen.profile') }}">☻ <span>Profile</span></a>
                <form action="{{ route('citizen.logout') }}" method="POST">
                    @csrf
                    <button type="submit">↪ <span>Log Out</span></button>
                </form>
            </nav>
        </aside>

        <main class="citizen-main">
            @if (session('status'))
                <div class="citizen-toast citizen-toast-success" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="citizen-toast citizen-toast-error" role="alert">Please check the highlighted fields and try again.</div>
            @endif

            <section class="citizen-screen {{ ($screen ?? 'home') === 'home' ? 'is-active' : '' }}" data-citizen-screen="home">
                <div class="citizen-location">
                    <div>
                        <strong>Davao City</strong>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg> Current Location</span>
                    </div>
                </div>

                @if (! empty($broadcastAlerts) && $broadcastAlerts->isNotEmpty())
                    @php
                        $latestBroadcast = $broadcastAlerts->first();
                        $isCrit = $latestBroadcast->severity === 'CRITICAL';
                        $scopeText = $latestBroadcast->barangay ? 'Brgy. ' . $latestBroadcast->barangay->name : 'Davao City (Citywide)';
                    @endphp
                    <div
                        class="citizen-broadcast-banner citizen-alert-summary {{ $isCrit ? 'is-critical' : 'is-warning' }}"
                        data-citizen-broadcast-banner
                        data-detail-label="{{ $latestBroadcast->severity }} BROADCAST"
                        data-detail-title="{{ $latestBroadcast->title }}"
                        data-detail-body="{{ $latestBroadcast->message }}&#10;&#10;Target Area: {{ $scopeText }}"
                        data-detail-meta="Broadcast by DRRMO · {{ $latestBroadcast->created_at->diffForHumans() }}"
                    >
                        <div class="citizen-broadcast-badge-row">
                            <span class="citizen-alert-severity {{ $isCrit ? 'is-critical' : 'is-warning' }}">
                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg>
                                <span>{{ $isCrit ? 'CRITICAL FLOOD BROADCAST' : 'FLOOD ADVISORY' }}</span>
                            </span>
                            <small class="tabular-nums">{{ $latestBroadcast->created_at->diffForHumans() }}</small>
                        </div>
                        <strong>{{ $latestBroadcast->title }}</strong>
                        <p>{{ $latestBroadcast->message }}</p>
                        <div class="citizen-broadcast-footer">
                            <span class="inline-flex items-center gap-1.5"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $scopeText }}</span>
                            <a href="{{ route('citizen.alerts') }}" data-citizen-nav="alerts">View all alerts <svg aria-hidden="true" viewBox="0 0 20 20"><path d="M4 10h11m-5-5 5 5-5 5"/></svg></a>
                        </div>
                    </div>
                @endif

                <div class="citizen-weather" data-citizen-weather data-detail-label="WEATHER UPDATE" data-detail-title="Today's Weather" data-detail-body="Live weather conditions for Davao City, including current temperature and rain probability." data-detail-meta="Updated from WeatherAPI">
                    <div><span class="weather-icon" data-citizen-weather-icon>☁</span><strong data-citizen-temperature>Loading…</strong><small data-citizen-condition>Weather update</small></div>
                    <div><span class="weather-icon">◐</span><strong data-citizen-rain>Loading…</strong><small>Rain Chance</small></div>
                </div>
                <article class="citizen-risk-card" data-detail-label="RISK STATUS" data-detail-title="Flood Risk Near You" data-detail-body="Water levels in major river systems remain normal. No immediate threat of flooding has been reported in your registered area." data-detail-meta="Current risk: Low">
                    <div class="citizen-card-heading"><span class="risk-symbol">▲</span><strong>Flood Risk Near You</strong><span class="citizen-pill pill-green">LOW RISK</span></div>
                    <p>Water level in major river systems remain normal. No immediate threat of flooding in your registered area.</p>
                    <div class="risk-scale"><i></i><i></i><i></i><i></i><div><span>Normal</span><span>Alert</span><span>Alarm</span><span>Critical</span></div></div>
                </article>
                <a href="{{ route('citizen.report') }}" class="citizen-report-cta" data-citizen-nav="report">
                    <span class="cta-icon">▣</span><span><strong>See Flooding in Your Area</strong><small>Help others by reporting current water levels</small></span><b>→</b>
                </a>
                <a href="{{ route('citizen.map') }}" class="citizen-map-preview" aria-label="Open live flood map">
                    <div class="citizen-map-preview-leaflet" data-citizen-preview-map data-boundary-url="{{ asset('data/davao-barangays.geojson') }}" data-reports-url="{{ route('citizen.map.reports') }}"></div>
                </a>
                <div class="citizen-section-title"><strong>Nearby Activity</strong><a href="{{ route('citizen.map') }}">View All</a></div>
                <div class="citizen-activity-list">
                    <article data-detail-label="VALIDATED REPORT" data-detail-title="Rizal Street" data-detail-body="Gutter-deep water reported near the train intersection. Passable for light vehicles but proceed with caution." data-detail-meta="Validated · 10 minutes ago"><div><span class="citizen-pill pill-green">● VALIDATED</span><small>10 min ago</small></div><strong>Rizal Street</strong><p>Gutter-deep water reported near the train intersection. Passable for light vehicles but...</p></article>
                    <article data-detail-label="MONITORING REPORT" data-detail-title="Brgy. Matina" data-detail-body="Water level is rising at Matina Crossing rear sensor. Monitor conditions and avoid low-lying roads." data-detail-meta="Monitoring · 45 minutes ago"><div><span class="citizen-pill pill-orange">◉ MONITORING</span><small>45 min ago</small></div><strong>Brgy. Matina</strong><p>Water level rising at Matina Crossing rear sensor.</p></article>
                    <article data-detail-label="OFFICIAL ADVISORY" data-detail-title="Brgy. Talomo" data-detail-body="Scheduled canal clearing operations today. Expect minor traffic build-up along the affected route." data-detail-meta="Official advisory · 2 hours ago"><div><span class="citizen-pill pill-blue">⚑ OFFICIAL ADVISORY</span><small>2h ago</small></div><strong>Brgy. Talomo</strong><p>Scheduled canal clearing operations today. Expect minor traffic build-up along the...</p></article>
                </div>
            </section>

            <section class="citizen-screen {{ ($screen ?? 'home') === 'map' ? 'is-active' : '' }}" data-citizen-screen="map">
                <div class="citizen-screen-title"><button type="button" class="citizen-subheader-button" data-citizen-nav="home">←</button><h1>LIVE FLOOD MAP</h1><button type="button" class="citizen-subheader-button" data-citizen-profile>◎</button></div>
                <div class="citizen-heatmap-filters">
                    <select data-citizen-year-filter data-selected-year="{{ now()->year }}" aria-label="Filter flood heatmap by year">
                        <option value="">All years</option>
                        <option value="{{ now()->year }}" selected>{{ now()->year }}</option>
                    </select>
                    <select data-citizen-month-filter data-selected-month="{{ now()->month }}" aria-label="Filter flood heatmap by month">
                        <option value="">All months</option>
                        <option value="{{ now()->month }}" selected>{{ now()->format('F') }}</option>
                    </select>
                </div>
                <div class="citizen-map-large">
                    <div class="citizen-map-leaflet" data-citizen-map data-boundary-url="{{ asset('data/davao-barangays.geojson') }}" data-reports-url="{{ route('citizen.map.reports') }}" data-search-url="{{ route('citizen.map.search') }}"></div>
                    <label class="map-search">⌕ <input type="search" data-map-search placeholder="Search location or area..." aria-label="Search location or area"><b data-map-location>♟</b></label>
                    <div class="map-search-results" data-map-search-results></div>
                    <div class="citizen-heatmap-status" data-citizen-heatmap-status>Loading verified flood heatmap…</div>
                </div>
                <div class="citizen-section-title"><strong>Nearby Activity</strong><a href="{{ route('citizen.map') }}">View All</a></div>
                <div class="map-activity-list">
                    <article data-detail-label="CRITICAL REPORT" data-detail-title="Main St. Underpass" data-detail-body="Severe flooding reported. The road is currently impassable. Avoid the area and follow emergency advisories." data-detail-meta="Critical · 10 minutes ago"><div><strong>Main St. Underpass</strong><small>0.5 km</small></div><p>♦ &nbsp; Severe flooding reported. Impassable fo...</p><span class="status-critical">Critical</span><small>◷ &nbsp; 10 min ago</small></article>
                    <article data-detail-label="MONITORING REPORT" data-detail-title="Rizal Ave. Extension" data-detail-body="Gutter-deep water reported. Drive with caution and allow extra travel time." data-detail-meta="Monitoring · 35 minutes ago"><div><strong>Rizal Ave. Extension</strong><small>1.2 km</small></div><p>▲ &nbsp; Gutter deep water. Drive with caution.</p><span class="status-monitoring">Monitoring</span><small>◷ &nbsp; 35 min ago</small></article>
                    <article data-detail-label="ROAD STATUS" data-detail-title="Quezon Boulevard" data-detail-body="Water has subsided and the road is now clear." data-detail-meta="Safe · 1 hour ago"><div><strong>Quezon Boulevard</strong><small>2.0 km</small></div><p>✓ &nbsp; Water has subsided. Road is now clear.</p><span class="status-safe">Safe</span><small>◷ &nbsp; 1h ago</small></article>
                </div>
            </section>

            <section class="citizen-screen {{ ($screen ?? 'home') === 'report' ? 'is-active' : '' }}" data-citizen-screen="report">
                <div class="citizen-screen-title report-title"><button type="button" class="citizen-subheader-button" data-citizen-nav="home">←</button><h1>REPORT FLOODING</h1><span class="citizen-subheader-spacer"></span></div>
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

            <section class="citizen-screen citizen-alerts-screen {{ ($screen ?? 'home') === 'alerts' ? 'is-active' : '' }}" data-citizen-screen="alerts">
                <div class="citizen-screen-title"><button type="button" class="citizen-subheader-button" data-citizen-nav="home">←</button><h1>ALERT / NOTIFICATION</h1><span class="citizen-subheader-spacer"></span></div>
                <div class="barangay-title">MONITORED REGIONS</div>
                <div class="barangay-tags">
                    @if (auth()->user()->barangay)
                        <span data-barangay-tag>Brgy. {{ auth()->user()->barangay->name }}</span>
                    @endif
                    <span data-barangay-tag>Davao City (Citywide)</span>
                </div>
                <div class="alert-list" data-citizen-alert-list>
                    @forelse ($broadcastAlerts as $alert)
                        @php
                            $badgeClass = match($alert->severity) {
                                'CRITICAL' => 'badge-red',
                                'WARNING' => 'badge-orange',
                                'ADVISORY' => 'badge-blue',
                                default => 'badge-green',
                            };
                            $badgeSymbol = match($alert->severity) {
                                'CRITICAL' => 'CRITICAL BROADCAST',
                                'WARNING' => 'WARNING',
                                'ADVISORY' => 'ADVISORY',
                                default => 'NOTICE',
                            };
                            $scopeName = $alert->barangay ? 'Brgy. ' . $alert->barangay->name : 'Davao City (Citywide)';
                        @endphp
                        <article
                            data-detail-label="{{ $alert->severity }} BROADCAST"
                            data-detail-title="{{ $alert->title }}"
                            data-detail-body="{{ $alert->message }}&#10;&#10;Target Area: {{ $scopeName }}"
                            data-detail-meta="Broadcast by DRRMO Admin · {{ $alert->created_at->diffForHumans() }}"
                            class="broadcast-alert-card {{ $alert->severity === 'CRITICAL' ? 'border-critical' : '' }}"
                        >
                            <div>
                                <span class="alert-badge citizen-alert-severity {{ $badgeClass }}">
                                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg>
                                    <span>{{ $badgeSymbol }}</span>
                                </span>
                                <small class="tabular-nums">{{ $alert->created_at->diffForHumans() }}</small>
                            </div>
                            <strong>{{ $alert->title }}</strong>
                            <p>{{ $alert->message }}</p>
                            <div class="alert-meta-row">
                                <span class="inline-flex items-center gap-1.5"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $scopeName }}</span>
                                <span class="alert-official-tag"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/></svg>DRRMO Official Broadcast</span>
                            </div>
                        </article>
                    @empty
                        <div class="empty-alerts-placeholder">
                            <svg class="empty-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                            <strong>No Broadcast Alerts</strong>
                            <p>There are currently no active emergency broadcasts for {{ auth()->user()->barangay?->name ?? 'Davao City' }}.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="citizen-screen {{ ($screen ?? 'home') === 'profile' ? 'is-active' : '' }}" data-citizen-screen="profile">
                <div class="citizen-screen-title"><button type="button" class="citizen-subheader-button" data-citizen-nav="home">←</button><h1>PROFILE</h1><span class="citizen-subheader-spacer"></span></div>
                @php
                    $profileName = trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? ''));
                    $profileName = $profileName !== '' ? $profileName : (auth()->user()->name ?? 'Citizen Reporter');
                    $profileWords = preg_split('/\s+/', $profileName, -1, PREG_SPLIT_NO_EMPTY);
                    $profileInitials = strtoupper(substr($profileWords[0] ?? 'C', 0, 1) . substr($profileWords[count($profileWords) - 1] ?? 'R', 0, 1));
                @endphp
                <div class="profile-card" data-citizen-profile-card>
                    <div class="profile-identity">
                        <div class="profile-avatar" data-profile-initials>{{ $profileInitials }}</div>
                        <div>
                            <p class="profile-name" data-profile-name>{{ $profileName }}</p>
                            <p class="profile-role-label">Citizen Reporter</p>
                        </div>
                    </div>
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
                    <article class="my-report" data-detail-label="SUBMITTED REPORT" data-detail-title="{{ str_replace('_', ' ', ucfirst($report->water_level_status)) }} Flooding" data-detail-body="{{ $report->report_text }}" data-detail-meta="{{ $report->barangay?->name ?? 'Barangay pending' }} · {{ $report->created_at->diffForHumans() }}"><span>♦</span><div><strong>{{ str_replace('_', ' ', ucfirst($report->water_level_status)) }} Flooding</strong><small>{{ $report->barangay?->name ?? 'Barangay pending' }}</small></div><small>{{ $report->created_at->diffForHumans() }}</small></article>
                @empty
                    <article class="my-report"><div><strong>No reports yet</strong><small>Your submitted reports will appear here.</small></div></article>
                @endforelse
                <form action="{{ route('citizen.logout') }}" method="POST" class="citizen-logout-form">
                    @csrf
                    <button type="submit">Log Out</button>
                </form>
            </section>
        </main>
        <div class="citizen-detail-modal" data-citizen-detail-modal aria-hidden="true">
            <div class="citizen-detail-card" role="dialog" aria-modal="true" aria-labelledby="citizen-detail-title">
                <button type="button" class="citizen-detail-close" data-detail-close aria-label="Close details">×</button>
                <span class="citizen-pill pill-blue" data-detail-modal-label>DETAIL</span>
                <h2 id="citizen-detail-title" data-detail-modal-title></h2>
                <p data-detail-modal-body></p>
                <small data-detail-modal-meta></small>
            </div>
        </div>

        <nav class="citizen-bottom-nav">
            <a href="{{ route('citizen.home') }}" data-citizen-nav="home" class="{{ ($screen ?? 'home') === 'home' ? 'is-active' : '' }}"><span>⌂</span>Home</a>
            <a href="{{ route('citizen.map') }}" data-citizen-nav="map" class="{{ ($screen ?? 'home') === 'map' ? 'is-active' : '' }}"><span>▰</span>Map</a>
            <a href="{{ route('citizen.report') }}" data-citizen-nav="report" class="{{ ($screen ?? 'home') === 'report' ? 'is-active' : '' }}"><span>＋</span>Report</a>
            <a href="{{ route('citizen.alerts') }}" data-citizen-nav="alerts" class="{{ ($screen ?? 'home') === 'alerts' ? 'is-active' : '' }}"><span>♟</span>Alerts</a>
            <a href="{{ route('citizen.profile') }}" data-citizen-nav="profile" class="{{ ($screen ?? 'home') === 'profile' ? 'is-active' : '' }}"><span>☻</span>Profile</a>
        </nav>
        </div>
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
