<?php
// active_proj.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
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

// Determine which projects to show: active (default) or done
$status = isset($_GET['status']) && $_GET['status'] === 'done' ? 'done' : 'active';

$pageTitle = 'OggiInLab | ' . ($status === 'active' ? 'Progetti attivi' : 'Progetti terminati');
$pageCsrf  = true;
$pageScriptFiles = ['assets/js/active_proj.js'];
$pageStyles = '
/* --- Scrolling text for long project names/descriptions --- */
.scrollable-text {
    display: inline-block;
    white-space: nowrap;
    box-sizing: border-box;
    animation: scroll-left 12s linear infinite;
}
.scrollable-text:hover {
    animation-play-state: paused;
}
@keyframes scroll-left {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* Constrain columns so text can overflow */
table.table-striped th:nth-child(1),
table.table-striped td:nth-child(1) {
    width: 180px;
    max-width: 180px;
    overflow: hidden;
}
table.table-striped th:nth-child(2),
table.table-striped td:nth-child(2) {
    width: 220px;
    max-width: 220px;
    overflow: hidden;
}

/* Ensure project-title button doesn\'t expand the cell */
.project-title {
    width: 100%;
    text-align: left;
    padding: 2px 4px;
}

/* Custom tooltip for truncated text */
.truncated-cell {
    position: relative;
    cursor: default;
}
.truncated-cell::after {
    content: attr(data-full-text);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #212529;
    color: #f8f9fa;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 0.85rem;
    white-space: normal;
    max-width: 320px;
    word-wrap: break-word;
    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 1000;
}
.truncated-cell:hover::after {
    opacity: 1;
    visibility: visible;
}
';
?>
<?php include "includes/header.php"; ?>

<div class="container mt-5">
    <div class="row">
        <h4 class="mb-3 text-center">Pannello di controllo amministratore</h4>
        <!-- Progetti Count Card -->
        <div class="col-md-6 mb-4">
            <div class="card bg-light" style="max-width: 22rem;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><?= $status === 'active' ? 'Progetti Attivi' : 'Progetti Terminati' ?></span>
                    <!-- Button trigger for AJAX -->
                    <button type="button" id="viewProjectsButton" 
                            class="btn btn-primary"
                            data-bs-toggle="collapse"
                            href="#projectsList"
                            role="button"
                            aria-expanded="false"
                            aria-controls="projectsList">
                        <?= $status === 'active' ? 'Visualizza progetti' : 'Progetti terminati' ?>
                    </button>
                </div>

                <div class="card-body text-center">
                    <?php
                        try {
                            $dateCondition = $status === 'active'
                                ? "(endDate IS NULL OR endDate >= CURRENT_DATE)"
                                : "(endDate < CURRENT_DATE)";

                            $sql = "SELECT idProgetto FROM progetto 
                                    WHERE $dateCondition
                                    AND (progetto.descProgetto != 'prenotaaulagiornaliero') 
                                    AND (progetto.descProgetto != 'orario delle lezioni')
                                    AND (progetto.descProgetto != 'Il laboratorio non è accessibile');";
                            $query = $dbh->prepare($sql);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_NUM);

                            // Count the number of projects
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
    <div class="row"> 
        <div id="projectsList" class="col-md-12 collapse mt-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Risultati dei Progetti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="collapse"></button>
                </div>
                <div class="card-body p-3">
                    <!-- Barra di ricerca per nome progetto -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="searchProjectName" class="form-control bg-dark text-white" placeholder="Cerca per nome progetto...">
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" id="resetSearch" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Reset
                            </button>
                        </div>
                    </div>
                    <?php
                        try {
                            $dateCondition = $status === 'active'
                                ? "(endDate IS NULL OR endDate >= CURRENT_DATE)"
                                : "(endDate < CURRENT_DATE)";

                            $sql = "SELECT 
                                    p.idProgetto,
                                    p.nomeProgetto, -- Name of the project
                                    p.descProgetto, -- description of the project
                                    p.idTutor,
                                    p.idEsperto,
                                    p.endDate, -- end course
                                    d.cognome AS Tutor_Cognome, -- Name of the tutor
                                    d2.cognome AS Esperto_Cognome -- Name of the expert
                                FROM progetto p
                                LEFT JOIN docente d ON p.idTutor = d.idDocente
                                LEFT JOIN docente d2 ON p.idEsperto = d2.idDocente
                                WHERE $dateCondition
                                AND (p.descProgetto != 'prenotaaulagiornaliero') 
                                AND (p.descProgetto != 'orario delle lezioni')
                                AND (p.descProgetto != 'Il laboratorio non è accessibile');";
                                
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
                                            data-id="<?= htmlspecialchars($project['idProgetto']) ?>"
                                            title="<?= htmlspecialchars($project['nomeProgetto']) ?>">
                                            <span class="scrollable-text">
                                                <?= htmlspecialchars($project['nomeProgetto']) ?>
                                            </span>
                                        </button>
                                    </td>
                                    <td class="truncated-cell" data-full-text="<?= htmlspecialchars($project['descProgetto']) ?>">
                                        <span class="scrollable-text">
                                            <?= htmlspecialchars($project['descProgetto']) ?>
                                        </span>
                                    </td>

                                    <td><?= htmlspecialchars($project['Tutor_Cognome'] ?? 'N/D') ?></td>
                                    <td><?= htmlspecialchars($project['Esperto_Cognome'] ?? 'N/D') ?></td>
                                    <td>
                                        <?php 
                                        $end_date = $project['endDate'];
                                        echo !empty($end_date) ? date("d-m-Y", strtotime($end_date)) : 'N/D';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!isset($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] == 1): ?>
                                            <button type="button" class="btn-delete btn btn-danger" data-id="<?= htmlspecialchars($project['idProgetto']) ?>">
                                                <i class="fas fa-trash"></i> Elimina
                                            </button>
                                        <?php endif; ?>
                                        <a href="manage-project.php?id=<?= htmlspecialchars($project['idProgetto']) ?>" class="btn btn-primary btn-modify">
                                            <i class="fas fa-edit"></i> Modifica
                                        </a>
                                        <?php if ($status === 'active'): ?>
                                        <!-- "Aggiungi Appuntamento" button only for active projects -->
                                        <button type="button" class="btn-add-appointment btn btn-success" data-id="<?= htmlspecialchars($project['idProgetto']) ?>">
                                            <i class="fas fa-plus"></i> Aggiungi Appuntamento
                                        </button>
                                        <?php endif; ?>
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
<!-- Modal: Add Appointment -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addAppointmentForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addAppointmentModalLabel">Aggiungi Appuntamento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body">
            <!-- Hidden field to store project ID (ID_Corso) -->
            <input type="hidden" name="idCorso" id="appointmentProjectId">

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
<!-- project's detail modal -->
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
<!-- Hidden field to pass status to JS -->
<input type="hidden" id="projectStatus" value="<?= $status ?>" />

<?php include 'includes/footer.php';?>
