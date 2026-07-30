(function () {
    'use strict';

    function applyWhatsappMask(input) {
        if (input.dataset.whatsappMaskApplied === 'true') return;
        input.dataset.whatsappMaskApplied = 'true';
        input.setAttribute('inputmode', 'tel');
        input.setAttribute('autocomplete', 'tel');
        input.setAttribute('maxlength', '19');
        input.setAttribute('placeholder', '+55 (24) 99999-9999');

        function update() {
            let digits = (input.value || '').replace(/\D/g, '');
            if (digits.indexOf('55') === 0) digits = digits.slice(2);
            if (digits.length === 11 && digits.indexOf('0') === 0) digits = digits.slice(1);
            digits = digits.slice(0, 11);

            if (!digits) {
                input.value = '';
                input.setCustomValidity('');
                input.classList.remove('is-invalid');
                return;
            }

            const ddd = digits.slice(0, 2);
            const phone = digits.slice(2);
            let formatted = '+55 (' + ddd;
            if (digits.length >= 2) formatted += ')';
            if (phone) {
                formatted += ' ' + (phone.length > 8
                    ? phone.slice(0, 5) + '-' + phone.slice(5)
                    : phone.length > 4
                        ? phone.slice(0, 4) + '-' + phone.slice(4)
                        : phone);
            }

            input.value = formatted;
            const valid = digits.length === 10 || digits.length === 11;
            input.setCustomValidity(valid ? '' : 'Informe +55, o DDD e um telefone com 8 ou 9 dígitos.');
            input.classList.toggle('is-invalid', !valid);
        }

        input.addEventListener('input', update);
        input.addEventListener('blur', update);
        if (input.form) input.form.addEventListener('submit', update);
        update();
    }

    function scan(root) {
        if (root.matches && root.matches('input[name="whatsapp"]')) applyWhatsappMask(root);
        if (root.querySelectorAll) root.querySelectorAll('input[name="whatsapp"]').forEach(applyWhatsappMask);
    }

    scan(document);
    if (window.MutationObserver && document.body) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) scan(node);
                });
            });
        }).observe(document.body, {childList: true, subtree: true});
    }
}());
