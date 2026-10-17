import './bootstrap';

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

const notificationsToggle = document.querySelector('[data-notifications-toggle]');
const notificationsMenu = document.querySelector('[data-notifications-menu]');
notificationsToggle?.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = !notificationsMenu?.classList.toggle('hidden');
    notificationsToggle.setAttribute('aria-expanded', String(isOpen));
});
document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-notifications-menu], [data-notifications-toggle]')) {
        notificationsMenu?.classList.add('hidden');
        notificationsToggle?.setAttribute('aria-expanded', 'false');
    }
});

document.querySelector('[data-logout]')?.addEventListener('click', () => {
    if (window.confirm('Log out of the AGOS portal?')) {
        window.location.href = '/login';
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
        card.querySelector('[data-profile-name]').textContent = fullName || 'Profile unavailable';
        card.querySelector('[data-profile-location]').textContent = `⌖  ${barangayName}`;
        card.querySelector('[data-profile-email]').textContent = user.email || 'Email not available';
        card.querySelector('[data-profile-phone]').textContent = user.phone_number || 'Phone number not available';
        card.querySelector('[data-profile-role]').textContent = (user.role || 'contributor').replace('_', ' ').toUpperCase();
        card.querySelector('[data-profile-initials]').textContent = fullName
            .split(' ')
            .filter(Boolean)
            .map((part) => part[0])
            .join('')
            .slice(0, 2)
            .toUpperCase() || '●';
    } catch (error) {
        console.error('Unable to load authenticated citizen profile.', error);
        showToast('Unable to load your profile');
    }
};

const citizenApp = document.querySelector('[data-citizen-app]');
if (citizenApp) {
    loadCitizenProfile(citizenApp);
    const citizenScreens = citizenApp.querySelectorAll('[data-citizen-screen]');
    const citizenNavItems = citizenApp.querySelectorAll('[data-citizen-nav]');
    const showCitizenScreen = (screenName) => {
        citizenScreens.forEach((screen) => screen.classList.toggle('is-active', screen.dataset.citizenScreen === screenName));
        citizenNavItems.forEach((item) => item.classList.toggle('is-active', item.dataset.citizenNav === screenName));
        citizenApp.querySelector('.citizen-main')?.scrollTo({ top: 0, behavior: 'smooth' });
    };

    citizenNavItems.forEach((item) => {
        item.addEventListener('click', (event) => {
            event.preventDefault();
            showCitizenScreen(item.dataset.citizenNav);
        });
    });

    citizenApp.querySelector('[data-citizen-alerts]')?.addEventListener('click', () => showCitizenScreen('alerts'));
    citizenApp.querySelector('[data-citizen-profile]')?.addEventListener('click', () => {
        showCitizenScreen('profile');
        loadCitizenProfile(citizenApp);
    });
    citizenApp.querySelector('[data-citizen-menu]')?.addEventListener('click', () => showToast('AGOS Citizen App'));

    citizenApp.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-barangay]');
        if (removeButton) {
            removeButton.closest('[data-barangay-tag]')?.remove();
            showToast('Barangay removed from alerts');
        }

        document.querySelectorAll('[data-report-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const isFalseAlarm = button.textContent.includes('False Alarm');
                button.textContent = isFalseAlarm ? 'Marked false alarm' : '✓ Confirmed';
                button.disabled = true;
                button.classList.add('cursor-not-allowed', 'opacity-60');
                showToast(isFalseAlarm ? 'Report marked as a false alarm' : 'Report marked as validated');
            });
        });

        const broadcastModal = document.querySelector('[data-broadcast-modal]');
        document.querySelectorAll('[data-broadcast-alert]').forEach((button) => {
            button.addEventListener('click', () => {
                broadcastModal?.classList.remove('hidden');
                broadcastModal?.classList.add('flex');
                broadcastModal?.querySelector('textarea')?.focus();
            });
        });
        broadcastModal?.querySelector('[data-close-broadcast]')?.addEventListener('click', () => {
            broadcastModal.classList.add('hidden');
            broadcastModal.classList.remove('flex');
        });
        broadcastModal?.addEventListener('click', (event) => {
            if (event.target === broadcastModal) {
                broadcastModal.classList.add('hidden');
                broadcastModal.classList.remove('flex');
            }
        });
        broadcastModal?.querySelector('[data-broadcast-form]')?.addEventListener('submit', (event) => {
            event.preventDefault();
            broadcastModal.classList.add('hidden');
            broadcastModal.classList.remove('flex');
            event.currentTarget.reset();
            showToast('Alert broadcast to response channels');
        });

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

    const photoInput = citizenApp.querySelector('#report-photo');
    photoInput?.addEventListener('change', () => {
        const file = photoInput.files?.[0];
        if (file) {
            citizenApp.querySelector('[data-photo-label]').textContent = file.name;
            showToast('Photo attached to report');
        }
    });
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('button');
    if (!button || button.dataset.themeToggle !== undefined || button.dataset.sidebarOpen !== undefined || button.dataset.sidebarClose !== undefined) {
        return;
    }

    if (button.dataset.broadcastAlert !== undefined) {
        const modal = document.querySelector('[data-broadcast-modal]');
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
        modal?.querySelector('textarea')?.focus();
        return;
    }

    const label = button.textContent.trim().replace(/\s+/g, ' ');
    const row = button.closest('[class*="rounded-lg"], article');

    if (label.includes('Refresh')) {
        button.textContent = '✓ Refreshed';
        showToast('Portal data refreshed');
        window.setTimeout(() => { button.textContent = '↻  Refresh'; }, 1800);
    } else if (label.includes('Confirm')) {
        button.textContent = '✓ Confirmed';
        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-not-allowed');
        showToast('Report marked as validated');
    } else if (label.includes('False Alarm')) {
        button.textContent = 'Marked false alarm';
        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-not-allowed');
        showToast('Report marked as a false alarm');
    } else if (label.includes('Broadcast')) {
        button.textContent = '✓ Alert broadcast';
        showToast('Alert broadcast to response channels');
    } else if (label.includes('Escalate')) {
        button.textContent = '✓ Escalated for review';
        showToast('Report escalated for review');
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

updateThemeToggle();
