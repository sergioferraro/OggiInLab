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

$(document).ready(function() {

    // Delete ALL soft-deleted appointments
    $('#btnDeleteAll').on('click', async function() {
        const ok = await showConfirm('Sei sicuro di voler eliminare DEFINITIVAMENTE tutti gli appuntamenti invalidati? Questa azione non può essere annullata.');
        if (!ok) return;

        const $btn = $(this);
        const csrfToken = $('#csrfToken').val();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Eliminazione...');

        $.ajax({
            url: 'assets/utils/delete-all-deleted.php',
            type: 'POST',
            data: { _token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    window.location.reload();
                } else {
                    showToast('Errore: ' + response.message, 'danger');
                    $btn.prop('disabled', false).html('<i class="fas fa-trash-alt"></i> Elimina tutti gli appuntamenti invalidati');
                }
            },
            error: function() {
                showToast('Errore di rete durante l\'eliminazione.', 'danger');
                $btn.prop('disabled', false).html('<i class="fas fa-trash-alt"></i> Elimina tutti gli appuntamenti invalidati');
            }
        });
    });

    const container = $('#appointmentsContainer');

    if (appointmentsData.length === 0) {
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
    html += '<th>Azione</th> ';
    html += '</tr>';
    html += '</thead><tbody>';

    appointmentsData.forEach(app => {
        html += `<tr>
            <td>${app.data}</td>
            <td>${app.oraInizio}</td>
            <td>${app.oraFine}</td>
            <td>${app.nAula}</td>
            <td>${app.descrizione}</td>
            <td>
                <button class="btn btn-sm btn-danger btn-delete" 
                        data-idCorso="${app.idCorso}" 
                        data-idAppuntamento="${app.idAppuntamento}">
                    <i class="fas fa-trash"></i> Elimina
                </button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.html(html);

    // Click on Edit: Populate Modal and Show It
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

    // Delete single appointment
    $('#appointmentsContainer').on('click', '.btn-delete', async function(e) {
        e.preventDefault();
        const $button = $(this);
        const courseId = $button.data('idcorso');
        const appointmentId = $button.data('idappuntamento');
        const csrfToken = $('#csrfToken').val();

        const ok = await showConfirm('Sei sicuro di voler eliminare questo appuntamento?');
        if (!ok) return;

        $.ajax({
            url: 'assets/utils/delete-appointment.php',
            type: 'POST',
            data: {
                idCorso: courseId,
                idAppuntamento: appointmentId,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').remove();
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