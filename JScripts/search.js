document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tbody = document.querySelector('.appointments-table tbody');
    let searchTimeout;
    let timeUpdateInterval = null;

    function startTrackingHandler() {
        const uid = this.dataset.uid;
        const appId = this.dataset.appid;
        
        fetch('../Controller/tracking/start_tracking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                uid: uid,
                appId: appId === 'null' ? null : appId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            }
        });
    }

    function endTrackingHandler() {
        const uid = this.dataset.uid;
        const appId = this.dataset.appid;
        
        fetch('../Controller/tracking/end_tracking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                uid: uid,
                appId: appId === 'null' ? null : appId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            }
        });
    }

    function updateTimers() {
        document.querySelectorAll('.tracking-info').forEach(span => {
            const startTime = new Date(span.dataset.start);
            const now = new Date();
            const elapsed = Math.floor((now - startTime) / 1000);
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;
            span.textContent = `Started: ${span.dataset.start} (${hours}h ${minutes}m ${seconds}s elapsed)`;
        });
    }

    function initializeTracking() {

  if (timeUpdateInterval) {
    clearInterval(timeUpdateInterval);
  }

  document.querySelectorAll('.start-tracking').forEach(button => {
    button.removeEventListener('click', startTrackingHandler);
    button.addEventListener('click', startTrackingHandler);
  });

  document.querySelectorAll('.end-tracking').forEach(button => {
    button.removeEventListener('click', endTrackingHandler);
    button.addEventListener('click', endTrackingHandler);
  });

}

    initializeTracking();

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            const searchValue = this.value.trim();

            const formData = new FormData();
            formData.append('search', searchValue);

            fetch('../Controller/tracking/search_users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                tbody.innerHTML = data;
                initializeTracking(); 
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: red;">Error occurred while searching</td></tr>';
            });
        }, 300);
    });
});
