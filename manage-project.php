<?php
/**
 * manage-project.php – OggiInLab: Modifica Progetto
 *
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/session.php';
error_reporting(E_ALL);
ini_set('display_errors', defined('APP_DEBUG') && APP_DEBUG ? '1' : '0');

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Logger.php';

// -------------------------------------------------------------------
// Auth guard
// -------------------------------------------------------------------
if (empty($_SESSION['alogin'])) {
    header('Location: index.php');
    exit;
}

// -------------------------------------------------------------------
// CSRF token
// -------------------------------------------------------------------
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Log CSRF token generation for security auditing
Logger::debug('csrf_token_generated', [
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// -------------------------------------------------------------------
// State
// -------------------------------------------------------------------
$errors  = [];
$success = '';

// -------------------------------------------------------------------
// Validate project ID
// -------------------------------------------------------------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errors[] = 'Progetto non specificato.';
} else {
    $projectId = (int) $_GET['id'];
}

// -------------------------------------------------------------------
// Fetch project data
// -------------------------------------------------------------------
$projectData = null;
if (empty($errors)) {
    try {
        $stmt = $dbh->prepare(
            'SELECT p.*, '
            . 't.cognome AS TutorCognome, '
            . 'e.cognome AS EspertoCognome '
            . 'FROM progetto p '
            . 'LEFT JOIN docente t ON p.idTutor  = t.idDocente '
            . 'LEFT JOIN docente e ON p.idEsperto = e.idDocente '
            . 'WHERE p.idProgetto = :id'
        );
        $stmt->execute([':id' => $projectId]);
        $projectData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = 'Errore nel recupero dei dati';
        error_log('OggiInLab errore DB: ' . $e->getMessage());
    }
}

if (empty($errors) && $projectData === null) {
    $errors[] = 'Progetto non trovato.';
}

// -------------------------------------------------------------------
// Fetch all docenti for dropdowns
// -------------------------------------------------------------------
try {
    $docenti = $dbh->query(
        'SELECT idDocente, CONCAT(nome, " ", cognome) AS FullName '
        . 'FROM docente WHERE isDeleted <> 1 ORDER BY cognome'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Errore nel recupero dei docenti';
    error_log('OggiInLab errore DB: ' . $e->getMessage());
    $docenti = [];
}

// -------------------------------------------------------------------
// POST handler
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize logger
    Logger::init();
    
    // Log all POST requests to the management page
    Logger::info('project_management_request', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'project_id' => $projectId ?? null,
        'has_csrf_token' => isset($_POST['_token'])
    ]);
    // CSRF check
    $postedToken = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        Logger::warning('csrf_validation_failed', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'project_id' => $projectId ?? null
        ]);
        $errors[] = 'Token di sicurezza non valido. Riprova.';
    } elseif (!empty($errors)) {
        // Already had errors (e.g. bad project ID), reject
        $errors[] = 'Impossibile elaborare la richiesta.';
    } else {
        $nomeProgetto = trim($_POST['nomeProgetto'] ?? '');
        $descProgetto = trim($_POST['Desc_Progetto'] ?? '');
        $idTutor      = ($_POST['id_tutor'] ?? '') !== '' ? (int) $_POST['id_tutor'] : null;
        $idEsperto    = ($_POST['id_esperto'] ?? '') !== '' ? (int) $_POST['id_esperto'] : null;
        $cnp          = trim($_POST['CNP'] ?? '');
        $cup          = trim($_POST['CUP'] ?? '');
        $startDate    = $_POST['startDate'] ?? '';
        $endDate      = $_POST['endDate'] ?? '';

        // Validate
        if ($nomeProgetto === '') {
            Logger::warning('project_validation_error', [
                'project_id' => $projectId,
                'error_field' => 'nomeProgetto',
                'message' => 'Nome progetto vuoto'
            ]);
            $errors[] = 'Il nome del progetto è obbligatorio.';
        }
        if ($descProgetto === '') {
            Logger::warning('project_validation_error', [
                'project_id' => $projectId,
                'error_field' => 'Desc_Progetto',
                'message' => 'Descrizione progetto vuota'
            ]);
            $errors[] = 'La descrizione del progetto è obbligatoria.';
        }
        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            Logger::warning('project_validation_error', [
                'project_id' => $projectId,
                'error_field' => 'date_range',
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            $errors[] = 'La data di inizio non può essere dopo quella di fine.';
        }

        // Update
        if (empty($errors)) {
            try {
                $stmt = $dbh->prepare(
                    'UPDATE progetto SET '
                    . 'nomeProgetto  = :nome, '
                    . 'descProgetto  = :descrizione, '
                    . 'idTutor       = :tutor_id, '
                    . 'idEsperto     = :esperto_id, '
                    . 'cnp           = :cnp, '
                    . 'cup           = :cup, '
                    . 'startDate     = :start, '
                    . 'endDate       = :end '
                    . 'WHERE idProgetto = :id'
                );
                $stmt->execute([
                    ':nome'       => $nomeProgetto,
                    ':descrizione' => $descProgetto,
                    ':tutor_id'   => $idTutor,
                    ':esperto_id' => $idEsperto,
                    ':cnp'        => $cnp,
                    ':cup'        => $cup,
                    ':start'      => $startDate !== '' ? $startDate : null,
                    ':end'        => $endDate !== '' ? $endDate : null,
                    ':id'         => $projectId,
                ]);
                $success = 'Modifiche salvate con successo!';
                
                // Log the successful update action
                Logger::success('project_update', [
                    'project_id' => $projectId,
                    'project_name' => $nomeProgetto,
                    'changes' => [
                        'tutor_id' => $idTutor,
                        'esperto_id' => $idEsperto,
                        'cnp' => $cnp,
                        'cup' => $cup,
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]);

                // Re-fetch updated data
                $stmt2 = $dbh->prepare('SELECT * FROM progetto WHERE idProgetto = :id');
                $stmt2->execute([':id' => $projectId]);
                $projectData = $stmt2->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $errors[] = 'Errore database';
                error_log('OggiInLab errore DB: ' . $e->getMessage());
                Logger::error('project_update_db_error', [
                    'project_id' => $projectId,
                    'error_message' => $e->getMessage(),
                    'sql_state' => $e->getCode()
                ]);
            }
        }
    }
}

$pageTitle = 'OggiInLab | Modifica Progetto';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container mt-4">

    <!-- Error alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
            <?php foreach ($errors as $error): ?>
                <p class="mb-0"><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Success alert -->
    <?php if ($success !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
            <i class="fa-solid fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($errors) && $projectData !== null): ?>
    <!-- ── Project Form ── -->
    <div class="card p-4">
        <h4 class="mb-4">
            <i class="fa-solid fa-pen-to-square me-2"></i>
            Modifica Progetto #<?= (int) $projectId ?>
        </h4>

        <form method="POST" id="projectForm">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- Nome Progetto -->
            <div class="mb-3">
                <label for="nomeProgetto" class="form-label">Nome Progetto</label>
                <input type="text"
                       id="nomeProgetto"
                       name="nomeProgetto"
                       value="<?= htmlspecialchars($projectData['nomeProgetto'] ?? '') ?>"
                       required
                       class="form-control form-control-lg">
            </div>

            <!-- Descrizione -->
            <div class="mb-3">
                <label for="Desc_Progetto" class="form-label">Descrizione</label>
                <textarea id="Desc_Progetto"
                          name="Desc_Progetto"
                          rows="4"
                          required
                          class="form-control"><?= htmlspecialchars($projectData['descProgetto'] ?? '') ?></textarea>
            </div>

            <!-- CNP / CUP -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="CNP" class="form-label">CNP</label>
                    <input type="text"
                           id="CNP"
                           name="CNP"
                           value="<?= htmlspecialchars($projectData['cnp'] ?? '') ?>"
                           class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="CUP" class="form-label">CUP</label>
                    <input type="text"
                           id="CUP"
                           name="CUP"
                           value="<?= htmlspecialchars($projectData['cup'] ?? '') ?>"
                           class="form-control">
                </div>
            </div>

            <!-- Date Range -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="startDate" class="form-label">Data Inizio</label>
                    <input type="date"
                           id="startDate"
                           name="startDate"
                           value="<?= htmlspecialchars($projectData['startDate'] ?? '') ?>"
                           class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="endDate" class="form-label">Data Fine</label>
                    <input type="date"
                           id="endDate"
                           name="endDate"
                           value="<?= htmlspecialchars($projectData['endDate'] ?? '') ?>"
                           class="form-control">
                </div>
            </div>

            <!-- ── Tutor ── -->
            <fieldset class="mb-4 p-3 border rounded">
                <legend class="fs-6"><i class="fa-solid fa-chalkboard-user me-2"></i>Tutor</legend>

                <div class="mb-3">
                    <label for="tutorSelect" class="form-label">Seleziona un docente esistente:</label>
                    <select name="id_tutor"
                            id="tutorSelect"
                            class="form-select">
                        <option value="">— Scegli un tutor —</option>
                        <?php foreach ($docenti as $doc): ?>
                            <option value="<?= $doc['idDocente'] ?>"
                                <?= ($projectData['idTutor'] ?? null) === $doc['idDocente'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['FullName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-check">
                    <input type="checkbox"
                           class="form-check-input"
                           id="newTutorToggle"
                           checked>
                    <label class="form-check-label" for="newTutorToggle">
                        Oppure aggiungi un nuovo tutor
                    </label>
                </div>

                <div id="newTutorFields" class="mt-2">
                    <div class="row g-2">
                        <div class="col">
                            <input type="text"
                                   name="tutor_nome"
                                   placeholder="Nome"
                                   class="form-control">
                        </div>
                        <div class="col">
                            <input type="text"
                                   name="tutor_cognome"
                                   placeholder="Cognome"
                                   class="form-control">
                        </div>
                        <div class="col-auto d-flex align-items-center">
                            <button type="button"
                                    id="addTutorBtn"
                                    class="btn btn-outline-success btn-sm"
                                    title="Aggiungi tutor">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- ── Esperto ── -->
            <fieldset class="mb-4 p-3 border rounded">
                <legend class="fs-6"><i class="fa-solid fa-user-tie me-2"></i>Esperto</legend>

                <div class="mb-3">
                    <label for="espertoSelect" class="form-label">Seleziona un docente esistente:</label>
                    <select name="id_esperto"
                            id="espertoSelect"
                            class="form-select">
                        <option value="">— Scegli un esperto —</option>
                        <?php foreach ($docenti as $doc): ?>
                            <option value="<?= $doc['idDocente'] ?>"
                                <?= ($projectData['idEsperto'] ?? null) === $doc['idDocente'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['FullName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-check">
                    <input type="checkbox"
                           class="form-check-input"
                           id="newEspertoToggle"
                           checked>
                    <label class="form-check-label" for="newEspertoToggle">
                        Oppure aggiungi un nuovo esperto
                    </label>
                </div>

                <div id="newEspertoFields" class="mt-2">
                    <div class="row g-2">
                        <div class="col">
                            <input type="text"
                                   name="esperto_nome"
                                   placeholder="Nome"
                                   class="form-control">
                        </div>
                        <div class="col">
                            <input type="text"
                                   name="esperto_cognome"
                                   placeholder="Cognome"
                                   class="form-control">
                        </div>
                        <div class="col-auto d-flex align-items-center">
                            <button type="button"
                                    id="addEspertoBtn"
                                    class="btn btn-outline-success btn-sm"
                                    title="Aggiungi esperto">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Submit -->
            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-floppy-disk me-2"></i>Salva Modifiche
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ── Toggle new-fields visibility ──
    function setupToggle(checkboxId, fieldsId) {
        var cb = document.getElementById(checkboxId);
        var fields = document.getElementById(fieldsId);
        if (!cb || !fields) return;

        function update() {
            fields.style.display = cb.checked ? 'block' : 'none';
        }
        cb.addEventListener('change', update);
        update();
    }

    setupToggle('newTutorToggle', 'newTutorFields');
    setupToggle('newEspertoToggle', 'newEspertoFields');

    // ── AJAX: add docente ──
    function addDocente(type) {
        var nomeInput    = document.querySelector('[name="' + type + '_nome"]');
        var cognomeInput = document.querySelector('[name="' + type + '_cognome"]');

        if (!nomeInput || !cognomeInput) return;

        var nome    = nomeInput.value.trim();
        var cognome = cognomeInput.value.trim();

        if (!nome || !cognome) {
            alert('Nome e cognome sono obbligatori.');
            return;
        }

        var formData = new FormData();
        formData.append('nome', nome);
        formData.append('cognome', cognome);
        var tokenInput = document.querySelector('input[name="_token"]');
        if (tokenInput) formData.append('_token', tokenInput.value);

        fetch('assets/utils/add_docente.php', {
            method: 'POST',
            body: formData
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                var selectId = type === 'tutor' ? 'tutorSelect' : 'espertoSelect';
                var select = document.getElementById(selectId);

                if (select) {
                    var option = new Option(
                        data.docente.cognome + ' ' + data.docente.nome,
                        data.docente.id,
                        true,
                        true
                    );
                    select.add(option);
                    select.value = data.docente.id;
                }

                // Pulisci i campi
                nomeInput.value    = '';
                cognomeInput.value = '';
            } else {
                alert('Errore: ' + data.message);
            }
        })
        .catch(function () {
            alert("Errore durante l'invio dei dati.");
        });
    }

    // ── Bind AJAX buttons ──
    var addTutorBtn = document.getElementById('addTutorBtn');
    if (addTutorBtn) {
        addTutorBtn.addEventListener('click', function () { addDocente('tutor'); });
    }

    var addEspertoBtn = document.getElementById('addEspertoBtn');
    if (addEspertoBtn) {
        addEspertoBtn.addEventListener('click', function () { addDocente('esperto'); });
    }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
