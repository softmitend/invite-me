document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !window.jQuery.fn.select2) {
        return;
    }

    window.jQuery('.js-select2').each(function () {
        window.jQuery(this).select2({
            allowClear: false,
            placeholder: window.jQuery(this).data('placeholder'),
            width: '100%'
        });
    });
});
