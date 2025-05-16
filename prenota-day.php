<?php 
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
// prenota-day.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "includes/config.php";

if (empty($_SESSION["alogin"])) {
    header("location: index.php");
    exit();
}
$projects = [];
try {
    $projectQuery = "SELECT idProgetto, nomeProgetto FROM progetto WHERE endDate>=CURRENT_DATE OR endDate IS NULL";
    $projectStmt = $dbh->prepare($projectQuery);
    $projectStmt->execute();
    $projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC); // Retrieve all projects
} catch (PDOException $e) {
    error_log("Errore recupero progetti: " . $e->getMessage());
}

$appointments = []; // Initialize as empty array
try {
    // Added DISTINCT to Luogo query
    $query = "SELECT DISTINCT
					appuntamento.idCorso, 
					appuntamento.idAppuntamento,
					appuntamento.descrizione,
                    progetto.nomeProgetto,
					data,
                    aula.nAula,
					LEFT(oraInizio, 5) AS oraInizio, 
                    LEFT(oraFine, 5) AS oraFine, 
					appuntamento.luogo,
                    admin.nomeCompleto AS autore
					FROM appuntamento
					JOIN progetto ON idCorso = progetto.idProgetto
                    LEFT JOIN aula ON appuntamento.luogo = aula.idAula
                    LEFT JOIN admin ON appuntamento.autore = admin.id
					WHERE appuntamento.isDeleted = 0
					AND Data >= CURRENT_DATE
					ORDER BY Data, oraInizio;";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $appointments = []; // Ensure it's an empty array on error
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>OggiInLab | Prenotazioni</title>
    <!-- BOOTSTRAP CORE STYLE (CYBORG) -->
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
    <div class="row mb-4">
        <h4 class="text-center w-100">Elenco prenotazioni</h4>
    </div>
    <div class="d-flex justify-content-end mb-3">
        <!-- Add appointment button -->
        <button type="button" class="btn btn-success btn-add-appointment" data-id="<?= htmlspecialchars($projectId ?? '') ?>">
            <i class="fas fa-plus"></i> Aggiungi prenotazione
        </button>
        <a href="assets/utils/print_app.php" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="btn btn-secondary btn-print">
                                    <i class="fas fa-print"></i> Stampa
                                </a>
        </div>
    <!-- Container to hold dynamically generated appointments -->
    <div id="appointmentsContainer"></div>
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
            <div class="mb-3">
                <label for="editAppointmentDescrizione" class="form-label">Descrizione</label>
                <input type="text" class="form-control" id="editAppointmentDescrizione" name="descrizione">
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
    
<!-- Modal: Add Appointment -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addAppointmentForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addAppointmentModalLabel">Aggiungi prenotazione</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
            
            <div class="modal-body">
            <div class="mb-3">
                <!-- Hidden field to store project ID (ID_Corso) -->
                <select class="form-control" id="appointmentProjectId" name="idCorso" required>
                    <option value="">Seleziona Progetto</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= htmlspecialchars($project['idProgetto']) ?>">
                            <?= htmlspecialchars($project['nomeProgetto']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="appointmentData" class="form-label">Data</label>
                <input type="date" class="form-control" id="appointmentData" name="data" required>
            </div>
            <div class="mb-3">
                <label for="appointmentOraInizio" class="form-label">Ora Inizio</label>
                <input type="time" class="form-control" id="appointmentOraInizio" name="oraInizio" required>
            </div>
            <div class="mb-3">
                <label for="appointmentOraFine" class="form-label">Ora Fine</label>
                <input type="time" class="form-control" id="appointmentOraFine" name="oraFine" required>
            </div>
            <div class="mb-3">
                <label for="appointmentLuogo" class="form-label">Luogo</label>
                <select class="form-control" id="appointmentLuogo" name="luogo" required>
                    <option value="">Seleziona Aula</option>
                    <?php
                    try {
                        $sql = "SELECT idAula, nAula FROM aula ORDER BY nAula ASC";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='" . htmlspecialchars($row['idAula']) . "'>" . htmlspecialchars($row['nAula']) . "</option>";
                        }
                    } catch (PDOException $e) {
                        error_log("Errore nel recupero delle aule: " . $e->getMessage());
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="appointmentDescrizione" class="form-label">Descrizione</label>
                <input type="text" class="form-control" id="appointmentDescrizione" name="descrizione" >
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Salva Appuntamento</button>
        </div>
      </div>
    </form>
  </div>
</div>
<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />    

<?php include 'includes/footer.php';?>

<!-- SCRIPTS -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script>
// Convert PHP array to JavaScript object
const appointmentsData = <?php echo json_encode($appointments); ?>;
</script>

<script>
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
                // e.g. disable submit button: 
                $('#addAppointmentForm button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    alert('Appuntamento aggiunto con successo.');
                    $('#addAppointmentModal').modal('hide');
                    // Refresh
                    window.location.reload();
                } else {
                    alert('Errore: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Errore nella richiesta AJAX.');
            },
            complete: function() {
                // Always restore the button's state
                $('#addAppointmentForm button[type="submit"]').prop('disabled', false);
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
    html += '<th>Autore</th>';
    html += '<th>Azione</th>'; // New column header
    html += '</tr>';
    html += '</thead><tbody>';
    
    appointmentsData.forEach(app => {
        
        // if descrizione is empty, then use nomeProgetto
        html += `<tr>
            <td>${new Date(app.data).toLocaleDateString('it-IT', { year: 'numeric', month: 'long', day: 'numeric' })}</td> <!-- Modified line -->
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
    // 3a) On click of the "Edit" button: + Populate the modal. + Show the content in the modal
$('#appointmentsContainer').on('click', '.btn-edit', function() {
    const $btn = $(this);
      // Log all data attributes first (for quick debugging)
      console.log('Button data:', $btn.data());
    
    
    $('#editAppointmentProjectId').val($btn.data('idcorso'));
    $('#editAppointmentId').val($btn.data('idappuntamento'));
    $('#editAppointmentData').val($btn.data('data'));
    $('#editAppointmentOraInizio').val($btn.data('orainizio'));
    $('#editAppointmentOraFine').val($btn.data('orafine'));
    $('#editAppointmentLuogo').val($btn.data('luogo'));
    $('#editAppointmentDescrizione').val($btn.data('descrizione'));
    $('#editAppointmentModal').modal('show');
    console.log(appointmentsData);

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
            console.log('AJAX Response:', response); 
            if (response.success) {
                alert('Appuntamento aggiornato con successo.');
                $('#editAppointmentModal').modal('hide');
                // Refresh
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

    $('#appointmentsContainer').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const $button = $(this);
        const courseId = $button.data('idcorso');
        const appointmentId = $button.data('idappuntamento');
        const csrfToken = $('#csrfToken').val();

        if (!confirm('Sei sicuro di voler eliminare questo appuntamento?')) {
            return;
        }

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
                    alert('Appuntamento eliminato con successo!');
                } else {
                    alert('Errore: ' + response.message);
                }
            },
            error: function() {
                alert('Errore di rete durante l\'eliminazione.');
            }
        });
    });
});
</script>
</body>
</html>
