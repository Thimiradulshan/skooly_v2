/**
 * Skooly admin UI behaviours.
 *
 * Local, dependency-free, and progressively enhanced. Every feature here is an
 * enhancement: forms still submit normally if this file fails to load.
 *
 * - Toast stack for flash messages, with keyboard dismissal.
 * - Confirmation dialog for forms carrying a data-confirm attribute.
 * - Submit-button loading state to prevent double submission.
 */
(function () {
    'use strict';

    var SUBMIT_SELECTOR = 'button[type="submit"], input[type="submit"]';
    var NAVIGATION_TIMEOUT_MS = 45000;

    function onReady(callback) {
        if (document.readyState !== 'loading') {
            callback();
            return;
        }
        document.addEventListener('DOMContentLoaded', callback);
    }

    /* ------------------------------------------------------------ toasts -- */

    function ensureToastRegion() {
        var region = document.getElementById('skooly-toasts');
        if (region) {
            return region;
        }
        region = document.createElement('div');
        region.id = 'skooly-toasts';
        region.className = 'toast-region';
        region.setAttribute('role', 'region');
        region.setAttribute('aria-label', 'Notifications');
        document.body.appendChild(region);
        return region;
    }

    function dismissToast(toast) {
        if (!toast || toast.dataset.dismissed === 'true') {
            return;
        }
        toast.dataset.dismissed = 'true';
        toast.classList.remove('toast-visible');
        window.setTimeout(function () {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 320);
    }

    function createToast(message, tone) {
        var region = ensureToastRegion();
        var toast = document.createElement('div');
        var isError = tone === 'error';
        toast.className = 'toast toast-' + (isError ? 'error' : tone || 'success');
        toast.setAttribute('role', isError ? 'alert' : 'status');

        var text = document.createElement('p');
        text.className = 'toast-text';
        text.textContent = message;
        toast.appendChild(text);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.innerHTML = '&times;';
        close.addEventListener('click', function () {
            dismissToast(toast);
        });
        toast.appendChild(close);

        region.appendChild(toast);
        // Force a reflow so the entry transition always runs.
        window.requestAnimationFrame(function () {
            toast.classList.add('toast-visible');
        });

        if (!isError) {
            window.setTimeout(function () {
                dismissToast(toast);
            }, 8000);
        }

        return toast;
    }

    /**
     * Move server-rendered flash alerts into the toast stack.
     *
     * The server renders a normal, visible alert so feedback survives a JS
     * failure or a disabled script. When this enhancement runs, the alert is
     * removed and shown as a toast instead.
     */
    function migrateFlashMessages() {
        var flashes = document.querySelectorAll('[data-flash-message]');
        Array.prototype.forEach.call(flashes, function (node) {
            createToast(node.getAttribute('data-flash-message'), node.getAttribute('data-flash-tone'));
            node.parentNode.removeChild(node);
        });
    }

    /* ------------------------------------------------------- announcer -- */

    function ensureAnnouncer() {
        var region = document.getElementById('skooly-announcer');
        if (region) {
            return region;
        }
        region = document.createElement('div');
        region.id = 'skooly-announcer';
        region.className = 'sr-only';
        region.setAttribute('role', 'status');
        region.setAttribute('aria-live', 'polite');
        document.body.appendChild(region);
        return region;
    }

    function announce(message) {
        ensureAnnouncer().textContent = message;
    }

    /* ------------------------------------------------------ confirm modal -- */

    function getConfirmHost() {
        var host = document.getElementById('skooly-confirm');
        if (host) {
            return host;
        }
        host = document.createElement('div');
        host.id = 'skooly-confirm';
        host.className = 'confirm-overlay';
        host.hidden = true;
        document.body.appendChild(host);
        return host;
    }

    function closeConfirm(host) {
        host.hidden = true;
        host.innerHTML = '';
    }

    function buildConfirm(host, options) {
        var title = options.title || 'Please confirm';
        var message = options.message || 'Are you sure?';
        var action = options.action || 'Confirm';
        var tone = options.tone || 'primary';

        host.innerHTML = '';
        host.hidden = false;

        var dialog = document.createElement('div');
        dialog.className = 'confirm-dialog';
        dialog.setAttribute('role', 'alertdialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-labelledby', 'skooly-confirm-title');
        dialog.setAttribute('aria-describedby', 'skooly-confirm-message');

        var heading = document.createElement('h2');
        heading.className = 'confirm-title';
        heading.id = 'skooly-confirm-title';
        heading.textContent = title;

        var body = document.createElement('p');
        body.className = 'confirm-message';
        body.id = 'skooly-confirm-message';
        body.textContent = message;

        var actions = document.createElement('div');
        actions.className = 'confirm-actions';

        var cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-secondary';
        cancel.textContent = options.cancel || 'Cancel';

        var proceed = document.createElement('button');
        proceed.type = 'button';
        proceed.className = 'btn' + (tone === 'danger' ? ' btn-danger' : '');
        proceed.textContent = action;

        actions.appendChild(cancel);
        actions.appendChild(proceed);
        dialog.appendChild(heading);
        dialog.appendChild(body);
        dialog.appendChild(actions);
        host.appendChild(dialog);

        var previouslyFocused = document.activeElement;
        proceed.focus();

        function releaseFocus() {
            document.removeEventListener('keydown', onKeydown);
            if (previouslyFocused) {
                previouslyFocused.focus();
            }
        }

        function onKeydown(event) {
            if (event.key === 'Escape') {
                closeConfirm(host);
                releaseFocus();
            }
        }
        document.addEventListener('keydown', onKeydown);

        cancel.addEventListener('click', function () {
            closeConfirm(host);
            releaseFocus();
        });

        proceed.addEventListener('click', function () {
            closeConfirm(host);
            document.removeEventListener('keydown', onKeydown);
            submitConfirmedForm(options.form);
        });
    }

    /**
     * Submit the form only after the user confirms it.
     *
     * requestSubmit() re-fires the submit event, where the confirmed marker
     * lets the handler continue instead of opening the dialog again. The
     * marker is removed immediately afterwards, including when native
     * constraint validation blocks the submission.
     */
    function submitConfirmedForm(form) {
        if (!form) {
            return;
        }

        form.dataset.confirmed = 'true';

        try {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                if (form.hasAttribute('data-loading')) {
                    setSubmitting(form);
                }
                form.submit();
            }
        } finally {
            delete form.dataset.confirmed;
        }
    }

    /* ------------------------------------------------- loading behaviour -- */

    function submitControls(form) {
        var controls = Array.prototype.slice.call(form.querySelectorAll(SUBMIT_SELECTOR));

        // A submit button can live outside the form through the form="id" attribute.
        if (form.id) {
            var external = document.querySelectorAll('[form="' + form.id + '"]');
            Array.prototype.forEach.call(external, function (control) {
                if (control.type === 'submit' && controls.indexOf(control) === -1) {
                    controls.push(control);
                }
            });
        }

        return controls;
    }

    function setSubmitting(form) {
        if (!form || form.dataset.submitting === 'true') {
            return;
        }
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');

        var controls = submitControls(form);
        Array.prototype.forEach.call(controls, function (control) {
            if (control.dataset.originalLabel === undefined) {
                control.dataset.originalLabel = control.tagName === 'BUTTON'
                    ? control.innerHTML
                    : control.value;
            }
            control.disabled = true;
            control.classList.add('is-loading');
            control.setAttribute('aria-busy', 'true');

            if (control.tagName === 'BUTTON') {
                control.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span> Processing';
            } else {
                control.value = 'Processing';
            }
        });

        announce('Submitting. Please wait.');

        // If the browser never navigates (cancelled navigation, dead network),
        // put the form back so the user is not trapped on a disabled button.
        form.dataset.submitTimer = String(window.setTimeout(function () {
            restoreForm(form);
            createToast(
                'No response was received. The action may not have completed. Check the record before trying again.',
                'error'
            );
        }, NAVIGATION_TIMEOUT_MS));
    }

    function restoreForm(form) {
        if (!form) {
            return;
        }

        if (form.dataset.submitTimer) {
            window.clearTimeout(Number(form.dataset.submitTimer));
            delete form.dataset.submitTimer;
        }

        delete form.dataset.confirmed;

        if (form.dataset.submitting !== 'true') {
            return;
        }

        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');

        var controls = submitControls(form);
        Array.prototype.forEach.call(controls, function (control) {
            if (control.dataset.originalLabel !== undefined) {
                if (control.tagName === 'BUTTON') {
                    control.innerHTML = control.dataset.originalLabel;
                } else {
                    control.value = control.dataset.originalLabel;
                }
                delete control.dataset.originalLabel;
            }
            control.disabled = false;
            control.classList.remove('is-loading');
            control.removeAttribute('aria-busy');
        });
    }

    function restoreAllForms() {
        var forms = document.querySelectorAll('form[data-loading], form[data-confirm]');
        Array.prototype.forEach.call(forms, restoreForm);
        announce('');
    }

    function interceptForms() {
        var forms = document.querySelectorAll('form[data-confirm], form[data-loading]');
        var host = getConfirmHost();

        Array.prototype.forEach.call(forms, function (form) {
            form.addEventListener('submit', function (event) {
                if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== 'true') {
                    event.preventDefault();
                    buildConfirm(host, {
                        title: form.getAttribute('data-confirm-title') || 'Please confirm',
                        message: form.getAttribute('data-confirm'),
                        action: form.getAttribute('data-confirm-action') || 'Confirm',
                        tone: form.getAttribute('data-confirm-tone') || 'primary',
                        cancel: form.getAttribute('data-confirm-cancel') || 'Cancel',
                        form: form
                    });
                    return;
                }

                if (form.hasAttribute('data-loading')) {
                    setSubmitting(form);
                }
            });
        });
    }

    onReady(function () {
        migrateFlashMessages();
        interceptForms();
    });

    // Restore controls when a page is restored from the back/forward cache,
    // otherwise a cancelled submit leaves the button stuck on "Processing".
    window.addEventListener('pageshow', function () {
        restoreAllForms();
    });
})();
