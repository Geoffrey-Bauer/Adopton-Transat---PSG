document.addEventListener('DOMContentLoaded', function() {
    const reservationForm = document.getElementById('reservationForm');
    const cancellationForm = document.getElementById('cancellationForm');
    const dateSelector = document.getElementById('dateSelector');

    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            handleAction(e, 'reserve');
        });
    }

    if (cancellationForm) {
        cancellationForm.addEventListener('submit', function(e) {
            handleAction(e, 'cancel');
        });
    }

    if (dateSelector) {
        flatpickr(dateSelector, {
            dateFormat: "Y-m-d",
            minDate: "today",
            maxDate: new Date().fp_incr(365), // Permet de sélectionner jusqu'à un an à l'avance
            locale: "fr",
            onChange: function(selectedDates, dateStr, instance) {
                window.location.href = `/?date=${dateStr}`;
            }
        });
    }

    // Gestion de l'ouverture des modals
    const reservationModal = document.getElementById('reservationModal');
    if (reservationModal) {
        reservationModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const transatId = button.getAttribute('data-transat-id');
            const startTime = button.getAttribute('data-start-time');
            document.getElementById('reservationTransatId').value = transatId;
            document.getElementById('reservationStartTime').value = startTime;
            document.getElementById('reservationPassword').value = ''; // Réinitialiser le mot de passe
        });
    }

    const cancellationModal = document.getElementById('cancellationModal');
    if (cancellationModal) {
        cancellationModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const transatId = button.getAttribute('data-transat-id');
            const startTime = button.getAttribute('data-start-time');
            document.getElementById('cancellationTransatId').value = transatId;
            document.getElementById('cancellationStartTime').value = startTime;
            document.getElementById('cancellationPassword').value = ''; // Réinitialiser le mot de passe
        });
    }
});

function showAlert(message, type) {
    const messageContainer = document.getElementById('message-container');
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.role = 'alert';
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    messageContainer.innerHTML = ''; // Efface les messages précédents
    messageContainer.appendChild(alertElement);

    // Faire défiler jusqu'au message
    messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Supprimer le message après 5 secondes
    setTimeout(() => {
        alertElement.remove();
    }, 5000);
}

function handleAction(e, action) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const transatId = formData.get('transatId');

    fetch(`/transat/${transatId}/${action}`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(`${action === 'reserve' ? 'Réservation réussie' : 'Annulation réussie'}`, 'success');
                updateUI(transatId, formData.get('startTime'), action);
                closeModal(action);
            } else {
                showAlert(`Échec de ${action === 'reserve' ? 'la réservation' : 'l\'annulation'}: ${data.message}`, 'danger');
            }
        })
        .catch(error => {
            showAlert(`Une erreur est survenue: ${error.message}`, 'danger');
        });
}

function closeModal(action) {
    const modalElement = document.getElementById(`${action}Modal`);
    if (modalElement) {
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        } else {
            // Si l'instance n'existe pas, essayons de fermer manuellement
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            document.body.classList.remove('modal-open');
            const modalBackdrops = document.getElementsByClassName('modal-backdrop');
            for (let backdrop of modalBackdrops) {
                backdrop.remove();
            }
        }
    }
    // Réinitialiser le champ de mot de passe après la fermeture du modal
    const passwordField = document.getElementById(`${action}Password`);
    if (passwordField) {
        passwordField.value = '';
    }
}

function updateUI(transatId, startTime, action) {
    const button = document.querySelector(`[data-transat-id="${transatId}"][data-start-time="${startTime}"]`);
    if (button) {
        const timeSlot = button.closest('.time-slot');
        const statusElement = timeSlot.querySelector('.time-slot-status');

        if (action === 'reserve') {
            statusElement.classList.remove('available');
            statusElement.classList.add('reserved');
            button.classList.remove('btn-reserve');
            button.classList.add('btn-cancel');
            button.textContent = 'Annuler';
            button.setAttribute('data-bs-target', '#cancellationModal');
        } else {
            statusElement.classList.remove('reserved');
            statusElement.classList.add('available');
            button.classList.remove('btn-cancel');
            button.classList.add('btn-reserve');
            button.textContent = 'Réserver';
            button.setAttribute('data-bs-target', '#reservationModal');
        }
    }
}