<?php 
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
// prenota-day.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "includes/config.php";
require_once __DIR__ . '/includes/Logger.php';

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
<?php
$pageTitle = 'OggiInLab | Prenotazioni';
$pageCsrf = true;
$pageScriptFiles = ['assets/js/prenota-day.js'];
?>
<?php include('includes/header.php'); ?>

<div class="container mt-5">
    <div class="row mb-4">
        <h4 class="text-center w-100">Elenco prenotazioni</h4>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" id="searchAppointment" class="form-control" placeholder="Cerca per progetto o descrizione...">
        </div>
        <div class="col-md-6 d-flex justify-content-end align-items-center gap-2">
            <button type="button" id="resetSearch" class="btn btn-secondary">
                <i class="fas fa-times"></i> Reset
            </button>
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
<!-- Toast container for notifications -->
<div id="toastContainer" class="fixed-top" style="z-index:9999; padding:15px;"></div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Conferma</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody"></div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger" id="confirmModalOk">Conferma</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csrfToken" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />    

<script>
// Convert PHP array to JavaScript object
const appointmentsData = <?php echo json_encode($appointments); ?>;
</script>

<?php include "includes/footer.php";?>
