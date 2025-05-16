<?php
// all_project.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "includes/config.php";

if (empty($_SESSION["alogin"])) {
    header("location: index.php");
    exit();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <!--[if IE]>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <![endif]-->
    <title>OggiInLab | Progetti terminati</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME STYLE  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>
<?php include "includes/header.php"; ?>

<div class="container mt-5">
    <div class="row">
        <h4 class="mb-3 text-center">Pannello di controllo amministratore</h4>

        
        <!-- Progetti Count Card -->
        <div class="col-md-6 mb-4">
            <div class="card bg-light" style="max-width: 22rem;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Progetti Attivi</span>
                    <!-- Button trigger for AJAX -->
                    <button type="button" id="viewProjectsButton" 
                            class="btn btn-primary"
                            data-bs-toggle="collapse"
                            href="#projectsList"
                            role="button"
                            aria-expanded="false"
                            aria-controls="projectsList">
                        Progetti terminati
                    </button>
                </div>

                <div class="card-body text-center">
                    <?php
                        try {
                            // Ensure column names match your database schema exactly!
                            $sql = "SELECT idProgetto FROM progetto 
                                    WHERE (endDate < CURRENT_DATE) -- Group date conditions with parentheses
                                    AND (progetto.descProgetto != 'prenotaaulagiornaliero') AND (progetto.descProgetto != 'orario delle lezioni')";
                            $query = $dbh->prepare($sql);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_NUM);

                            // Count the number of active projects
                            $listprogetti = count($results); 

                        } catch (PDOException $e) {
                            // Log error instead of displaying raw message for production
                            error_log("Database query error: " . $e->getMessage());
                            $listprogetti = 0; 
                        }
                    ?>

                    <i class="bi bi-list-ul fa-5x"></i>
                    <h3 id="projectCount"><?php echo intval($listprogetti); ?></h3>
                </div>
                
            </div>
            
        </div>

    </div>
    


        <div id="projectsList" class="col-md-12 collapse mt-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Risultati dei Progetti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="collapse"></button>
                </div>

                <div class="card-body p-3">
    <?php
        try {
            $sql = "SELECT 
                p.idProgetto,
                p.nomeProgetto,
                p.descProgetto,
                p.idTutor,
                p.idEsperto,
                p.endDate,
                d.cognome AS Tutor_Cognome,
                d2.cognome AS Esperto_Cognome
            FROM progetto p
            LEFT JOIN docente d ON p.idTutor = d.idDocente
            LEFT JOIN docente d2 ON p.idEsperto = d2.idDocente
            WHERE (endDate < CURRENT_DATE) 
            AND (p.descProgetto != 'prenotaaulagiornaliero') AND (p.descProgetto != 'orario delle lezioni')";
            
            $query = $dbh->prepare($sql);
            $query->execute();
            $projects = $query->fetchAll(PDO::FETCH_ASSOC);

            if (empty($projects)) {
                echo "<div class='alert alert-warning'>Nessun progetto trovato.</div>";
            } else {
    ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nome Progetto</th>
                            <th>Descrizione</th>
                            <th>Tutor</th>
                            <th>Esperto</th>
                            <th>Data Fine</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projects as $project): ?>
                        <!-- Project Row -->
                        <tr class="project-item">
                            <td>
                                <!-- Button to toggle collapsible appointments -->
                                <button type="button" class="btn btn-link project-title" 
                                        data-bs-toggle="collapse"
                                        data-bs-target="#appointments-<?= htmlspecialchars($project['idProgetto']) ?>"
                                        aria-expanded="true"
                                        aria-controls="appointments-<?= htmlspecialchars($project['idProgetto']) ?>"
                                        data-id="<?= htmlspecialchars($project['idProgetto']) ?>">
                                    <?= htmlspecialchars($project['nomeProgetto']) ?>
                                </button>
                            </td>
                            <td><?= htmlspecialchars($project['descProgetto']) ?></td>
                            <td><?= htmlspecialchars($project['Tutor_Cognome'] ?? 'N/D') ?></td>
                            <td><?= htmlspecialchars($project['Esperto_Cognome'] ?? 'N/D') ?></td>
                            <td>
                                <?php 
                                    $end_date = $project['endDate'];
                                    echo !empty($end_date) ? date("d-m-Y", strtotime($end_date)) : 'N/D';
                                ?>
                            </td>
                            <td>
                            <button type="button" class="btn-delete btn btn-danger" data-id="<?= htmlspecialchars($project['idProgetto']) ?>">
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                            <a href="manage-project.php?id=<?= htmlspecialchars($project['idProgetto']) ?>" class="btn btn-primary btn-modify">
                                <i class="fas fa-edit"></i> Modifica
                            </a>
                            
                            <a href="assets/utils/print_project.php?id=<?= htmlspecialchars($project['idProgetto']) ?>" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="btn btn-secondary btn-print">
                                    <i class="fas fa-print"></i> Stampa
                                </a>
                            </td>
                        </tr>
                        <!-- Collapsible Row for Appointments -->
                        <tr class="collapse-row">
                            <td colspan="6">
                                <div id="appointments-<?= htmlspecialchars($project['idProgetto']) ?>" class="collapse appointments-container">
                                    <!-- Appointments loaded via AJAX will appear here -->
                                </div>
                            </td>
                        </tr>
<?php endforeach; ?>

                
                    </tbody>
                </table>
    <?php
            }
        } catch (PDOException $e) {
            error_log("Error fetching projects: " . $e->getMessage());
            echo "<div class='alert alert-danger'>Errore nel recupero dei progetti.</div>";
        }
    ?>
</div>




                </div>
                
            </div>
        </div>

    </div>
</div>


<!-- Modal: Edit Appointment -->
<div class="modal fade" id="editAppointmentModal" tabindex="-1" aria-labelledby="editAppointmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editAppointmentForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editAppointmentModalLabel">Modifica Appuntamento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body">
            <!-- Hidden fields -->
            <input type="hidden" name="idCorso" id="editAppointmentProjectId">
            <input type="hidden" name="idAppuntamento" id="editAppointmentId">

            <div class="mb-3">
                <label for="editAppointmentData" class="form-label">Data</label>
                <input type="date" class="form-control" id="editAppointmentData" name="data" required>
            </div>
            <div class="mb-3">
                <label for="editAppointmentOraInizio" class="form-label">Ora Inizio</label>
                <input type="time" class="form-control" id="editAppointmentOraInizio" name="oraInizio" required>
            </div>
            <div class="mb-3">
                <label for="editAppointmentOraFine" class="form-label">Ora Fine</label>
                <input type="time" class="form-control" id="editAppointmentOraFine" name="oraFine" required>
            </div>
            <div class="mb-3">
                <label for="editAppointmentLuogo" class="form-label">Luogo</label>
                <select class="form-control" id="editAppointmentLuogo" name="luogo" required>
                    <option value="">Seleziona Aula</option>
                    <?php
                    try {
                        $sql = "SELECT idAula, nAula FROM aula ORDER BY nAula ASC";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='" . htmlspecialchars($row['idAula']) . "'>"
                                 . htmlspecialchars($row['nAula']) . "</option>";
                        }
                    } catch (PDOException $e) {
                        error_log("Errore nel recupero delle aule: " . $e->getMessage());
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Aggiorna Appuntamento</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- Add modal for project details -->
<div id="projectDetailsModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectIdTitle">Dettagli Progetto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="projectDetailsContainer">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Caricamento...</span>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
<?php include 'includes/footer.php';?>



<!-- SCRIPTS -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

<script> 
    $(document).ready(function() {

// Load project details when clicking on an element
    function loadProjectDetails(element) {
        const projectId = $(element).data('id');
    
        // Clear the container and show loader
        $('#projectDetailsContainer').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Caricamento...</span></div>');
    
         $.ajax({
            url: 'assets/utils/get_project_details.php',
            method: 'GET',
            data: { id: projectId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Create HTML content with details
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
                    
                    // Update modal and show details
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
});
    // Show the modal when clicking on a project
    $(document).on('click', '.project-title', function(){
        var projectId = $(this).data('id');
        var targetDiv = $('#appointments-' + projectId);

        // Log for debugging
        console.log("Project title clicked. ID:", projectId);

        // If the appointments container is empty, load the appointments via AJAX
        // if (targetDiv.is(':empty')) 
        {
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
    });

    $(document).on('click', '.btn-delete-appointment', function(e) { 
        e.preventDefault(); 
        const $button = $(this); 
        const appointmentId = $button.data('id'); 
        const projectId = $button.data('id_corso'); // Ensure row has project ID
        console.log("Id appuntamento:", appointmentId);
        console.log("Id corso:", projectId);
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        if (confirm("Confermi l'annullamento dell'appuntamento?")) {
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
                        $button.prop('disabled', false).html('Elimina');
                    } else {
                        alert('Errore: ' + response.message);
                        $button.prop('disabled', false).html('Elimina');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error, xhr);
                    try {
                        const errResponse = JSON.parse(xhr.responseText);
                        alert('Errore di sistema: ' + (errResponse.message || 'Imprevisto'));
                        } catch {
                            alert('Errore critico: ' + error);
                        }
                        $button.prop('disabled', false).html('Elimina');
                    }
                });
            }
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
                    alert('Appuntamento aggiornato con successo.');
                    $('#editAppointmentModal').modal('hide');
                    // Reload the list: for simplicity
                    window.location.reload();
                } else {
                    alert('Errore: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.log('Error Response:', xhr.responseText); 
                alert('Errore nella richiesta AJAX.');
            },
            complete: function() {
                $('#editAppointmentForm button[type="submit"]').prop('disabled', false);
            }
        });
    });


    

    // Handle the form submission via AJAX
    $('#addAppointmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'assets/utils/add-appointment.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                // Optional: Disable the submit button or show a loader
            },
            success: function(response) {
                if (response.success) {
                    // Appointment successfully added.
                    alert('Appuntamento aggiunto con successo.');
                    $('#addAppointmentModal').modal('hide');

                    // Optionally, refresh the appointment list for the current project.
                    // For example, if you already have the appointments div open:
                    var targetDiv = $('#appointments-' + $('#appointmentProjectId').val());
                    if(targetDiv.length) {
                        targetDiv.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Caricamento...</span></div>');
                        $.ajax({
                            url: 'assets/utils/get_done_appointments.php',
                            method: 'GET',
                            data: { id: $('#appointmentProjectId').val() },
                            dataType: 'json',
                            success: function(response) {
                                if(response.success) {
                                    var htmlContent = '<table class="table table-bordered">';
                                    htmlContent += '<thead><tr><th>Data</th><th>Ora Inizio</th><th>Ora Fine</th><th>Luogo</th><th>Azioni</th></tr></thead><tbody>';
                                    $.each(response.appointments, function(index, appointment) {
                                        htmlContent += '<tr>';
                                        htmlContent += '<td>' + appointment.data + '</td>';
                                        htmlContent += '<td>' + appointment.oraInizio + '</td>';
                                        htmlContent += '<td>' + appointment.oraFine + '</td>';
                                        htmlContent += '<td>' + (appointment.luogo ? appointment.luogo : 'N/D') + '</td>';
                                        htmlContent += '<td>';
                                        htmlContent += '<button type="button" class="btn-delete-appointment btn btn-danger"';
                                        htmlContent += ' data-id-corso="' + appointment.idCorso + '"';
                                        htmlContent += ' data-id="' + appointment.idAppuntamento + '">';
                                        htmlContent += '<i class="fas fa-trash"></i> Annulla';
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

                } else {
                    alert('Errore: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Errore nella richiesta AJAX.');
            }
        });
    });

    // Handle project deletion
    $(document).on('click', '.btn-delete', function(e) {
    e.preventDefault();

    const $button = $(this);
    const deleteId = $button.data('id');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    console.log("Deleting ID:", deleteId, "Token:", csrfToken);
    if (confirm("Confermi l'eliminazione del progetto?")) {
        $.ajax({
            url: 'assets/utils/delete-project.php',
            method: 'POST',
            data: {
                delete_id: deleteId,
                _token: csrfToken
            },
            beforeSend: function() {
                $button.prop('disabled', true).html('Eliminando...');
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').slideUp(300, function() {
                        $(this).remove();
                        // Update project count
                        // const currentCount = parseInt($('#projectCount').text());
                        // $('#projectCount').text(currentCount - 1);
                    });
                    $('#projectDetailsContainer').empty();
                    $button.prop('disabled', false).html('Elimina');
                } else {
                    alert('Errore: ' + response.message);
                    $button.prop('disabled', false).html('Elimina');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error, xhr);
                try {
                    const errResponse = JSON.parse(xhr.responseText);
                    alert('Errore di sistema: ' + (errResponse.message || 'Imprevisto'));
                } catch {
                    alert('Errore critico: ' + error);
                }
                $button.prop('disabled', false).html('Elimina');
            }
        });
    }
    });
   



</script>
</body>
</html>
