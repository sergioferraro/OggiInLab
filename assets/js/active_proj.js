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

function getAppointmentsEndpoint() {
    return 'assets/utils/get_done_appointments.php';
}

$(document).ready(function() {

// Load project details
    function loadProjectDetails(element) {
        const projectId = $(element).data('id');
    
        // Clean container and show
        $('#projectDetailsContainer').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Caricamento...</span></div>');
    
         $.ajax({
            url: 'assets/utils/get_project_details.php',
            method: 'GET',
            data: { id: projectId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Create html content
                    const content = `
                        <h6>${response.progetto.nome_progetto}</h6>
                        <p><strong>Tutor:</strong> ${response.progetto.Tutor_Cognome}</p>
                        <p><strong>Esperto:</strong> ${response.progetto.Esperto_Cognome}</p>
                        <p><strong>Data inizio:</strong> ${response.progetto.start_date}</p>
                        <p><strong>Data fine:</strong> ${response.progetto.end_date}</p>
                        <hr>
                        <p class="mb-0"><strong>Descrizione:</strong></p>
                        <pre>${response.progetto.Desc_Progetto}</pre>
                        </hr>
                    `;
                    
                    // Refresh modal and show details
                    $('#projectDetailsContainer').html(content);
                    $('#projectIdTitle').text(`Progetto ${response.progetto.nome_progetto}`);
                } else {
                    $('#projectDetailsContainer').html('<div class="alert alert-warning">Nessun dettaglio disponibile per questo progetto.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Errore durante il caricamento:', error);
                $('#projectDetailsContainer').html('<div class="alert alert-danger">Errore di rete. Riprova più tardi.</div>');
            }
        });
    }

   

    // Load appointments for a project
    function loadAppointments(projectId) {
        var targetDiv = $('#appointments-' + projectId);
        targetDiv.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Caricamento...</span></div>');

        $.ajax({
            url: 'assets/utils/get_done_appointments.php',
            method: 'GET',
            data: { id: projectId },
            dataType: 'json',
            success: function(response) {
                console.log("AJAX response:", response);

                if (response.success) {
                    var htmlContent = '<table class="table table-bordered">';
                    htmlContent += '<thead><tr><th>Data</th><th>Ora Inizio</th><th>Ora Fine</th><th>Luogo</th><th>Descrizione</th></tr></thead><tbody>';

                    $.each(response.appointments, function(index, appointment){
                        const start = new Date(`${appointment.data}T${appointment.oraInizio}`);
                        const end = new Date(`${appointment.data}T${appointment.oraFine}`);
                        const id = appointment.idAppuntamento;
                        htmlContent += '<tr>';
                        htmlContent += '<td>' + start.toLocaleDateString('it-IT') + '</td>';
                        htmlContent += '<td>' + appointment.oraInizio + '</td>';
                        htmlContent += '<td>' + appointment.oraFine + '</td>';
                        htmlContent += '<td>' + (appointment.luogo ? appointment.luogo : 'N/D') + '</td>';
                        htmlContent += '<td>' + (appointment.descrizione ? appointment.descrizione : 'N/D') + '</td>';
                        htmlContent += '<td>';
                        // Modifica button (only for active projects)
                        if ($('#projectStatus').val() === 'active') {
                            htmlContent += '<button type="button" class="btn btn-sm btn-primary btn-edit me-1"';
                            htmlContent += ' data-id_corso="' + appointment.idCorso + '"';
                            htmlContent += ' data-id_appuntamento="' + appointment.idAppuntamento + '"';
                            htmlContent += ' data-data="' + appointment.data + '"';
                            htmlContent += ' data-ora_inizio="' + appointment.oraInizio + '"';
                            htmlContent += ' data-ora_fine="' + appointment.oraFine + '"';
                            htmlContent += ' data-luogo="' + (appointment.aulaId  || 'N/D') + '"';
                            htmlContent += ' data-descrizione="' + appointment.descrizione + '"';
                            htmlContent += '>';
                            htmlContent += '<i class="fas fa-edit"></i> Modifica';
                            htmlContent += '</button>';
                        }
                        htmlContent += '<button type="button" class="btn-delete-appointment btn btn-danger"';
                        htmlContent += ' data-id_corso="' + appointment.idCorso + '"';
                        htmlContent += ' data-id="' + appointment.idAppuntamento + '"';
                        htmlContent += '>';
                        htmlContent += '<i class="fas fa-trash"></i> Elimina';
                        htmlContent += '</button>';
                        htmlContent += '</td>';
                        htmlContent += '</tr>';
                    });

                    htmlContent += '</tbody></table>';
                    targetDiv.html(htmlContent);
                } else {
                    targetDiv.html('<div class="alert alert-warning">Nessun appuntamento trovato.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Errore nel caricamento degli appuntamenti:', error);
                targetDiv.html('<div class="alert alert-danger">Errore nel caricamento degli appuntamenti.</div>');
            }
        });
    }

    // Show appointments when click on a project
    $(document).on('click', '.project-title', function(){
        var projectId = $(this).data('id');
        console.log("Project title clicked. ID:", projectId);
        loadAppointments(projectId);
    });

    $(document).on('click', '.btn-delete-appointment', async function(e) { 
        e.preventDefault(); 
        const $button = $(this); 
        const appointmentId = $button.data('id'); 
        const projectId = $button.data('id_corso');
        console.log("Id appuntamento:", appointmentId);
        console.log("Id corso:", projectId);
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const ok = await showConfirm("Confermi l'annullamento dell'appuntamento?");
        if (!ok) return;

        $.ajax({
            url: 'assets/utils/invalida-appointment.php',
            method: 'POST',
            data: {
                idCorso: projectId,
                idAppuntamento: appointmentId,
                _token: csrfToken
            },
            beforeSend: function() {
            $button.prop('disabled', true).html('Eliminando...');
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').remove();
                    showToast('Appuntamento eliminato con successo!', 'success');
                    $button.prop('disabled', false).html('Elimina');
                } else {
                    showToast('Errore: ' + response.message, 'danger');
                    $button.prop('disabled', false).html('Elimina');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error, xhr);
                try {
                    const errResponse = JSON.parse(xhr.responseText);
                    showToast('Errore di sistema: ' + (errResponse.message || 'Imprevisto'), 'danger');
                } catch {
                    showToast('Errore critico: ' + error, 'danger');
                }
                $button.prop('disabled', false).html('Elimina');
            }
        });
    });

    $(document).on('click', '.project-click-area', function() {
        $('#projectDetailsModal').modal('show');
        loadProjectDetails($(this));
    });

    $(document).on('click', '.btn-edit', function()  {
        const $btn = $(this);
        console.log('Button data:', $btn.data());

        $('#editAppointmentProjectId').val($btn.data('id_corso'));
        $('#editAppointmentId').val($btn.data('id_appuntamento'));
        $('#editAppointmentData').val($btn.data('data'));
        $('#editAppointmentOraInizio').val($btn.data('ora_inizio'));
        $('#editAppointmentOraFine').val($btn.data('ora_fine'));
        $('#editAppointmentLuogo').val($btn.data('luogo'));
        $('#editAppointmentDescrizione').val($btn.data('descrizione'));
        $('#editAppointmentModal').modal('show');
    });

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
                console.log('AJAX Response:', response); 
                if (response.success) {
                    showToast('Appuntamento aggiornato con successo.', 'success');
                    $('#editAppointmentModal').modal('hide');
                    // Reload list
                    window.location.reload();
                } else {
                    showToast('Errore: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.log('Error Response:', xhr.responseText);
                showToast('Errore nella richiesta AJAX.', 'danger');
            },
            complete: function() {
                $('#editAppointmentForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.btn-add-appointment', function() {
        const projectId = $(this).data('id');
        // Set the hidden input with the project id (foreign key)
        $('#appointmentProjectId').val(projectId);
        
        // Clear the form inputs
        $('#addAppointmentForm')[0].reset();
        
        // Open the modal
        $('#addAppointmentModal').modal('show');
    });

    // Handle the form submission via AJAX
    $('#addAppointmentForm').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();
        // Append CSRF token (the #csrfToken input is outside the form)
        const tokenData = formData + '&' + $.param({ _token: $('#csrfToken').val() });

        $.ajax({
            url: 'assets/utils/add-appointment.php',
            type: 'POST',
            data: tokenData,
            dataType: 'json',
            beforeSend: function() {
                // Optional: Disable the submit button or show a loader
            },
            success: function(response) {
                if (response.success) {
                    showToast('Appuntamento aggiunto con successo.', 'success');
                    $('#addAppointmentModal').modal('hide');

                    var targetDiv = $('#appointments-' + $('#appointmentProjectId').val());
                    if(targetDiv.length) {
                        loadAppointments($('#appointmentProjectId').val());
                    }
                } else {
                    showToast('Errore: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Errore nella richiesta AJAX.', 'danger');
            }
        });
    });

    $(document).on('click', '.btn-delete', async function(e) {
    e.preventDefault();
    const $button = $(this);
    const deleteId = $button.data('id');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Check if there are appointments associated with the project
    $.ajax({
        url: getAppointmentsEndpoint(),
        method: 'GET',
        data: { id: deleteId },
        beforeSend: function() {
            $button.prop('disabled', true).html('Verifica...');
        }
    }).done(async function(response) {
        if (response.success && response.appointments.length > 0) {
            // Appointments exist: show a warning
            showToast("Impossibile eliminare il progetto. Esistono appuntamenti associati.", 'danger');
            $button.prop('disabled', false).html('Elimina');
        } else {
            // No appointments: ask for confirmation and proceed with deletion
            const ok = await showConfirm("Confermi l'eliminazione del progetto?");
            if (!ok) {
                $button.prop('disabled', false).html('Elimina');
            } else {
                $.ajax({
                    url: 'assets/utils/delete-project.php',
                    method: 'POST',
                    data: {
                        delete_id: deleteId,
                        _token: csrfToken
                    },
                    beforeSend: function() {
                        $button.prop('disabled', true).html('Eliminando...');
                    }
                }).done(function(response) {
                    if (response.success) {
                        // Remove the row and restore the button
                        $button.closest('tr').slideUp(300, function() {
                            $(this).remove();
                        });
                        $('#projectDetailsContainer').empty();
                        showToast('Progetto eliminato con successo.', 'success');
                        $button.prop('disabled', false).html('Elimina');
                    } else {
                        showToast('Errore: ' + response.message, 'danger');
                        $button.prop('disabled', false).html('Elimina');
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX Error:', error, xhr);
                    try {
                        const errResponse = JSON.parse(xhr.responseText);
                        showToast('Errore di sistema: ' + (errResponse.message || 'Imprevisto'), 'danger');
                    } catch {
                        showToast('Errore critico: ' + error, 'danger');
                    }
                    $button.prop('disabled', false).html('Elimina');
                });
            }
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error (verifica appuntamenti):', error, xhr);
        showToast('Errore durante il controllo degli appuntamenti.', 'danger');
        $button.prop('disabled', false).html('Elimina');
    });
});
}); // close $(document).ready

    /* --- Filtraggio ricerca per nome progetto --- */
    $(document).on('input', '#searchProjectName', function() {
        var query = $(this).val().toLowerCase().trim();

        $('.project-item').each(function() {
            var projectName = $(this).find('.project-title').text().toLowerCase();
            var match = projectName.indexOf(query) !== -1;
            $(this).toggle(match);
            // Nascondi anche la riga collassabile degli appuntamenti associata
            $(this).next('.collapse-row').toggle(match);
        });
    });

    $(document).on('click', '#resetSearch', function() {
        $('#searchProjectName').val('');
        $('.project-item').show();
        $('.collapse-row').show();
    });

    /* --- Scrolling animation for truncated project text --- */
    $(document).ready(function() {
        function initScrollingText() {
            $('.scrollable-text').each(function() {
                var $el = $(this);
                // Check if text overflows its container
                if (this.scrollWidth > this.clientWidth) {
                    // Store original content first, then duplicate for seamless infinite loop
                    var originalContent = $el.html();
                    $el.html(originalContent + '&nbsp;&nbsp;&nbsp;&nbsp;' + originalContent);
                }
            });
        }

        // Run after DOM is ready and again after a short delay (for Bootstrap collapse rendering)
        initScrollingText();
        setTimeout(initScrollingText, 300);
    });
