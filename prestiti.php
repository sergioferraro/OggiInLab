<?php
/**
 * prestiti.php – OggiInLab: Gestione Prestiti Attrezzature
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/includes/config.php';

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
$beneficiario      = '';
$classe            = '';
$data_prestito     = '';
$data_consegna_prevista = '';
$descrizione_bene  = '';
$errors            = [];
$success           = '';

// -------------------------------------------------------------------
// POST handler (form submission)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    // CSRF check
    $postedToken = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $errors[] = 'Token di sicurezza non valido. Riprova.';
    } else {
        $beneficiario       = trim($_POST['beneficiario'] ?? '');
        $classe             = trim($_POST['classe'] ?? '');
        $data_prestito      = trim($_POST['data_prestito'] ?? '');
        $data_consegna_prevista = trim($_POST['data_consegna_prevista'] ?? '');
        $descrizione_bene   = trim($_POST['descrizione_bene'] ?? '');

        // --- Validate required fields ---
        if ($beneficiario === '') {
            $errors[] = 'Il nome del beneficiario è obbligatorio.';
        }
        if ($data_prestito === '') {
            $errors[] = 'La data di prestito è obbligatoria.';
        }
        if ($data_consegna_prevista === '') {
            $errors[] = 'La data presunta di consegna è obbligatoria.';
        }
        if ($descrizione_bene === '') {
            $errors[] = 'La descrizione del bene è obbligatoria.';
        }

        // --- Insert record ---
        if (empty($errors)) {
            try {
                $id_admin = intval($_SESSION['id'] ?? 0);
                if ($id_admin <= 0) {
                    $errors[] = 'Sessione non valida. Effettua nuovamente il login.';
                } else {
                    $stmt = $dbh->prepare(
                        'INSERT INTO prestito (id_admin, beneficiario, classe, data_prestito, data_consegna_prevista, descrizione_bene) '
                        . 'VALUES (:id_admin, :beneficiario, :classe, :data_prestito, :data_consegna_prevista, :descrizione_bene)'
                    );
                    $stmt->execute([
                        ':id_admin'            => $id_admin,
                        ':beneficiario'        => $beneficiario,
                        ':classe'              => $classe ?: null,
                        ':data_prestito'       => $data_prestito,
                        ':data_consegna_prevista' => $data_consegna_prevista,
                        ':descrizione_bene'    => $descrizione_bene,
                    ]);
                    $success = 'Prestito registrato con successo!';
                    // Reset form
                    $beneficiario = '';
                    $classe = '';
                    $data_prestito = '';
                    $data_consegna_prevista = '';
                    $descrizione_bene = '';
                }
            } catch (PDOException $e) {
                error_log('Errore inserimento prestito: ' . $e->getMessage());
                $errors[] = 'Errore durante il salvataggio del prestito.';
            }
        }
    }
}

$pageTitle = 'OggiInLab | Prestiti Attrezzature';
$pageScriptFiles = ['assets/js/prestiti.js'];
$pageStyles = '
    .table {
        color: #f8f9fa;
    }
    .table-dark {
        background-color: #2c2c2c;
    }
    .badge-overdue {
        background-color: #dc3545;
    }
    .badge-ok {
        background-color: #0d6efd;
    }
';
$pageCsrf = true;
?>
<?php include "includes/header.php"; ?>

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
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
            <i class="fa-solid fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- ── Form Nuovo Prestito ── -->
    <div class="card p-4 mb-4">
        <h4 class="mb-4"><i class="fa-solid fa-box-open me-2"></i>Nuovo Prestito Attrezzatura</h4>

        <form method="POST" id="prestitoForm">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="beneficiario" class="form-label">Nome del beneficiario</label>
                    <input type="text"
                           id="beneficiario"
                           name="beneficiario"
                           value="<?= htmlspecialchars($beneficiario) ?>"
                           required
                           class="form-control"
                           placeholder="Es. Mario Rossi">
                </div>
                <div class="col-md-6">
                    <label for="classe" class="form-label">Classe <small class="text-muted">(opzionale)</small></label>
                    <input type="text"
                           id="classe"
                           name="classe"
                           value="<?= htmlspecialchars($classe) ?>"
                           class="form-control"
                           placeholder="Es. 3A">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="data_prestito" class="form-label">Data di prestito</label>
                    <input type="date"
                           id="data_prestito"
                           name="data_prestito"
                           value="<?= htmlspecialchars($data_prestito) ?>"
                           required
                           class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="data_consegna_prevista" class="form-label">Data presunta di consegna</label>
                    <input type="date"
                           id="data_consegna_prevista"
                           name="data_consegna_prevista"
                           value="<?= htmlspecialchars($data_consegna_prevista) ?>"
                           required
                           class="form-control">
                </div>
            </div>

            <div class="mb-3">
                <label for="descrizione_bene" class="form-label">Descrizione del bene</label>
                <textarea id="descrizione_bene"
                          name="descrizione_bene"
                          rows="3"
                          required
                          class="form-control"
                          placeholder="Descrizione dettagliata dell'attrezzatura prestata…"><?= htmlspecialchars($descrizione_bene) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-plus-circle me-2"></i>Registra Prestito
            </button>
        </form>
    </div>

    <!-- ── Lista Prestiti Non Riconsegnati ── -->
    <div class="card p-4">
        <h4 class="mb-4"><i class="fa-solid fa-clock me-2"></i>Beni Non Ancora Riconsegnati</h4>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle" id="prestitiTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Beneficiario</th>
                        <th>Classe</th>
                        <th>Data prestito</th>
                        <th>Consegna prevista</th>
                        <th>Descrizione bene</th>
                        <th style="width:120px;">Azione</th>
                    </tr>
                </thead>
                <tbody id="prestitiBody">
                    <!-- Populated via AJAX -->
                </tbody>
            </table>
        </div>

        <div id="prestitiEmpty" class="text-center text-muted py-4" style="display:none;">
            <i class="fa-solid fa-check-double fa-2x mb-2"></i>
            <p>Nessun prestito in sospeso.</p>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
