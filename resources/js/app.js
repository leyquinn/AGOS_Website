import './bootstrap';
import Alpine from 'alpinejs';
import './dashboard-charts';
import './model-analytics';
import './map';

window.Alpine = Alpine;
Alpine.start();

const weatherCard = document.querySelector('[data-weather-card]');
if (weatherCard) {
    const refreshButton = document.querySelector('[data-weather-refresh]');
    const temperature = weatherCard.querySelector('[data-weather-temperature]');
    const range = weatherCard.querySelector('[data-weather-range]');
    const rain = weatherCard.querySelector('[data-weather-rain]');
    const signal = weatherCard.querySelector('[data-weather-signal]');
    const icon = weatherCard.querySelector('[data-weather-icon]');
    const condition = weatherCard.querySelector('[data-weather-condition]');
    const updated = weatherCard.querySelector('[data-weather-updated]');
    const setLoading = (isLoading) => {
        refreshButton?.toggleAttribute('disabled', isLoading);
        refreshButton?.classList.toggle('opacity-60', isLoading);
        if (isLoading) signal.textContent = 'Loading…';
    };
    const loadWeather = async () => {
        setLoading(true);
        try {
            const response = await fetch('/weather', { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) throw new Error(`Weather request failed with status ${response.status}`);
            const data = await response.json();
            temperature.textContent = `${data.temperature}°C`;
            condition.textContent = data.condition;
            range.textContent = `${data.high}° / ${data.low}°`;
            rain.textContent = `${data.rain_chance}%`;
            signal.textContent = data.rainfall_signal;
            signal.className = `mt-2 inline-block rounded-lg px-3 py-1 text-xs font-bold ${
                data.rain_chance >= 70 ? 'bg-amber-400 text-amber-950' : 'bg-emerald-500 text-emerald-950'
            }`;
            icon.src = data.icon_url.startsWith('//') ? `https:${data.icon_url}` : data.icon_url;
            icon.alt = data.condition;
            icon.hidden = !data.icon_url;
            const fetchedAt = new Date(data.fetched_at);
            const providerUpdatedAt = new Date(data.updated_at.replace(' ', 'T'));
            updated.textContent = `Fetched ${fetchedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} · Source updated ${providerUpdatedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
        } catch (error) {
            console.error('Unable to load weather data.', error);
            temperature.textContent = 'Unavailable';
            condition.textContent = 'Weather service unavailable';
            range.textContent = '—';
            rain.textContent = '—';
            signal.textContent = 'Unavailable';
            signal.className = 'mt-2 inline-block rounded-lg bg-slate-500 px-3 py-1 text-xs font-bold text-white';
            updated.textContent = 'Weather service unavailable';
            icon.hidden = true;
        } finally {
            setLoading(false);
        }
    };
    refreshButton?.addEventListener('click', loadWeather);
    loadWeather();
}

const citizenWeather = document.querySelector('[data-citizen-weather]');
if (citizenWeather) {
    const temperature = citizenWeather.querySelector('[data-citizen-temperature]');
    const condition = citizenWeather.querySelector('[data-citizen-condition]');
    const rain = citizenWeather.querySelector('[data-citizen-rain]');
    const icon = citizenWeather.querySelector('[data-citizen-weather-icon]');

    fetch('/citizen/weather', {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
        cache: 'no-store',
    })
        .then((response) => {
            if (!response.ok) throw new Error(`Citizen weather request failed with status ${response.status}`);
            return response.json();
        })
        .then((data) => {
            temperature.textContent = `${data.temperature}°C`;
            condition.textContent = data.condition;
            rain.textContent = `${data.rain_chance}%`;
            if (data.icon_url) {
                icon.textContent = '';
                icon.style.backgroundImage = `url(${data.icon_url.startsWith('//') ? `https:${data.icon_url}` : data.icon_url})`;
                icon.classList.add('weather-image-icon');
            }
        })
        .catch((error) => {
            console.error('Unable to load citizen weather data.', error);
            temperature.textContent = 'Unavailable';
            condition.textContent = 'Weather unavailable';
            rain.textContent = '—';
        });
}

document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const carouselId = carousel.id;
    const track = carousel.querySelector('[data-carousel-track]');
    const previousButton = document.querySelector(`[data-carousel-prev="${carouselId}"]`);
    const nextButton = document.querySelector(`[data-carousel-next="${carouselId}"]`);
    const cards = track ? Array.from(track.children) : [];
    let currentIndex = 0;
    let autoAdvance;

    const getMetrics = () => {
        const firstCard = cards[0];
        const gap = Number.parseFloat(getComputedStyle(track).columnGap) || 0;
        const cardWidth = firstCard?.getBoundingClientRect().width || carousel.clientWidth;
        const visibleCards = Math.max(1, Math.floor((carousel.clientWidth + gap) / (cardWidth + gap)));
        return { step: cardWidth + gap, maxIndex: Math.max(0, cards.length - visibleCards) };
    };
    const renderCarousel = (index, animate = true) => {
        if (!track) {
            return;
        }
        const { step, maxIndex } = getMetrics();
        currentIndex = Math.min(Math.max(index, 0), maxIndex);
        track.classList.toggle('transition-none', !animate);
        track.style.transform = `translateX(-${currentIndex * step}px)`;
    };
    const scrollCarousel = (direction) => {
        const { maxIndex } = getMetrics();
        const nextIndex = currentIndex + direction;
        renderCarousel(nextIndex > maxIndex ? 0 : nextIndex < 0 ? maxIndex : nextIndex);
        window.clearTimeout(autoAdvance);
        autoAdvance = window.setTimeout(advanceCarousel, 5200);
    };
    const advanceCarousel = () => {
        const { maxIndex } = getMetrics();
        renderCarousel(currentIndex >= maxIndex ? 0 : currentIndex + 1);
        autoAdvance = window.setTimeout(advanceCarousel, 5200);
    };

    previousButton?.addEventListener('click', () => scrollCarousel(-1));
    nextButton?.addEventListener('click', () => scrollCarousel(1));
    window.addEventListener('resize', () => renderCarousel(currentIndex, false));
    renderCarousel(0, false);
    autoAdvance = window.setTimeout(advanceCarousel, 5200);
});

const scrollFadeElements = document.querySelectorAll('[data-scroll-fade]');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (scrollFadeElements.length && !prefersReducedMotion && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('scroll-reveal-ready');

    const scrollFadeObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            entry.target.classList.toggle('is-visible', entry.isIntersecting);
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -8% 0px',
    });

    scrollFadeElements.forEach((element) => scrollFadeObserver.observe(element));
}

const themeStorageKey = 'agos-theme';
const root = document.documentElement;
const isStoredLightTheme = localStorage.getItem(themeStorageKey) === 'light';
root.classList.toggle('theme-light', isStoredLightTheme);
document.body.classList.toggle('theme-light', isStoredLightTheme);

const themeToggles = document.querySelectorAll('[data-theme-toggle]');
const mobileMenuButton = document.querySelector('[data-mobile-menu]');
const mobileMenuPanel = document.querySelector('[data-mobile-menu-panel]');
mobileMenuButton?.addEventListener('click', () => {
    const isOpen = !mobileMenuPanel?.classList.toggle('hidden');
    mobileMenuButton.setAttribute('aria-expanded', String(isOpen));
});

const updateThemeToggle = () => {
    if (!themeToggles.length) {
        return;
    }

    const isLight = root.classList.contains('theme-light');
    themeToggles.forEach((themeToggle) => {
        themeToggle.setAttribute('aria-label', isLight ? 'Switch to dark mode' : 'Switch to light mode');
        themeToggle.setAttribute('title', isLight ? 'Switch to dark mode' : 'Switch to light mode');
        themeToggle.querySelector('[data-theme-icon="sun"]')?.classList.toggle('hidden', !isLight);
        themeToggle.querySelector('[data-theme-icon="moon"]')?.classList.toggle('hidden', isLight);
    });
};

themeToggles.forEach((themeToggle) => themeToggle.addEventListener('click', () => {
    const isLight = !root.classList.contains('theme-light');
    root.classList.toggle('theme-light', isLight);
    document.body.classList.toggle('theme-light', isLight);
    localStorage.setItem(themeStorageKey, isLight ? 'light' : 'dark');
    updateThemeToggle();
}));

updateThemeToggle();

const sidebar = document.querySelector('[data-sidebar]');
const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarCollapsedStorageKey = 'agos-sidebar-collapsed';
const openSidebar = () => {
    sidebar?.classList.remove('-translate-x-full');
    sidebarOverlay?.classList.remove('hidden');
};
const closeSidebar = () => {
    sidebar?.classList.add('-translate-x-full');
    sidebarOverlay?.classList.add('hidden');
};
document.querySelector('[data-sidebar-open]')?.addEventListener('click', openSidebar);
document.querySelector('[data-sidebar-close]')?.addEventListener('click', closeSidebar);
sidebarOverlay?.addEventListener('click', closeSidebar);
sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));
const updateSidebarToggle = () => {
    const isCollapsed = sidebar?.classList.contains('is-collapsed') ?? false;
    sidebarToggle?.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
    sidebarToggle?.setAttribute('title', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
};
if (localStorage.getItem(sidebarCollapsedStorageKey) === 'true' && window.matchMedia('(min-width: 1024px)').matches) {
    sidebar?.classList.add('is-collapsed');
}
sidebarToggle?.addEventListener('click', () => {
    const isCollapsed = sidebar?.classList.toggle('is-collapsed') ?? false;
    localStorage.setItem(sidebarCollapsedStorageKey, String(isCollapsed));
    updateSidebarToggle();
});
updateSidebarToggle();

const showToast = (message) => {
    let toast = document.querySelector('[data-portal-toast]');
    if (!toast) {
        toast = document.createElement('div');
        toast.dataset.portalToast = '';
        toast.className = 'fixed bottom-5 right-5 z-50 rounded-xl bg-blue-600 px-4 py-3 text-xs font-bold text-white shadow-xl shadow-blue-950/30';
        document.body.append(toast);
    }
    toast.textContent = message;
    toast.classList.remove('hidden');
    clearTimeout(Number(toast.dataset.timeout));
    toast.dataset.timeout = String(window.setTimeout(() => toast.classList.add('hidden'), 2200));
};

const harvestSettingsStorageKey = 'agos-harvest-settings';
const harvestIntervalLabels = {
    '1h': '1 hour',
    '2h': '2 hours',
    '6h': '6 hours',
    '12h': '12 hours',
    '24h': '24 hours',
};
const savedHarvestSettings = JSON.parse(localStorage.getItem(harvestSettingsStorageKey) || '{}');
const harvestIntervalSelect = document.querySelector('[data-harvest-interval]');
const alertThresholdSelect = document.querySelector('[data-alert-threshold]');
if (harvestIntervalSelect) harvestIntervalSelect.value = harvestIntervalLabels[savedHarvestSettings.interval] ? savedHarvestSettings.interval : '1h';
if (alertThresholdSelect) alertThresholdSelect.value = savedHarvestSettings.threshold || '0.80';
document.querySelectorAll('[data-harvest-interval-display]').forEach((display) => {
    display.textContent = harvestIntervalLabels[savedHarvestSettings.interval] || '1 hour';
});
const saveHarvestSettings = () => {
    const interval = harvestIntervalSelect?.value || '1h';
    const threshold = alertThresholdSelect?.value || '0.80';
    localStorage.setItem(harvestSettingsStorageKey, JSON.stringify({ interval, threshold }));
    document.querySelectorAll('[data-harvest-interval-display]').forEach((display) => {
        display.textContent = harvestIntervalLabels[interval];
    });
};
harvestIntervalSelect?.addEventListener('change', saveHarvestSettings);
alertThresholdSelect?.addEventListener('change', saveHarvestSettings);

const notificationsToggle = document.querySelector('[data-notifications-toggle]');
const notificationsMenu = document.querySelector('[data-notifications-menu]');
const notificationsList = document.querySelector('[data-notifications-list]');
const notificationsStatus = document.querySelector('[data-notifications-status]');
const notificationsClear = document.querySelector('[data-notifications-clear]');
const notificationStorageKey = 'agos-notifications-last-seen';
let latestNotificationId = Number(localStorage.getItem(notificationStorageKey) || 0);
let notificationsInitialized = false;
const escapeNotificationText = (value) => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[character]));

const renderNotifications = (notifications) => {
    if (!notificationsList) return;
    if (!notifications.length) return;
    notificationsList.innerHTML = notifications.map((notification) => `
        <div class="notification-item rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
            <p class="notification-item-title text-[10px] font-bold text-slate-700 dark:text-slate-200">New report from ${escapeNotificationText(notification.barangay)}</p>
            <p class="notification-item-message mt-1 truncate text-[10px] leading-relaxed text-slate-500 dark:text-slate-400">${escapeNotificationText(notification.message)}</p>
        </div>
    `).join('');
};

const loadNotifications = async () => {
    try {
        const response = await fetch('/notifications', { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
        if (!response.ok) throw new Error(`Notification request failed with status ${response.status}`);
        const { notifications } = await response.json();
        const newestId = notifications[0]?.id || 0;
        if (notificationsInitialized && newestId > latestNotificationId) {
            notificationsStatus?.classList.replace('bg-emerald-500/15', 'bg-amber-500/15');
            notificationsStatus?.classList.replace('text-emerald-600', 'text-amber-700');
            if (notificationsStatus) notificationsStatus.textContent = 'New report';
            document.querySelector('[data-notifications-toggle] span')?.classList.add('bg-amber-500');
        }
        latestNotificationId = Math.max(latestNotificationId, newestId);
        localStorage.setItem(notificationStorageKey, String(latestNotificationId));
        renderNotifications(notifications);
        notificationsInitialized = true;
    } catch (error) {
        console.error('Unable to load portal notifications.', error);
    }
};
notificationsClear?.addEventListener('click', () => {
    localStorage.setItem(notificationStorageKey, String(latestNotificationId));
    notificationsStatus?.classList.remove('bg-amber-500/15', 'text-amber-700');
    notificationsStatus?.classList.add('bg-emerald-500/15', 'text-emerald-600', 'dark:text-emerald-300');
    if (notificationsStatus) notificationsStatus.textContent = 'All clear';
    document.querySelector('[data-notifications-toggle] span')?.classList.remove('bg-amber-500');
});
notificationsToggle?.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = !notificationsMenu?.classList.toggle('hidden');
    notificationsToggle.setAttribute('aria-expanded', String(isOpen));
});
loadNotifications();
window.setInterval(loadNotifications, 30000);
document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-notifications-menu], [data-notifications-toggle]')) {
        notificationsMenu?.classList.add('hidden');
        notificationsToggle?.setAttribute('aria-expanded', 'false');
    }
});

document.querySelector('[data-logout]')?.addEventListener('click', (event) => {
    if (!window.confirm('Log out of the AGOS portal?')) {
        event.preventDefault();
    }
});

const getCitizenApiToken = () => localStorage.getItem('agos_access_token') || sessionStorage.getItem('agos_access_token');

const loadCitizenProfile = async (citizenApp) => {
    const token = getCitizenApiToken();
    const headers = { Accept: 'application/json' };
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    try {
        const response = await fetch('/api/v1/user', {
            headers,
            credentials: 'include',
        });
        if (!response.ok) {
            throw new Error(`Profile request failed with status ${response.status}`);
        }

        const user = await response.json();
        const card = citizenApp.querySelector('[data-citizen-profile-card]');
        if (!card) {
            return;
        }

        const fullName = user.full_name || `${user.first_name || ''} ${user.last_name || ''}`.trim();
        const barangayName = user.barangay?.name || 'Barangay not assigned';
        const setProfileText = (selector, value) => {
            const element = card.querySelector(selector);
            if (element) element.textContent = value;
        };
        setProfileText('[data-profile-name]', fullName || 'Profile unavailable');
        setProfileText('[data-profile-location]', `⌖  ${barangayName}`);
        setProfileText('[data-profile-email]', user.email || 'Email not available');
        setProfileText('[data-profile-phone]', user.phone_number || 'Phone number not available');
        setProfileText('[data-profile-role]', (user.role || 'contributor').replace('_', ' ').toUpperCase());
        setProfileText('[data-profile-initials]', fullName
            .split(' ')
            .filter(Boolean)
            .map((part) => part[0])
            .join('')
            .slice(0, 2)
            .toUpperCase() || '●');
    } catch (error) {
        console.error('Unable to load authenticated citizen profile.', error);
        showToast('Unable to load your profile');
    }
};

const citizenApp = document.querySelector('[data-citizen-app]');
if (citizenApp) {
    const reportLatitude = citizenApp.querySelector('[data-report-latitude]');
    const reportLongitude = citizenApp.querySelector('[data-report-longitude]');
    const locationButton = citizenApp.querySelector('[data-use-current-location]');
    const locationLabel = citizenApp.querySelector('[data-location-label]');
    const locationStatus = citizenApp.querySelector('[data-location-status]');
    const requestCurrentLocation = () => {
        if (!('geolocation' in navigator)) {
            locationStatus.textContent = 'Device location is not supported by this browser.';
            return;
        }

        locationStatus.textContent = 'Requesting permission to access your device location…';
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                reportLatitude.value = coords.latitude.toFixed(8);
                reportLongitude.value = coords.longitude.toFixed(8);
                locationLabel.textContent = `Pinned at ${coords.latitude.toFixed(5)}, ${coords.longitude.toFixed(5)}`;
                locationStatus.textContent = 'Exact location pinned. Submit the report to share it with the response team.';
            },
            () => {
                locationStatus.textContent = 'Location permission was not granted. You can still submit with your assigned barangay.';
            },
            { enableHighAccuracy: true, maximumAge: 300000, timeout: 10000 },
        );
    };
    locationButton?.addEventListener('click', requestCurrentLocation);
    if (reportLatitude && reportLongitude && 'geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                reportLatitude.value = coords.latitude.toFixed(8);
                reportLongitude.value = coords.longitude.toFixed(8);
                locationLabel.textContent = `Pinned at ${coords.latitude.toFixed(5)}, ${coords.longitude.toFixed(5)}`;
                locationStatus.textContent = 'Device location detected. Tap Edit to refresh the pin.';
            },
            () => {
                // Location is optional; the report can still be submitted without coordinates.
            },
            { enableHighAccuracy: true, maximumAge: 300000, timeout: 10000 },
        );
    }

    loadCitizenProfile(citizenApp);
    const citizenScreens = citizenApp.querySelectorAll('[data-citizen-screen]');
    const citizenNavItems = citizenApp.querySelectorAll('[data-citizen-nav]');
    const showCitizenScreen = (screenName) => {
        citizenScreens.forEach((screen) => screen.classList.toggle('is-active', screen.dataset.citizenScreen === screenName));
        citizenNavItems.forEach((item) => item.classList.toggle('is-active', item.dataset.citizenNav === screenName));
        citizenApp.querySelector('.citizen-main')?.scrollTo({ top: 0, behavior: 'smooth' });
        if (screenName === 'map') {
            citizenApp.querySelector('[data-citizen-screen="map"]')?.dispatchEvent(new Event('citizen-map-shown'));
        }
        if (screenName === 'home') {
            citizenApp.querySelector('[data-citizen-screen="home"]')?.dispatchEvent(new Event('citizen-home-shown'));
        }
    };

    const initialScreen = citizenApp.dataset.initialScreen || 'home';
    if (initialScreen !== 'home') {
        showCitizenScreen(initialScreen);
    }

    citizenNavItems.forEach((item) => {
        item.addEventListener('click', (event) => {
            if (item.tagName === 'A') {
                return;
            }
            event.preventDefault();
            showCitizenScreen(item.dataset.citizenNav);
        });
    });

    citizenApp.querySelector('[data-citizen-alerts]')?.addEventListener('click', (event) => {
        if (event.currentTarget.tagName !== 'A') {
            showCitizenScreen('alerts');
        }
    });
    citizenApp.querySelector('[data-citizen-profile]')?.addEventListener('click', () => {
        showCitizenScreen('profile');
        loadCitizenProfile(citizenApp);
    });
    const sidebar = citizenApp.querySelector('[data-citizen-sidebar]');
    const sidebarOverlay = citizenApp.querySelector('[data-citizen-sidebar-overlay]');
    const setSidebarOpen = (isOpen) => {
        sidebar?.classList.toggle('is-open', isOpen);
        sidebarOverlay?.classList.toggle('is-visible', isOpen);
        sidebar?.setAttribute('aria-hidden', String(!isOpen));
    };
    citizenApp.querySelector('[data-citizen-menu]')?.addEventListener('click', () => setSidebarOpen(true));
    citizenApp.querySelector('[data-citizen-menu-close]')?.addEventListener('click', () => setSidebarOpen(false));
    sidebarOverlay?.addEventListener('click', () => setSidebarOpen(false));
    sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setSidebarOpen(false)));

    const detailModal = citizenApp.querySelector('[data-citizen-detail-modal]');
    const openDetail = (card) => {
        const detailLabel = card.dataset.detailLabel || 'DETAIL';
        const modalLabel = detailModal?.querySelector('[data-detail-modal-label]');
        const sourceBadge = card.querySelector('.citizen-pill, .alert-badge, .status-critical, .status-monitoring, .status-safe');
        const sourceTone = sourceBadge?.className.match(/(?:pill|badge|status)-(green|orange|blue|red|critical|monitoring|safe)/)?.[1];
        const tone = { critical: 'red', monitoring: 'orange', safe: 'green' }[sourceTone] || sourceTone || 'blue';
        modalLabel?.classList.remove('pill-green', 'pill-orange', 'pill-blue', 'pill-red');
        modalLabel?.classList.add(`pill-${tone}`);
        modalLabel?.replaceChildren(document.createTextNode(detailLabel));
        detailModal?.querySelector('[data-detail-modal-title]').replaceChildren(document.createTextNode(card.dataset.detailTitle || 'Details'));
        detailModal?.querySelector('[data-detail-modal-body]').replaceChildren(document.createTextNode(card.dataset.detailBody || 'No additional information available.'));
        detailModal?.querySelector('[data-detail-modal-meta]').replaceChildren(document.createTextNode(card.dataset.detailMeta || ''));
        detailModal?.classList.add('is-visible');
        detailModal?.setAttribute('aria-hidden', 'false');
    };
    const closeDetail = () => {
        detailModal?.classList.remove('is-visible');
        detailModal?.setAttribute('aria-hidden', 'true');
    };
    citizenApp.querySelectorAll('[data-detail-title]').forEach((card) => {
        card.setAttribute('tabindex', '0');
        card.addEventListener('click', () => openDetail(card));
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openDetail(card);
            }
        });
    });
    citizenApp.querySelector('[data-detail-close]')?.addEventListener('click', closeDetail);
    detailModal?.addEventListener('click', (event) => {
        if (event.target === detailModal) closeDetail();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDetail();
            setSidebarOpen(false);
        }
    });

    citizenApp.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-barangay]');
        if (removeButton) {
            removeButton.closest('[data-barangay-tag]')?.remove();
            showToast('Barangay removed from alerts');
        }

        if (event.target.closest('[data-edit-profile]')) {
            const card = event.target.closest('.profile-card');
            const name = card?.querySelector('[data-profile-name]');
            const location = card?.querySelector('[data-profile-location]');
            if (!card || !name || !location) {
                return;
            }

            const editing = card.dataset.editing === 'true';
            if (editing) {
                name.textContent = card.querySelector('[data-profile-name-input]')?.value.trim() || name.dataset.originalValue || '';
                location.textContent = `⌖  ${card.querySelector('[data-profile-location-input]')?.value.trim() || location.dataset.originalValue || 'Barangay not assigned'}`;
                card.querySelector('[data-profile-name-input]')?.remove();
                card.querySelector('[data-profile-location-input]')?.remove();
                event.target.closest('[data-edit-profile]').textContent = '⌁  Edit Profile';
                card.dataset.editing = 'false';
                showToast('Profile updated');
                return;
            }

            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.value = name.textContent.trim();
            name.dataset.originalValue = name.textContent.trim();
            nameInput.dataset.profileNameInput = '';
            nameInput.className = 'profile-edit-input';
            const locationInput = document.createElement('input');
            locationInput.type = 'text';
            locationInput.value = location.textContent.replace(/^⌖\s*/, '').trim();
            location.dataset.originalValue = location.textContent.replace(/^⌖\s*/, '').trim();
            locationInput.dataset.profileLocationInput = '';
            locationInput.className = 'profile-edit-input';
            name.replaceWith(nameInput);
            location.replaceWith(locationInput);
            event.target.closest('[data-edit-profile]').textContent = '✓  Save Profile';
            card.dataset.editing = 'true';
            nameInput.focus();
        }
    });

    const pollCitizenAlerts = async () => {
        try {
            const res = await fetch('/api/v1/citizen/alerts', {
                headers: { Accept: 'application/json' },
                credentials: 'include',
            });
            if (!res.ok) return;
            const data = await res.json();
            const alerts = data.alerts || [];

            const alertBell = citizenApp.querySelector('[data-citizen-alerts]');
            let dot = alertBell?.querySelector('.citizen-notification-dot');
            if (alerts.length > 0) {
                if (!dot && alertBell) {
                    dot = document.createElement('i');
                    dot.className = 'citizen-notification-dot';
                    dot.setAttribute('aria-label', 'New notification');
                    alertBell.appendChild(dot);
                }
            } else if (dot) {
                dot.remove();
            }

            const homeScreen = citizenApp.querySelector('[data-citizen-screen="home"]');
            let banner = homeScreen?.querySelector('[data-citizen-broadcast-banner]');
            if (alerts.length > 0) {
                const top = alerts[0];
                const isCrit = top.severity === 'CRITICAL';
                if (!banner && homeScreen) {
                    const loc = homeScreen.querySelector('.citizen-location');
                    banner = document.createElement('div');
                    banner.setAttribute('data-citizen-broadcast-banner', '');
                    loc?.after(banner);
                }
                if (banner) {
                    const escapeText = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;',
                    }[ch]));
                    banner.className = `citizen-broadcast-banner citizen-alert-summary ${isCrit ? 'is-critical' : 'is-warning'}`;
                    banner.dataset.detailLabel = `${top.severity} BROADCAST`;
                    banner.dataset.detailTitle = top.title;
                    banner.dataset.detailBody = `${top.message}\n\nTarget Area: ${top.target_scope}`;
                    banner.dataset.detailMeta = `Broadcast by DRRMO · ${top.time_ago}`;
                    banner.innerHTML = `
                        <div class="citizen-broadcast-badge-row">
                            <span class="citizen-alert-severity ${isCrit ? 'is-critical' : 'is-warning'}">
                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg>
                                <span>${isCrit ? 'CRITICAL FLOOD BROADCAST' : 'FLOOD ADVISORY'}</span>
                            </span>
                            <small class="tabular-nums">${escapeText(top.time_ago)}</small>
                        </div>
                        <strong>${escapeText(top.title)}</strong>
                        <p>${escapeText(top.message)}</p>
                        <div class="citizen-broadcast-footer">
                            <span class="inline-flex items-center gap-1.5"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>${escapeText(top.target_scope)}</span>
                            <a href="/citizen/alerts" data-citizen-nav="alerts">View all alerts <svg aria-hidden="true" viewBox="0 0 20 20"><path d="M4 10h11m-5-5 5 5-5 5"/></svg></a>
                        </div>
                    `;
                    banner.onclick = () => openDetail(banner);
                }
            } else if (banner) {
                banner.remove();
            }

            const alertList = citizenApp.querySelector('[data-citizen-alert-list]');
            if (alertList && alerts.length > 0) {
                const currentArticles = alertList.querySelectorAll('article.broadcast-alert-card');
                if (currentArticles.length !== alerts.length) {
                    alertList.innerHTML = alerts.map((alert) => {
                        const isCritical = alert.severity === 'CRITICAL';
                        const badgeClass = alert.severity === 'CRITICAL' ? 'badge-red' : (alert.severity === 'WARNING' ? 'badge-orange' : (alert.severity === 'ADVISORY' ? 'badge-blue' : 'badge-green'));
                        const badgeSymbol = alert.severity === 'CRITICAL' ? '● CRITICAL BROADCAST' : (alert.severity === 'WARNING' ? '▲ WARNING' : (alert.severity === 'ADVISORY' ? '⚑ ADVISORY' : '● NOTICE'));
                        return `
                            <article
                                data-detail-label="${alert.severity} BROADCAST"
                                data-detail-title="${alert.title}"
                                data-detail-body="${alert.message}\n\nTarget Area: ${alert.target_scope}"
                                data-detail-meta="Broadcast by DRRMO Admin · ${alert.time_ago}"
                                class="broadcast-alert-card ${isCritical ? 'border-critical' : ''}"
                            >
                                <div>
                                    <span class="alert-badge ${badgeClass}">${badgeSymbol}</span>
                                    <small>${alert.time_ago}</small>
                                </div>
                                <strong>${alert.title}</strong>
                                <p>${alert.message}</p>
                                <div class="alert-meta-row">
                                    <span>📍 ${alert.target_scope}</span>
                                    <span class="alert-official-tag">DRRMO Official Broadcast</span>
                                </div>
                            </article>
                        `;
                    }).join('');

                    alertList.querySelectorAll('[data-detail-title]').forEach((card) => {
                        card.setAttribute('tabindex', '0');
                        card.addEventListener('click', () => openDetail(card));
                    });
                }
            }
        } catch {
            // silent ignore
        }
    };

    pollCitizenAlerts();
    setInterval(pollCitizenAlerts, 15000);
}

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-close-broadcast]')) {
        const modal = document.querySelector('[data-broadcast-modal]');
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
        return;
    }

    const button = event.target.closest('button');
    if (!button || button.type === 'submit' || button.closest('form') || button.closest('[data-harvesting-page]') || button.dataset.themeToggle !== undefined || button.dataset.sidebarOpen !== undefined || button.dataset.sidebarClose !== undefined) {
        return;
    }

    if (button.dataset.broadcastAlert !== undefined) {
        const modal = document.querySelector('[data-broadcast-modal]');
        if (modal) {
            const form = modal.querySelector('[data-broadcast-form]');
            const postIdInput = form?.querySelector('#broadcast-post-id');
            const titleInput = form?.querySelector('#broadcast-title');
            const barangaySelect = form?.querySelector('#broadcast-barangay');
            const messageInput = form?.querySelector('#broadcast-message');

            if (postIdInput) postIdInput.value = button.dataset.postId || '';
            const brgyName = button.dataset.postBarangay;
            if (brgyName && titleInput) {
                titleInput.value = `Flood Warning: Brgy. ${brgyName}`;
            } else if (titleInput && !titleInput.value) {
                titleInput.value = 'Flash Flood Warning: Davao City';
            }

            if (brgyName && barangaySelect) {
                for (const opt of barangaySelect.options) {
                    if (opt.text.toLowerCase().includes(brgyName.toLowerCase())) {
                        barangaySelect.value = opt.value;
                        break;
                    }
                }
            }

            if (button.dataset.postText && messageInput) {
                messageInput.value = button.dataset.postText;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            titleInput?.focus();
        }
        return;
    }

    const label = button.textContent.trim().replace(/\s+/g, ' ');
    const row = button.closest('[class*="rounded-lg"], article');

    if (label.includes('Refresh')) {
        button.textContent = '✓ Refreshed';
        showToast('Portal data refreshed');
        window.setTimeout(() => { button.textContent = '↻  Refresh'; }, 1800);
    } else if (button.dataset.reviewAction === 'confirm') {
        button.textContent = '✓ Confirmed';
        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-not-allowed');
        showToast('Uncertain output confirmed as flood-related');
    } else if (button.dataset.reviewAction === 'false-positive') {
        button.textContent = 'Marked false positive';
        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-not-allowed');
        showToast('System output marked as a false positive');
    } else if (label.includes('Broadcast')) {
        button.textContent = '✓ Alert broadcast';
        showToast('Alert broadcast to response channels');
    } else if (label.includes('Share')) {
        navigator.clipboard?.writeText(window.location.href);
        showToast('Report link copied');
    } else if (label.includes('History')) {
        showToast('Report history is up to date');
    } else if (label.includes('Remove')) {
        row?.remove();
        showToast('Source removed from whitelist');
    } else if (label.includes('Save changes')) {
        localStorage.setItem('agos-settings-saved', new Date().toISOString());
        showToast('Settings saved on this device');
    } else if (label.includes('Add')) {
        const section = button.closest('section');
        const input = section?.querySelector('input[type="text"]');
        if (input) {
            const value = input.value.trim();
            if (!value) {
                input.focus();
                showToast('Enter a keyword or hashtag first');
                return;
            }
            const platform = section.querySelector('select')?.value || 'All platforms';
            const item = document.createElement('div');
            item.className = 'flex items-center justify-between gap-3 rounded-lg bg-[#91acd5] px-3 py-2.5';
            item.innerHTML = `<div class="min-w-0"><p class="truncate text-xs font-bold text-slate-700"></p><p class="mt-0.5 text-[9px] text-slate-500"></p></div><button type="button" class="relative h-5 w-9 shrink-0 rounded-full bg-slate-950" aria-label="Disable listener"><span class="absolute right-1 top-1 h-3 w-3 rounded-full bg-cyan-300"></span></button>`;
            item.querySelector('p').textContent = value;
            item.querySelectorAll('p')[1].textContent = `${platform} · 0 matches today`;
            section.querySelector('.space-y-3')?.prepend(item);
            input.value = '';
            showToast('Listener added');
        } else {
            showToast('Use this control to add a verified source');
        }
    } else if (button.getAttribute('aria-label')?.startsWith('Disable')) {
        const enabled = !button.classList.contains('bg-slate-950');
        button.classList.toggle('bg-slate-950', enabled);
        button.classList.toggle('bg-slate-400', !enabled);
        button.setAttribute('aria-label', enabled ? 'Disable listener' : 'Enable listener');
        button.querySelector('span')?.classList.toggle('right-1', enabled);
        button.querySelector('span')?.classList.toggle('left-1', !enabled);
        showToast(enabled ? 'Listener enabled' : 'Listener paused');
    }
});

document.addEventListener('change', (event) => {
    const control = event.target;
    if (control.matches('select')) {
        showToast(`Updated: ${control.value}`);
    }
});

const setupBroadcastModal = () => {
    const modal = document.querySelector('[data-broadcast-modal]');
    if (!modal) return;

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    const form = modal.querySelector('[data-broadcast-form]');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('[data-broadcast-submit]') || form.querySelector('button[type="submit"]');
            const origText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Broadcasting...';
            }

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    showToast('✓ Alert successfully broadcast to Citizen App!');
                    closeModal();
                    form.reset();
                } else {
                    showToast(data.message || 'Error sending broadcast alert.');
                }
            } catch (err) {
                showToast('Failed to send broadcast alert. Please try again.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = origText;
                }
            }
        });
    }
};

setupBroadcastModal();
updateThemeToggle();
