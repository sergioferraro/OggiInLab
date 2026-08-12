
/* ---- Utility: show inline toast notification ---- */
function showToast(message, type) {
    type = type || 'success';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show shadow" role="alert" style="min-width:300px;">
            <i class="fas ${icon}"></i> ${message}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>`;
    $('#toastContainer').append(alertHtml);
    setTimeout(() => { $('#toastContainer .alert').first().alert('close'); }, 4000);
}

/* ---- Utility: async confirmation dialog (Bootstrap modal) ---- */
function showConfirm(message) {
    return new Promise(function(resolve) {
        let confirmed = false;
        $('#confirmModalBody').text(message);
        $('#confirmModalOk').off('click').on('click', function() {
            confirmed = true;
            $('#confirmModal').modal('hide');
        });
        $('#confirmModal').on('hidden.bs.modal', function handler() {
            $(this).off('hidden.bs.modal', handler);
            resolve(confirmed);
        });
        $('#confirmModal').modal('show');
    });
}

const urlParams = new URLSearchParams(window.location.search);

if (urlParams.has('openModal')) {
  const myModal = new bootstrap.Modal(document.getElementById('addAppointmentModal'));

  // Get Date from URL Parameter
  const dateFromUrl = urlParams.get('date');

  // If the date is present in the URL, set it to the input field of the modal.

  if (dateFromUrl) {
    const dateInput = document.getElementById('appointmentData');
    if (dateInput) {
      dateInput.value = dateFromUrl;
    }
  }

  myModal.show();

  // Remove the 'openModal' and 'date' parameters from the URL after opening the modal
  const cleanUrlParams = new URLSearchParams(urlParams);
  cleanUrlParams.delete('openModal');
  cleanUrlParams.delete('date'); // Remove also 'date' parameter

  let newUrl = window.location.pathname;
  if (cleanUrlParams.toString()) {
      newUrl += '?' + cleanUrlParams.toString();
  }

  window.history.replaceState(null, '', newUrl);
}

$(document).ready(function() {
    // 1) Open the modal and set the hidden project ID
    $('.btn-add-appointment').on('click', function() {
        $('#addAppointmentModal').modal('show');
    });

    // 2) Handle the form submission via AJAX
    $('#addAppointmentForm').on('submit', function(e) {
        e.preventDefault();

        // Serialize form fields into an array so we can append the CSRF token
        const dataArray = $(this).serializeArray();
        dataArray.push({
            name: '_token',
            value: $('#csrfToken').val()
        });

        $.ajax({
            url: 'assets/utils/add-appointment.php',
            type: 'POST',
            data: $.param(dataArray),
            dataType: 'json',
            beforeSend: function() {
                $('#addAppointmentForm button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    showToast('Appuntamento aggiunto con successo.', 'success');
                    $('#addAppointmentModal').modal('hide');
                    window.location.reload();
                } else {
                    showToast('Errore: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Errore nella richiesta AJAX.', 'danger');
            },
            complete: function() {
                $('#addAppointmentForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    const container = $('#appointmentsContainer');

    // Funzione per renderizzare la tabella a partire da un array di appuntamenti
    function renderAppointments(data) {
        if (data.length === 0) {
            container.html('<div class="alert alert-info mt-4" role="alert">Nessun appuntamento trovato.</div>');
            return;
        }

        let html = '<table class="table table-striped mt-4">';
        html += '<thead class="table-dark">';
        html += '<tr>';
        html += '<th>Data</th>';
        html += '<th>Ora Inizio</th>';
        html += '<th>Ora Fine</th>';
        html += '<th>Luogo</th>';
        html += '<th>Descrizione</th>';
        html += '<th>Autore</th>';
        html += '<th>Azione</th>';
        html += '</tr>';
        html += '</thead><tbody>';

        data.forEach(app => {
            html += `<tr>
                <td>${new Date(app.data).toLocaleDateString('it-IT', { year: 'numeric', month: 'long', day: 'numeric' })}</td>
                <td>${app.oraInizio}</td>
                <td>${app.oraFine}</td>
                <td>${app.nAula}</td>
                <td>${app.descrizione.trim() !== '' ? app.descrizione : app.nomeProgetto}</td>
                <td>${app.autore}</td>
                <td>
                    <button class="btn btn-sm btn-primary btn-edit me-1"
                            data-idCorso="${app.idCorso}"
                            data-idAppuntamento="${app.idAppuntamento}"
                            data-data="${app.data}"
                            data-oraInizio="${app.oraInizio}"
                            data-oraFine="${app.oraFine}"
                            data-luogo="${app.luogo}"
                            data-descrizione="${app.descrizione}">
                        <i class="fas fa-edit"></i>Modifica
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" 
                            data-idCorso="${app.idCorso}" 
                            data-idAppuntamento="${app.idAppuntamento}">
                        <i class="fas fa-trash"></i> Annulla
                    </button>
                </td>
            </tr>`;
        });

        html += '</tbody></table>';
        container.html(html);
    }

    // Render iniziale
    renderAppointments(appointmentsData);

    // 3a) On click of the "Edit" button: Populate the modal and show it
    $('#appointmentsContainer').on('click', '.btn-edit', function() {
        const $btn = $(this);
        $('#editAppointmentProjectId').val($btn.data('idcorso'));
        $('#editAppointmentId').val($btn.data('idappuntamento'));
        $('#editAppointmentData').val($btn.data('data'));
        $('#editAppointmentOraInizio').val($btn.data('orainizio'));
        $('#editAppointmentOraFine').val($btn.data('orafine'));
        $('#editAppointmentLuogo').val($btn.data('luogo'));
        $('#editAppointmentDescrizione').val($btn.data('descrizione'));
        $('#editAppointmentModal').modal('show');
    });

    // 3b) Submit del form di Edit via AJAX
    $('#editAppointmentForm').on('submit', function(e) {
        e.preventDefault();

        const dataArray = $(this).serializeArray();
        dataArray.push({
            name: '_token',
            value: $('#csrfToken').val()
        });

        $.ajax({
            url: 'assets/utils/edit-appointment.php',
            type: 'POST',
            data: $.param(dataArray),
            dataType: 'json',
            beforeSend: function() {
                $('#editAppointmentForm button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    showToast('Appuntamento aggiornato con successo.', 'success');
                    $('#editAppointmentModal').modal('hide');
                    window.location.reload();
                } else {
                    showToast('Errore: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Errore nella richiesta AJAX.', 'danger');
            },
            complete: function() {
                $('#editAppointmentForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    /* --- Filtraggio ricerca per progetto e descrizione --- */
    $(document).on('input', '#searchAppointment', function() {
        var query = $(this).val().toLowerCase().trim();

        if (query === '') {
            renderAppointments(appointmentsData);
            return;
        }

        var filtered = appointmentsData.filter(function(app) {
            var nomeProgetto = (app.nomeProgetto || '').toLowerCase();
            var descrizione  = (app.descrizione  || '').toLowerCase();
            return nomeProgetto.indexOf(query) !== -1 || descrizione.indexOf(query) !== -1;
        });

        renderAppointments(filtered);
    });

    $(document).on('click', '#resetSearch', function() {
        $('#searchAppointment').val('');
        renderAppointments(appointmentsData);
    });

    // Delete (invalidate) single appointment
    $('#appointmentsContainer').on('click', '.btn-delete', async function(e) {
        e.preventDefault();
        const $button = $(this);
        const courseId = $button.data('idcorso');
        const appointmentId = $button.data('idappuntamento');
        const csrfToken = $('#csrfToken').val();

        const ok = await showConfirm('Sei sicuro di voler annullare questo appuntamento?');
        if (!ok) return;

        $.ajax({
            url: 'assets/utils/invalida-appointment.php',
            type: 'POST',
            data: {
                idCorso: courseId,
                idAppuntamento: appointmentId,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').remove();
                    showToast('Appuntamento eliminato con successo!', 'success');
                } else {
                    showToast('Errore: ' + response.message, 'danger');
                }
            },
            error: function() {
                showToast('Errore di rete durante l\'eliminazione.', 'danger');
            }
        });
    });
});