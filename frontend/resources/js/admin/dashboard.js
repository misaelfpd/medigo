// Grafik pertumbuhan pengguna di dashboard admin (data dari atribut data-* kanvas)
document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.getElementById("userGrowthChart");
    if (!canvas || typeof window.Chart === "undefined") return;

    let labels = [];
    let values = [];
    try {
        labels = JSON.parse(canvas.dataset.labels || "[]");
        values = JSON.parse(canvas.dataset.values || "[]");
    } catch (e) {
        return;
    }

    const primary = getComputedStyle(document.documentElement).getPropertyValue("--mg-primary").trim() || "#002b5e";

    new window.Chart(canvas, {
        type: "line",
        data: {
            labels,
            datasets: [
                {
                    label: "Pengguna",
                    data: values,
                    spanGaps: false,
                    borderColor: primary,
                    borderWidth: 2.5,
                    pointBackgroundColor: "#ffffff",
                    pointBorderColor: primary,
                    pointBorderWidth: 2.5,
                    pointRadius: 4.5,
                    pointHoverRadius: 6,
                    fill: true,
                    backgroundColor: (context) => {
                        const { ctx, chartArea } = context.chart;
                        if (!chartArea) return null;
                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, "rgba(0, 43, 94, 0.18)");
                        gradient.addColorStop(1, "rgba(0, 43, 94, 0.01)");
                        return gradient;
                    },
                    tension: 0.35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: primary,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: (context) => `${Number(context.parsed.y).toLocaleString("id-ID")} Pengguna`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: "#64748b", font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: "#64748b",
                        font: { size: 11 },
                        callback: (val) => Number(val).toLocaleString("id-ID"),
                    },
                    grid: { color: "#f1f5f9" },
                },
            },
        },
    });
});
