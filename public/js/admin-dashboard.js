document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-income-percent]').forEach(function (element) {
        element.style.setProperty('--income-percent', element.dataset.incomePercent + '%');
    });
});
