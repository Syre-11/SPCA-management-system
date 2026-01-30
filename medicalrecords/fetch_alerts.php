<script>
function fetchAlerts() {
    fetch('fetch_alerts.php')
        .then(response => response.json())
        .then(data => {
            const alertsContainer = document.querySelector('.alerts');
            if (!alertsContainer) return;

            let html = '<h2>⚠️ Alerts & Reminders</h2>';
            if (data.length > 0) {
                data.forEach(alert => {
                    html += `
                        <div class="alert">
                            💉 ${alert.Animal_Name} (${alert.Animal_Species}) - 
                            ${alert.status} - Due: ${alert.Due_Date}
                        </div>
                    `;
                });
            } else {
                html += `<div class="alert">No pending alerts</div>`;
            }
            alertsContainer.innerHTML = html;
        })
        .catch(error => {
            console.error("Error fetching alerts:", error);
        });
}

// Initial fetch
fetchAlerts();
// Refresh every 15 seconds
setInterval(fetchAlerts, 15000);
</script>
