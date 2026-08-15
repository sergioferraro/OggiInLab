<?php
/**
 * add-project.php – OggiInLab: Aggiungi Progetto
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
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

// -------------------------------------------------------------------
// State
// -------------------------------------------------------------------
$nome_progetto = '';
$desc_progetto = '';
$id_tutor      = null;
$id_esperto    = null;
$errors        = [];

// -------------------------------------------------------------------
// POST handler
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $postedToken = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        Logger::warning('project_add_csrf_error', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $errors[] = 'Token di sicurezza non valido. Riprova.';
    } else {
        $nome_progetto = trim($_POST['nome_progetto'] ?? '');
        $desc_progetto = trim($_POST['desc_progetto'] ?? '');
        $id_tutor      = !empty($_POST['id_tutor']) ? intval($_POST['id_tutor']) : null;
        $id_esperto    = !empty($_POST['id_esperto']) ? intval($_POST['id_esperto']) : null;

        // --- Validate required fields ---
        if ($nome_progetto === '') {
            Logger::warning('project_add_validation_error', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'error_field' => 'nomeProgetto'
            ]);
            $errors[] = 'Il nome del progetto è obbligatorio.';
        }
        if ($desc_progetto === '') {
            Logger::warning('project_add_validation_error', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'error_field' => 'Desc_Progetto'
            ]);
            $errors[] = 'La descrizione del progetto è obbligatoria.';
        }

        // --- Handle new tutor (inline, non-AJAX fallback) ---
        if (isset($_POST['tutor_action']) && $_POST['tutor_action'] === 'new' && empty($id_tutor)) {
            $tutorNome    = trim($_POST['tutor_nome'] ?? '');
            $tutorCognome = trim($_POST['tutor_cognome'] ?? '');
            if ($tutorNome !== '' && $tutorCognome !== '') {
                try {
                    // Controlla duplicati
                    $checkStmt = $dbh->prepare('SELECT idDocente FROM docente WHERE nome = :nome AND cognome = :cognome');
                    $checkStmt->execute([':nome' => $tutorNome, ':cognome' => $tutorCognome]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        $id_tutor = (int) $existing['idDocente'];
                        Logger::info('project_add_docente_duplicate', [
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'docente_type' => 'tutor',
                            'id_docente' => $id_tutor,
                            'nome' => $tutorNome,
                            'cognome' => $tutorCognome
                        ]);
                    } else {
                        $stmt = $dbh->prepare('INSERT INTO docente (nome, cognome) VALUES (:nome, :cognome)');
                        $stmt->execute([':nome' => $tutorNome, ':cognome' => $tutorCognome]);
                        $id_tutor = (int) $dbh->lastInsertId();
                        Logger::success('project_add_docente', [
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'docente_type' => 'tutor',
                            'id_docente' => $id_tutor,
                            'nome' => $tutorNome,
                            'cognome' => $tutorCognome
                        ]);
                    }
                } catch (PDOException $e) {
                    Logger::error('project_add_docente_error', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'docente_type' => 'tutor',
                        'nome' => $tutorNome,
                        'cognome' => $tutorCognome,
                        'error_message' => $e->getMessage()
                    ]);
                    $errors[] = 'Errore nell\'inserimento del tutor';
                    error_log('OggiInLab errore DB: ' . $e->getMessage());
                }
            } else {
                Logger::warning('project_add_docente_validation', [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'docente_type' => 'tutor'
                ]);
                $errors[] = 'Nome e cognome del tutor sono obbligatori.';
            }
        }

        // --- Handle new esperto (inline, non-AJAX fallback) ---
        if (isset($_POST['esperto_action']) && $_POST['esperto_action'] === 'new' && empty($id_esperto)) {
            $espertoNome    = trim($_POST['esperto_nome'] ?? '');
            $espertoCognome = trim($_POST['esperto_cognome'] ?? '');
            if ($espertoNome !== '' && $espertoCognome !== '') {
                try {
                    // Controlla duplicati
                    $checkStmt = $dbh->prepare('SELECT idDocente FROM docente WHERE nome = :nome AND cognome = :cognome');
                    $checkStmt->execute([':nome' => $espertoNome, ':cognome' => $espertoCognome]);
                    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        $id_esperto = (int) $existing['idDocente'];
                        Logger::info('project_add_docente_duplicate', [
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'docente_type' => 'esperto',
                            'id_docente' => $id_esperto,
                            'nome' => $espertoNome,
                            'cognome' => $espertoCognome
                        ]);
                    } else {
                        $stmt = $dbh->prepare('INSERT INTO docente (nome, cognome) VALUES (:nome, :cognome)');
                        $stmt->execute([':nome' => $espertoNome, ':cognome' => $espertoCognome]);
                        $id_esperto = (int) $dbh->lastInsertId();
                        Logger::success('project_add_docente', [
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'docente_type' => 'esperto',
                            'id_docente' => $id_esperto,
                            'nome' => $espertoNome,
                            'cognome' => $espertoCognome
                        ]);
                    }
                } catch (PDOException $e) {
                    Logger::error('project_add_docente_error', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'docente_type' => 'esperto',
                        'nome' => $espertoNome,
                        'cognome' => $espertoCognome,
                        'error_message' => $e->getMessage()
                    ]);
                    $errors[] = 'Errore nell\'inserimento dell\'esperto';
                    error_log('OggiInLab errore DB: ' . $e->getMessage());
                }
            } else {
                Logger::warning('project_add_docente_validation', [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'docente_type' => 'esperto'
                ]);
                $errors[] = 'Nome e cognome dell\'esperto sono obbligatori.';
            }
        }

        // --- Insert project ---
        if (empty($errors)) {
            try {
                $startDate = date('Y-m-d');
                $endDate   = date('Y-12-31', strtotime('+1 year'));
                $cnp       = trim($_POST['cnp'] ?? '');
                $cup       = trim($_POST['cup'] ?? '');

                $stmt = $dbh->prepare(
                    'INSERT INTO progetto (nomeProgetto, idTutor, idEsperto, descProgetto, cnp, cup, startDate, endDate) '
                    . 'VALUES (:nome, :tutor_id, :esperto_id, :descrizione, :cnp, :cup, :start, :end)'
                );
                $stmt->execute([
                    ':nome'        => $nome_progetto,
                    ':tutor_id'    => $id_tutor,
                    ':esperto_id'  => $id_esperto,
                    ':descrizione' => $desc_progetto,
                    ':cnp'         => $cnp,
                    ':cup'         => $cup,
                    ':start'       => $startDate,
                    ':end'         => $endDate,
                ]);

                Logger::success('project_add', [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'id_progetto' => (int) $dbh->lastInsertId(),
                    'nome_progetto' => $nome_progetto,
                    'descrizione' => $desc_progetto,
                    'tutor_id' => $id_tutor,
                    'esperto_id' => $id_esperto
                ]);

                $success = 'Progetto aggiunto con successo!';
            } catch (PDOException $e) {
                Logger::error('project_add_db_error', [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'nome_progetto' => $nome_progetto,
                    'descrizione' => $desc_progetto,
                    'tutor_id' => $id_tutor,
                    'esperto_id' => $id_esperto,
                    'error_message' => $e->getMessage()
                ]);
                $errors[] = 'Errore nell\'inserimento del progetto';
                error_log('OggiInLab errore DB: ' . $e->getMessage());
            }
        }
    }
}

// -------------------------------------------------------------------
// Fetch docenti for dropdowns
// -------------------------------------------------------------------
try {
    $docenti = $dbh->query('SELECT idDocente, cognome FROM docente WHERE isDeleted <> 1')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Errore nel recupero dei docenti';
    error_log('OggiInLab errore DB: ' . $e->getMessage());
    $docenti = [];
}

$pageTitle = 'OggiInLab | Aggiungi Progetto';
$pageScriptFiles = ['assets/js/add-project.js'];
$pageCsrf = true;
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
    <?php if (!empty($success ?? '')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
            <i class="fa-solid fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- ── Project Form ── -->
    <div class="card p-4">
        <h4 class="mb-4"><i class="fa-solid fa-folder-plus me-2"></i>Aggiungi Progetto</h4>

        <form method="POST" id="projectForm">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- Nome Progetto -->
            <div class="mb-3">
                <label for="nome_progetto" class="form-label">Nome Progetto</label>
                <input type="text"
                       id="nome_progetto"
                       name="nome_progetto"
                       value="<?= htmlspecialchars($nome_progetto) ?>"
                       required
                       class="form-control">
            </div>

            <!-- Descrizione -->
            <div class="mb-3">
                <label for="desc_progetto" class="form-label">Descrizione</label>
                <textarea id="desc_progetto"
                          name="desc_progetto"
                          rows="4"
                          required
                          class="form-control"><?= htmlspecialchars($desc_progetto) ?></textarea>
            </div>

            <!-- CNP / CUP -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="CNP" class="form-label">CNP</label>
                    <input type="text"
                           id="CNP"
                           name="cnp"
                           class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="CUP" class="form-label">CUP</label>
                    <input type="text"
                           id="CUP"
                           name="cup"
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
                           value="<?= date('Y-m-d') ?>"
                           class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="endDate" class="form-label">Data Fine</label>
                    <input type="date"
                           id="endDate"
                           name="endDate"
                           value="<?= date('Y-12-31', strtotime('+1 year')) ?>"
                           class="form-control">
                </div>
            </div>

            <!-- ── Tutor ── -->
            <fieldset class="mb-4 p-3 border rounded">
                <legend class="fs-6"><i class="fa-solid fa-chalkboard-user me-2"></i>Tutor</legend>

                <div class="mb-3">
                    <label for="tutorSelect" class="form-label">Seleziona un docente esistente:</label>
                    <select name="id_tutor" id="tutorSelect" class="form-select">
                        <option value="">— Scegli un tutor —</option>
                        <?php foreach ($docenti as $doc): ?>
                            <option value="<?= $doc['idDocente'] ?>"
                                <?= ($id_tutor === (int) $doc['idDocente']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['cognome']) ?>
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
                            <input type="hidden" name="tutor_action" value="new">
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
                    <select name="id_esperto" id="espertoSelect" class="form-select">
                        <option value="">— Scegli un esperto —</option>
                        <?php foreach ($docenti as $doc): ?>
                            <option value="<?= $doc['idDocente'] ?>"
                                <?= ($id_esperto === (int) $doc['idDocente']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['cognome']) ?>
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
                            <input type="hidden" name="esperto_action" value="new">
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
                <i class="fa-solid fa-plus-circle me-2"></i>Aggiungi Progetto
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
