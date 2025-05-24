document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("prescriptionModal");
  const modalBody = document.getElementById("modalBody");
  const closeModal = document.getElementById("closeModal");

  // Open prescription modal
  document.querySelectorAll('.prescribe-btn').forEach(button => {
    button.addEventListener('click', () => {
      const consultId = button.getAttribute('data-consult-id');
      const patientName = button.getAttribute('data-patient-name');

      modalBody.innerHTML = 'Loading...';
      modal.style.display = 'block';

      fetch(`prescriptionForm.php?consult_id=${consultId}&patient_name=${encodeURIComponent(patientName)}`)
        .then(response => response.text())
        .then(html => {
          modalBody.innerHTML = html;

          const form = document.getElementById("prescriptionForm");

          form.onsubmit = function (e) {
            e.preventDefault();

            fetch("submitPrescription.php", {
              method: "POST",
              body: new FormData(form),
            })
              .then(res => res.json())
              .then(data => {
                if (data.status === "success") {
                  alert(data.message);
                  modal.style.display = "none";
                  modalBody.innerHTML = '';
                  form.reset();
                } else {
                  alert("Error: " + data.message);
                }
              })
              .catch(err => {
                console.error("Fetch error:", err);
                alert("An error occurred while submitting the prescription.");
              });
          };
        })
        .catch(err => {
          console.error("Form load error:", err);
          modalBody.innerHTML = `<p style="color: red;">Error loading form.</p>`;
        });
    });
  });

  // Close modal
  closeModal.onclick = () => {
    modal.style.display = "none";
    modalBody.innerHTML = '';
  };

  window.onclick = (event) => {
    if (event.target === modal) {
      modal.style.display = "none";
      modalBody.innerHTML = '';
    }
  };
});
