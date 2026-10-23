/* Global payment-allocation preview. Display-only; server validation remains authoritative. */
(function () {
    'use strict';

    function cents(value) {
        var normalized = String(value || '').trim();
        if (!/^\d+(?:\.\d{0,2})?$/.test(normalized)) {
            return null;
        }

        var parts = normalized.split('.');

        return BigInt(parts[0]) * 100n + BigInt((parts[1] || '').padEnd(2, '0'));
    }

    function format(value) {
        var whole = value / 100n;
        var fraction = String(value % 100n).padStart(2, '0');

        return String(whole) + '.' + fraction;
    }

    function update(form) {
        var allocationTotal = 0n;
        var amounts = form.querySelectorAll('[data-allocation-amount]');

        Array.prototype.forEach.call(amounts, function (input) {
            var amount = cents(input.value);
            if (amount !== null) {
                allocationTotal += amount;
            }
        });

        form.querySelector('[data-allocation-total]').textContent = format(allocationTotal);

        var paymentTotal = cents(form.querySelector('[data-payment-total]').value);
        form.querySelector('[data-unallocated-payment]').textContent = paymentTotal === null
            ? '—'
            : format(paymentTotal - allocationTotal);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('[data-payment-allocation-form]');

        Array.prototype.forEach.call(forms, function (form) {
            form.addEventListener('input', function () {
                update(form);
            });
            update(form);
        });
    });
})();
