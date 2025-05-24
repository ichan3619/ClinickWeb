document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tbody = document.querySelector('.appointments-table tbody');
    let searchTimeout;
    let timeUpdateInterval = null;

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


    function startTrackingHandler() {

        this.disabled = true;
        
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
        })
        .catch(() => {
            this.disabled = false; 
        });
    }

    function endTrackingHandler() {

        this.disabled = true;
        
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
        })
        .catch(() => {
            this.disabled = false;
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
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            newButton.addEventListener('click', startTrackingHandler);
        });

        document.querySelectorAll('.end-tracking').forEach(button => {
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            newButton.addEventListener('click', endTrackingHandler);
        });

        if (document.querySelectorAll('.tracking-info').length > 0) {
            updateTimers();
            timeUpdateInterval = setInterval(updateTimers, 1000);
        }
    }

    initializeTracking();
});