// Minimal progressive-enhancement script. All core booking and
// cancellation flows work without JavaScript; this file only adds
// small usability touches for browsers that support it.
(function () {
    'use strict';

    document.documentElement.classList.add('js');

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm');
            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('form[data-date-picker] input[type="date"]').forEach(function (input) {
        input.addEventListener('change', function () {
            input.form.requestSubmit();
        });
    });

    document.querySelectorAll('[data-calendar-dialog]').forEach(function (dialog) {
        if (typeof dialog.showModal !== 'function') {
            return;
        }

        document.documentElement.classList.add('dialog-supported');

        var months = Array.from(dialog.querySelectorAll('[data-calendar-month]'));
        var activeIndex = Math.max(0, months.findIndex(function (month) {
            return month.classList.contains('is-active');
        }));
        var monthLabel = dialog.querySelector('[data-month-label]');
        var previousButton = dialog.querySelector('[data-month-previous]');
        var nextButton = dialog.querySelector('[data-month-next]');

        function showMonth(index) {
            activeIndex = Math.max(0, Math.min(months.length - 1, index));
            months.forEach(function (month, monthIndex) {
                month.hidden = monthIndex !== activeIndex;
            });
            monthLabel.textContent = months[activeIndex].getAttribute('data-month-label');
            previousButton.disabled = activeIndex === 0;
            nextButton.disabled = activeIndex === months.length - 1;
        }

        document.querySelectorAll('[data-calendar-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                showMonth(activeIndex);
                dialog.showModal();
            });
        });

        dialog.querySelector('[data-calendar-close]').addEventListener('click', function () {
            dialog.close();
        });
        previousButton.addEventListener('click', function () {
            showMonth(activeIndex - 1);
        });
        nextButton.addEventListener('click', function () {
            showMonth(activeIndex + 1);
        });
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });
})();
