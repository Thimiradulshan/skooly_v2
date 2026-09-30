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

    var FOCUSABLE =
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

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
            }, 6000);
        }

        return toast;
    }

    function migrateFlashMessages() {
        var flashes = document.querySelectorAll('[data-flash-message]');
        Array.prototype.forEach.call(flashes, function (node) {
            createToast(node.getAttribute('data-flash-message'), node.getAttribute('data-flash-tone'));
            node.parentNode.removeChild(node);
        });
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

        function onKeydown(event) {
            if (event.key === 'Escape') {
                closeConfirm(host);
                document.removeEventListener('keydown', onKeydown);
                if (previouslyFocused) {
                    previouslyFocused.focus();
                }
            }
        }
        document.addEventListener('keydown', onKeydown);

        cancel.addEventListener('click', function () {
            closeConfirm(host);
            document.removeEventListener('keydown', onKeydown);
            if (previouslyFocused) {
                previouslyFocused.focus();
            }
        });

        proceed.addEventListener('click', function () {
            closeConfirm(host);
            document.removeEventListener('keydown', onKeydown);
            setSubmitting(options.form);
        });
    }

    function interceptConfirmedForms() {
        var forms = document.querySelectorAll('form[data-confirm]');
        var host = getConfirmHost();

        Array.prototype.forEach.call(forms, function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === 'true') {
                    return;
                }
                event.preventDefault();
                buildConfirm(host, {
                    title: form.getAttribute('data-confirm-title') || 'Please confirm',
                    message: form.getAttribute('data-confirm'),
                    action: form.getAttribute('data-confirm-action') || 'Confirm',
                    tone: form.getAttribute('data-confirm-tone') || 'primary',
                    cancel: form.getAttribute('data-confirm-cancel') || 'Cancel',
                    form: form
                });
            });
        });
    }

    /* ------------------------------------------------- loading behaviour -- */

    function setSubmitting(form) {
        if (!form || form.dataset.submitting === 'true') {
            return;
        }
        form.dataset.submitting = 'true';

        var submit = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!submit) {
            return;
        }
        submit.dataset.originalLabel = submit.dataset.originalLabel || submit.innerHTML;
        submit.disabled = true;
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
        if (submit.tagName === 'BUTTON') {
            submit.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span> Processing';
        }
    }

    function interceptLoadingForms() {
        var forms = document.querySelectorAll('form[data-loading]');
        Array.prototype.forEach.call(forms, function (form) {
            form.addEventListener('submit', function () {
                setSubmitting(form);
            });
        });
    }

    onReady(function () {
        migrateFlashMessages();
        interceptConfirmedForms();
        interceptLoadingForms();
    });
})();
