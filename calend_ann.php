<?php
/**
 * calend_ann.php – OggiInLab: Calendario Scolastico Annuale
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
// Helpers
// -------------------------------------------------------------------

/**
 * Restituisce l'anno scolastico corrente in formato "2025/2026".
 */
function getAnnoScolastico(DateTime $data): string
{
    $mese = (int) $data->format('m');
    $anno = (int) $data->format('Y');

    if ($mese >= 9) {
        return "{$anno}/" . ($anno + 1);
    }
    return ($anno - 1) . "/{$anno}";
}

/**
 * Calcola la data di Pasqua per un dato anno (algoritmo Gregoriano anonimo).
 */
function getPasqua(int $anno): string
{
    $a = $anno % 19;
    $b = intdiv($anno, 100);
    $c = $anno % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);

    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

    return sprintf('%04d-%02d-%02d', $anno, $month, $day);
}

// -------------------------------------------------------------------
// State
// -------------------------------------------------------------------
$errors        = [];
$success       = '';
$annoScolastico = getAnnoScolastico(new DateTime());
$pasqua         = getPasqua((int) (new DateTime())->format('Y'));

// Pasquetta = Pasqua + 1 giorno
$pasquettaDate = new DateTime($pasqua);
$pasquettaDate->modify('+1 day');
$pasquetta = $pasquettaDate->format('Y-m-d');

[$anno1, $anno2] = explode('/', $annoScolastico);

// -------------------------------------------------------------------
// Fetch school year dates from DB
// -------------------------------------------------------------------
$schoolStart = '';
$schoolEnd   = '';
try {
    $stmt = $dbh->prepare(
        'SELECT startDate, endDate FROM progetto WHERE nomeProgetto = :nome LIMIT 1'
    );
    $stmt->execute([':nome' => 'orario']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $schoolStart = $row['startDate'] ?? '';
        $schoolEnd   = $row['endDate'] ?? '';
    }
} catch (PDOException $e) {
    error_log('Recupero date anno scolastico: ' . $e->getMessage());
}

// -------------------------------------------------------------------
// POST handler – school year dates
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $errors[] = 'Token di sicurezza non valido. Riprova.';
    } elseif ($_POST['action'] === 'save_dates') {
        // ── Salva date inizio/fine anno scolastico ──
        $startDate = $_POST['startDate'] ?? '';
        $endDate   = $_POST['endDate'] ?? '';

        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            $errors[] = 'La data di fine non può essere antecedente a quella di inizio.';
        }

        if (empty($errors)) {
            try {
                $stmt = $dbh->prepare(
                    'UPDATE progetto SET startDate = :start, endDate = :end WHERE nomeProgetto = :nome'
                );
                $stmt->execute([
                    ':start' => $startDate,
                    ':end'   => $endDate,
                    ':nome'  => 'orario',
                ]);
                $success  = 'Date anno scolastico salvate con successo.';
                $schoolStart = $startDate;
                $schoolEnd   = $endDate;
            } catch (PDOException $e) {
                $errors[] = 'Errore salvataggio date: ' . htmlspecialchars($e->getMessage());
            }
        }

    } elseif ($_POST['action'] === 'add_chiusura') {
        // ── Aggiungi chiusura ──
        $data        = $_POST['data'] ?? '';
        $descrizione = trim($_POST['descrizione'] ?? '');

        if ($data === '') {
            $errors[] = 'La data è obbligatoria.';
        }
        if ($descrizione === '') {
            $errors[] = 'La descrizione è obbligatoria.';
        }

        if (empty($errors)) {
            try {
                $stmt = $dbh->prepare(
                    'INSERT INTO calendario (annoScolastico, giorno, nomeChiusura) VALUES (:anno, :giorno, :nome)'
                );
                $stmt->execute([
                    ':anno'   => $annoScolastico,
                    ':giorno' => $data,
                    ':nome'   => $descrizione,
                ]);
                $success = 'Chiusura aggiunta con successo.';
            } catch (PDOException $e) {
                $errors[] = 'Errore inserimento: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// -------------------------------------------------------------------
// Auto-seed required holidays for current school year
// -------------------------------------------------------------------
$requiredHolidays = [
    ["date" => "{$anno1}-10-04", "description" => "San Francesco"],
    ["date" => "{$anno1}-11-01", "description" => "Tutti i Santi"],
    ["date" => "{$anno1}-12-08", "description" => "Immacolata"],
    ["date" => "{$anno1}-12-25", "description" => "Natale"],
    ["date" => "{$anno1}-12-26", "description" => "Santo Stefano"],
    ["date" => "{$anno2}-01-06", "description" => "Epifania"],
    ["date" => "{$anno2}-04-25", "description" => "Festa della Liberazione"],
    ["date" => "{$anno2}-05-01", "description" => "Festa del Lavoro"],
    ["date" => "{$anno2}-06-02", "description" => "Festa della Repubblica"],
    ["date" => $pasqua,          "description" => "Pasqua"],
    ["date" => $pasquetta,       "description" => "Lunedì dell'Angelo"],
    ["date" => "{$anno2}-08-15", "description" => "Ferragosto"],
];

try {
    foreach ($requiredHolidays as $holiday) {
        $stmtCheck = $dbh->prepare(
            'SELECT COUNT(*) FROM calendario WHERE annoScolastico = :anno AND giorno = :giorno'
        );
        $stmtCheck->execute([
            ':anno'  => $annoScolastico,
            ':giorno' => $holiday['date'],
        ]);

        if ((int) $stmtCheck->fetchColumn() === 0) {
            $stmtInsert = $dbh->prepare(
                'INSERT INTO calendario (annoScolastico, giorno, nomeChiusura) VALUES (:anno, :giorno, :nome)'
            );
            $stmtInsert->execute([
                ':anno'  => $annoScolastico,
                ':giorno' => $holiday['date'],
                ':nome'   => $holiday['description'],
            ]);
        }
    }
} catch (PDOException $e) {
    error_log('Auto-seed festività: ' . $e->getMessage());
}

// -------------------------------------------------------------------
// Fetch all holidays for current school year
// -------------------------------------------------------------------
try {
    $stmt = $dbh->prepare(
        'SELECT idCalendario, giorno, nomeChiusura FROM calendario '
        . 'WHERE annoScolastico = :anno ORDER BY giorno'
    );
    $stmt->execute([':anno' => $annoScolastico]);
    $festivi = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $errors[] = 'Errore nel recupero delle festività: ' . htmlspecialchars($e->getMessage());
    $festivi = [];
}

$pageTitle = 'OggiInLab | Calendario Scolastico';
$pageCsrf  = true;
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

    <h4 class="mb-4 text-center"><i class="fa-solid fa-calendar-days me-2"></i>Pannello di controllo amministratore</h4>

    <!-- ── Form: Date anno scolastico ── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3"><i class="fa-solid fa-calendar-week me-2"></i>Date anno scolastico</h5>
        <p class="text-muted small">
            Le date di inizio/fine anno vanno impostate una sola volta all'inizio dell'anno
            secondo il calendario scolastico di riferimento. Faranno parte del progetto "orario scolastico".
        </p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="save_dates">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="startDate" class="form-label">Data inizio anno scolastico</label>
                    <input type="date"
                           class="form-control"
                           id="startDate"
                           name="startDate"
                           value="<?= htmlspecialchars($schoolStart) ?>"
                           required>
                </div>
                <div class="col-md-6">
                    <label for="endDate" class="form-label">Data fine anno scolastico</label>
                    <input type="date"
                           class="form-control"
                           id="endDate"
                           name="endDate"
                           value="<?= htmlspecialchars($schoolEnd) ?>"
                           required>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Salva date
                </button>
            </div>
        </form>
    </div>

    <!-- ── Tabella festività ── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3">
            <i class="fa-solid fa-calendar-day me-2"></i>
            Festività e chiusure – Anno scolastico <?= htmlspecialchars($annoScolastico) ?>
        </h5>
        <p class="text-muted small">
            Le festività (compresa la Pasqua) sono calcolate automaticamente ed è sconsigliabile cancellarle.
            Aggiungere solo le chiusure e/o le sospensioni didattiche secondo l'autonomia d'Istituto.
        </p>

        <?php if (!empty($festivi)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrizione</th>
                            <th style="width:140px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($festivi as $festa): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($festa->giorno))) ?></td>
                                <td><?= htmlspecialchars($festa->nomeChiusura) ?></td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-danger btn-delete"
                                            data-id="<?= (int) $festa->idCalendario ?>">
                                        <i class="fa-solid fa-trash me-1"></i>Elimina
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">Nessuna festività o chiusura registrata per questo anno scolastico.</p>
        <?php endif; ?>
    </div>

    <!-- ── Form: Aggiungi chiusura ── -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3"><i class="fa-solid fa-plus-circle me-2"></i>Aggiungi una nuova chiusura</h5>

        <form method="POST" id="aggiungiChiusuraForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="add_chiusura">

            <div class="mb-3">
                <label for="giornoChiusura" class="form-label">Data</label>
                <input type="date"
                       class="form-control"
                       id="giornoChiusura"
                       name="data"
                       required>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           id="checkboxSospensione"
                           name="sospensione">
                    <label class="form-check-label" for="checkboxSospensione">
                        Sospensione dell'attività didattica
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <label for="nomeChiusura" class="form-label">Descrizione</label>
                <input type="text"
                       class="form-control"
                       id="nomeChiusura"
                       name="descrizione"
                       required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i>Aggiungi chiusura
            </button>
        </form>
    </div>
</div>

<!-- ── Modal conferma eliminazione ── -->
<div class="modal fade" id="eliminaEventoModal" tabindex="-1" aria-labelledby="eliminaEventoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminaEventoModalLabel">Conferma eliminazione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                Sei sicuro di voler eliminare questa voce dal calendario?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <form method="POST" action="assets/utils/elimina_calendario.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="idCalendario" id="modalDeleteId">
                    <button type="submit" class="btn btn-danger">Elimina</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ── Checkbox sospensione → auto-compila descrizione ──
    var checkbox = document.getElementById('checkboxSospensione');
    var descInput = document.getElementById('nomeChiusura');

    if (checkbox && descInput) {
        function updateDescrizione() {
            if (checkbox.checked) {
                descInput.value = 'Sospensione dell\'attività didattica';
                descInput.readOnly = true;
            } else {
                descInput.value = '';
                descInput.readOnly = false;
            }
        }
        checkbox.addEventListener('change', updateDescrizione);
    }

    // ── Pulsanti elimina → modal ──
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            document.getElementById('modalDeleteId').value = id;
            var modal = new bootstrap.Modal(document.getElementById('eliminaEventoModal'));
            modal.show();
        });
    });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
