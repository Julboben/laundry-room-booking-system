// Progressive enhancement only: booking and cancellation work without JavaScript.

const REFRESH_INTERVAL_MS = 5 * 60 * 1000;
const DEFAULT_IDLE_SECONDS = 90;
const SWIPE_DISTANCE_PX = 60;

const body = document.body;

function initConfirmForms() {
    for (const form of document.querySelectorAll('form[data-confirm]')) {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    }
}

function initCalendarDialog() {
    const dialog = document.querySelector('[data-calendar-dialog]');

    if (!dialog || typeof dialog.showModal !== 'function') {
        return;
    }

    const months = [...dialog.querySelectorAll('[data-calendar-month]')];
    const monthLabel = dialog.querySelector('[data-month-label]');
    const previousButton = dialog.querySelector('[data-month-previous]');
    const nextButton = dialog.querySelector('[data-month-next]');
    let activeIndex = Math.max(0, months.findIndex((month) => month.hasAttribute('data-active')));

    const showMonth = (index) => {
        activeIndex = Math.max(0, Math.min(months.length - 1, index));
        months.forEach((month, monthIndex) => {
            month.hidden = monthIndex !== activeIndex;
        });
        monthLabel.textContent = months[activeIndex].dataset.monthLabel;
        previousButton.disabled = activeIndex === 0;
        nextButton.disabled = activeIndex === months.length - 1;
    };

    for (const trigger of document.querySelectorAll('[data-calendar-open]')) {
        trigger.addEventListener('click', () => {
            showMonth(activeIndex);
            dialog.showModal();
        });
    }

    dialog.querySelector('[data-calendar-close]').addEventListener('click', () => dialog.close());
    previousButton.addEventListener('click', () => showMonth(activeIndex - 1));
    nextButton.addEventListener('click', () => showMonth(activeIndex + 1));

    // Fallback for browsers without closedby="any".
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
}

// Keeps the kiosk calendar current: reloads at the next slot change and at least every few minutes.
let refreshTimer;

function scheduleRefresh() {
    clearTimeout(refreshTimer);
    const refreshAt = Number(body.dataset.refreshAt);

    if (!refreshAt) {
        return;
    }

    const delay = Math.max(1000, Math.min(REFRESH_INTERVAL_MS, refreshAt - Date.now() + 1000));
    refreshTimer = setTimeout(() => {
        if (document.visibilityState === 'visible' && !document.querySelector('dialog[open]')) {
            window.location.reload();
        } else {
            scheduleRefresh();
        }
    }, delay);
}

function initAutoRefresh() {
    document.addEventListener('visibilitychange', () => {
        const refreshAt = Number(body.dataset.refreshAt);

        if (refreshAt && document.visibilityState === 'visible' && Date.now() >= refreshAt) {
            window.location.reload();
        }
    });

    scheduleRefresh();
}

// Switches days inside the page instead of a full navigation, so the screen never blanks.
const DAY_CACHE_MS = 30 * 1000;
const dayCache = new Map();

function isCalendarUrl(url) {
    return url.origin === window.location.origin && url.pathname === '/calendar.php';
}

function fetchDay(href) {
    const cached = dayCache.get(href);

    if (cached && Date.now() - cached.fetchedAt < DAY_CACHE_MS) {
        return cached.html;
    }

    const html = fetch(href, { credentials: 'same-origin' }).then((response) => {
        // A redirect away from the calendar means the session expired; let a real navigation handle it.
        if (!response.ok || !isCalendarUrl(new URL(response.url))) {
            throw new Error('Unexpected calendar response');
        }

        return response.text();
    });
    html.catch(() => dayCache.delete(href));
    dayCache.set(href, { html, fetchedAt: Date.now() });

    return html;
}

function prefetchNeighbourDays() {
    const area = document.querySelector('[data-swipe-area]');

    for (const url of [area?.dataset.previousUrl, area?.dataset.nextUrl]) {
        if (url) {
            fetchDay(new URL(url, window.location.href).href).catch(() => {});
        }
    }
}

const DAY_SLIDE_PX = 32;
const DAY_SLIDE_MS = 320;
const DAY_SLIDE_EASING = 'cubic-bezier(0.2, 0.8, 0.2, 1)';

let latestDayRequest = 0;

// Animates the real elements rather than using a view transition, which would block taps until it ends.
function slideInDay(section, direction) {
    if (typeof section.animate !== 'function' || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const offset = direction === 'back' ? -DAY_SLIDE_PX : DAY_SLIDE_PX;
    const parts = [[section.querySelector('.day-title'), 0], [section.querySelector('.day-slots'), 40]];

    for (const [element, delay] of parts) {
        element?.animate(
            [{ opacity: 0, transform: `translateX(${offset}px)` }, { opacity: 1, transform: 'none' }],
            { duration: DAY_SLIDE_MS, delay, easing: DAY_SLIDE_EASING, fill: 'backwards' },
        );
    }
}

async function showDay(href, { push = true } = {}) {
    const request = ++latestDayRequest;
    let html;

    try {
        html = await fetchDay(href);
    } catch {
        window.location.assign(href);
        return;
    }

    // A later tap won while this day was loading.
    if (request !== latestDayRequest) {
        return;
    }

    const current = document.querySelector('.daily-calendar');

    const page = new DOMParser().parseFromString(html, 'text/html');
    const next = page.querySelector('.daily-calendar');

    if (!current || !next) {
        window.location.assign(href);
        return;
    }

    const hadFocus = current.contains(document.activeElement);
    const direction = (next.dataset.date ?? '') < (current.dataset.date ?? '') ? 'back' : 'forward';
    document.querySelector('dialog[open]')?.close();

    // The slide replaces the cards' own entrance animation, which would otherwise play as well.
    next.classList.add('is-swapped');
    current.replaceWith(document.adoptNode(next));
    slideInDay(next, direction);

    const languageSwitch = page.querySelector('.language-switch');
    if (languageSwitch) {
        document.querySelector('.language-switch')?.replaceWith(document.adoptNode(languageSwitch));
    }

    document.title = page.title;
    body.dataset.refreshAt = page.body.dataset.refreshAt ?? '';

    if (push) {
        window.history.pushState(null, '', href);
    }

    if (hadFocus) {
        next.querySelector('[aria-current="date"]')?.focus({ preventScroll: true });
    }

    initCalendarSection();
    scheduleRefresh();
}

function initDayNavigation() {
    if (!document.querySelector('.daily-calendar')) {
        return;
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link || !link.closest('.daily-calendar') || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const url = new URL(link.href);

        if (!isCalendarUrl(url)) {
            return;
        }

        event.preventDefault();

        if (url.searchParams.get('date') === document.querySelector('.daily-calendar')?.dataset.date) {
            document.querySelector('dialog[open]')?.close();
            return;
        }

        showDay(url.href);
    });

    window.addEventListener('popstate', () => {
        showDay(window.location.href, { push: false });
    });
}

// Sends the shared tablet back to the calendar when a resident walks away mid-flow.
function initIdleReturn() {
    const target = body.dataset.idleReturn;

    if (!target) {
        return;
    }

    const idleSeconds = Number(body.dataset.idleSeconds) || DEFAULT_IDLE_SECONDS;
    const countdown = document.querySelector('[data-idle-countdown]');
    let remaining = idleSeconds;

    const render = () => {
        if (countdown) {
            countdown.hidden = false;
            countdown.textContent = countdown.dataset.idleCountdown.replace('%d', String(remaining));
        }
    };

    const reset = () => {
        remaining = idleSeconds;
        render();
    };

    for (const eventName of ['pointerdown', 'keydown', 'input']) {
        document.addEventListener(eventName, reset, { passive: true });
    }

    render();
    setInterval(() => {
        remaining -= 1;

        if (remaining <= 0) {
            window.location.assign(target);
            return;
        }

        render();
    }, 1000);
}

function initSwipe() {
    const area = document.querySelector('[data-swipe-area]');

    if (!area) {
        return;
    }

    let start = null;

    area.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse') {
            start = { x: event.clientX, y: event.clientY };
        }
    });

    area.addEventListener('pointercancel', () => {
        start = null;
    });

    area.addEventListener('pointerup', (event) => {
        if (!start) {
            return;
        }

        const deltaX = event.clientX - start.x;
        const deltaY = event.clientY - start.y;
        start = null;

        if (Math.abs(deltaX) < SWIPE_DISTANCE_PX || Math.abs(deltaX) < Math.abs(deltaY) * 1.5) {
            return;
        }

        const url = deltaX < 0 ? area.dataset.nextUrl : area.dataset.previousUrl;

        if (url) {
            showDay(new URL(url, window.location.href).href);
        }
    });
}

// Everything inside the calendar section, which is replaced when the day changes.
function initCalendarSection() {
    initCalendarDialog();
    initSwipe();
    prefetchNeighbourDays();
}

initConfirmForms();
initCalendarSection();
initDayNavigation();
initAutoRefresh();
initIdleReturn();
