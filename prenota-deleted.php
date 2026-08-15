<?php
// prenota-deleted.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
require_once __DIR__ . '/includes/session.php';
error_reporting(E_ALL);
ini_set('display_errors', defined('APP_DEBUG') && APP_DEBUG ? '1' : '0');

require_once __DIR__ . '/includes/config.php';

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


$pageTitle = 'OggiInLab | Appuntamenti annullati';
$pageScriptFiles = ['assets/js/prenota-deleted.js'];
$pageCsrf = true;
?>
<?php include "includes/header.php"; ?>

<div class="container mt-5">
    <div class="row mb-4">
        <h4 class="text-center w-100">Elenco prenotazioni annullate</h4>
        <div class="text-center mt-3">
            <button type="button" id="btnDeleteAll" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> Elimina tutti gli appuntamenti invalidati
            </button>
        </div>
    </div>
    <!-- Container to hold dynamically generated appointments -->
    <div id="appointmentsContainer"></div>
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
    const appointmentsData = <?php echo json_encode($appointments); ?>;
</script>
<?php include "includes/footer.php";?>
