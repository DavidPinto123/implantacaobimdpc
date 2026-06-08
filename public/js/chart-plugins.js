document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        if (window.Chart && window.ChartDataLabels) {
            Chart.register(window.ChartDataLabels);
            console.log("Plugin ChartDataLabels registrado localmente.");
        } else {
            console.warn("Chart.js ou ChartDataLabels não estão disponíveis.");
        }
    }, 500);
});
