document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('prescriptionModal');
    const closeBtn = document.getElementById('closeModal');
    const modalBody = document.getElementById('modalBody');

    modal.style.display = 'none';

    // Open modal with prescription form
    document.querySelectorAll('.prescribe-btn').forEach(button => {
        button.addEventListener('click', function () {
            const patientName = this.dataset.patientName || 'Unknown Patient';
            const appID = this.dataset.appId || '';

            fetch(`prescription.php?patient_name=${encodeURIComponent(patientName)}&appID=${encodeURIComponent(appID)}`)
                .then(response => response.text())
                .then(data => {
                    modalBody.innerHTML = data;
                    modal.style.display = 'flex';
                })
                .catch(error => {
                    console.error("Fetch error:", error);
                    modalBody.innerHTML = `<p style="color:red;">Failed to load content.</p>`;
                    modal.style.display = 'flex';
                });
        });
    });

    // Handle modal form submission using fetch
    modalBody.addEventListener('submit', function (e) {
        if (e.target && e.target.matches('#prescriptionForm')) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        alert(data.message);
                        modal.style.display = 'none';
                        location.reload(); // reload to reflect changes
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Submission error:', error);
                    alert("An unexpected error occurred.");
                });
        }
    });



    closeBtn.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    window.addEventListener('beforeunload', function () {
        modal.style.display = 'none';
    });
});

  // Event delegation to handle future buttons too
document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('remove-btn')) {
        console.log("Remove button clicked");
        const row = e.target.closest('tr');
        const prescribeBtn = row.querySelector('.prescribe-btn');
        const appID = prescribeBtn?.dataset.appId;

        if (!appID) {
            alert("Appointment ID not found.");
            return;
        }

        if (confirm("Are you sure you want to remove this patient from the list?")) {
            fetch('removePatient.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `appID=${encodeURIComponent(appID)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Optional fade-out
                    row.style.transition = 'opacity 0.4s';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 400);

                    alert(data.message);
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error("AJAX Error:", error);
                alert("An error occurred while updating.");
            });
        }
    }
});

//Link insertion if Online
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.save-link-btn').forEach(function (button) {
    button.addEventListener('click', function () {
      const appID = this.getAttribute('data-app-id');
      const input = document.querySelector(`.link-input[data-app-id='${appID}']`);
      const link = input.value.trim();

      if (link === '') {
        alert('Please enter a valid link.');
        return;
      }

      fetch('updateLink.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `appID=${encodeURIComponent(appID)}&link=${encodeURIComponent(link)}`
      })
      .then(response => response.text())
      .then(data => {
        alert(data);
        location.reload(); // Reload to reflect updated link
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
      });
    });
  });
});
