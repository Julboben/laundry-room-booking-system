// Minimal progressive-enhancement script. All core booking and
// cancellation flows work without JavaScript; this file only adds
// small usability touches for browsers that support it.
(function () {
    'use strict';

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm');
            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
})();
