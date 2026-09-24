// Progressive enhancement only: booking and cancellation work without JavaScript.

const REFRESH_INTERVAL_MS = 5 * 60 * 1000;
const DEFAULT_IDLE_SECONDS = 90;
const SWIPE_DISTANCE_PX = 60;

document.documentElement.classList.add('js');

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

function initDatePicker() {
    for (const input of document.querySelectorAll('form[data-date-picker] input[type="date"]')) {
        input.addEventListener('change', () => input.form.requestSubmit());
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
        trigger.hidden = false;
        trigger.addEventListener('click', () => {
            showMonth(activeIndex);
            dialog.showModal();
        });
    }

    for (const fallback of document.querySelectorAll('form[data-date-picker]')) {
        fallback.hidden = true;
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
function initAutoRefresh() {
    const refreshAt = Number(body.dataset.refreshAt);

    if (!refreshAt) {
        return;
    }

    let timer;
    const schedule = () => {
        clearTimeout(timer);
        const delay = Math.max(1000, Math.min(REFRESH_INTERVAL_MS, refreshAt - Date.now() + 1000));
        timer = setTimeout(() => {
            if (document.visibilityState === 'visible' && !document.querySelector('dialog[open]')) {
                window.location.reload();
            } else {
                schedule();
            }
        }, delay);
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && Date.now() >= refreshAt) {
            window.location.reload();
        }
    });

    schedule();
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
            window.location.assign(url);
        }
    });
}

initConfirmForms();
initDatePicker();
initCalendarDialog();
initAutoRefresh();
initIdleReturn();
initSwipe();
