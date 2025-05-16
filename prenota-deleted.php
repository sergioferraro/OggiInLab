<?php
// prenota-deleted.php
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
$projectId = null;
try {
    $projectQuery = "SELECT idProgetto FROM progetto";
    $projectStmt = $dbh->prepare($projectQuery);
    $projectStmt->execute();
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
    $projectId = $project['idProgetto'] ?? null;
} catch (PDOException $e) {
    error_log("Errore recupero ID progetto: " . $e->getMessage());
}
$appointments = []; // Initialize as empty array
try {
    // Added DISTINCT to Luogo query
    $query = "SELECT DISTINCT
					appuntamento.idCorso, 
					appuntamento.idAppuntamento,
					appuntamento.descrizione,
					data,
                    aula.nAula,
					LEFT(oraInizio, 5) AS oraInizio, 
                    LEFT(oraFine, 5) AS oraFine, 
					appuntamento.luogo
					FROM appuntamento
					LEFT JOIN progetto ON idCorso = progetto.idProgetto
                    LEFT JOIN aula ON appuntamento.luogo = aula.idAula
					WHERE 
					appuntamento.isDeleted = 1
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
    <title>OggiInLab | Appuntamenti annullati</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME STYLE  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background-color: #1e1e1e;
            color: #f8f9fa;
        }
        .card {
            background-color: #2c2c2c !important;
            border-color: #444;
        }
        .btn-link.text-primary {
            color: #0d6efd !important;
        }
        .bg-dark {
        background-color: #1e1e1e !important;
        }
        .text-white {
            color: #f8f9fa !important;
        }
        .form-control.bg-dark {
            background-color: #1e1e1e;
            color: #f8f9fa;
            border-color: #444;
        }
        .btn.btn-primary {
        background-color: #0d6efd !important; /* Light Blue */
        border-color: #0d6efd !important;
        color: white !important;
        }

        .btn.btn-primary:hover {
            background-color: #0a58ca !important; /* Darker Blue on Hover */
            border-color: #0a58ca !important;
            color: white !important;
        }
        .form-label {
        font-weight: bold;
        margin-bottom: 5px;
        }

        /* Style for File Input */
        input[type="file"] {
            padding: 10px !important;
            border-radius: 4px;
        }
        input[type="file"] {
            background-color: #1e1e1e !important;
            color: #f8f9fa !important;
            border: 2px solid #444 !important;
            padding: 10px !important;
            border-radius: 4px !important;
        }
        .btn-link.text-primary {
            font-size: 1.2rem; /*  Emoji Sizes */
            padding: 0;
        }

        /* Distance Between the Image and the Like Button */
        form.d-inline.mt-2 {
            margin-top: 15px;
        }
        .form-control {
            background-color: #5c5e62 !important;
            color: white !important;
        }
        .form-select {
            background-color: #5c5e62 !important;
            color: white !important;
        }

    </style>
</head>

<body>
<?php include "includes/header.php"; ?>

<div class="container mt-5">
    <div class="row mb-4">
        <h4 class="text-center w-100">Elenco prenotazioni annullate</h4>
    </div>
    <!-- Container to hold dynamically generated appointments -->
    <div id="appointmentsContainer"></div>
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

$(document).ready(function() {
   
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
    html += '<th>Azione</th>'; 
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
// 3a) Click on Edit: Populate Modal and Show It
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
    console.log(appointmentsData);

});
// 3b) Submit the Edit Form via AJAX
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
