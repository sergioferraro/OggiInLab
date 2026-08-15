<?php
// orario_lab.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */

require_once __DIR__ . '/includes/session.php';

// Controllo login
if (!isset($_SESSION['alogin'])) {
    header('Location: index.php');
    exit;
}

include 'includes/config.php';

// Definition of Time Slots (8 hours)
$ore = [
    ['inizio' => '08:10', 'fine' => '09:10'],
    ['inizio' => '09:10', 'fine' => '10:10'],
    ['inizio' => '10:10', 'fine' => '11:10'],
    ['inizio' => '11:10', 'fine' => '12:10'],
    ['inizio' => '12:10', 'fine' => '13:10'],
    ['inizio' => '13:10', 'fine' => '14:10'],
    ['inizio' => '14:10', 'fine' => '15:10'],
    ['inizio' => '15:10', 'fine' => '16:10'],
];
// Days of the Week Mon-Sat
$giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];

// Retrieve Project "orario"
$stmt = $dbh->prepare("SELECT idProgetto, nomeProgetto FROM progetto WHERE nomeProgetto = 'orario'");
$stmt->execute();
$project = $stmt->fetch(PDO::FETCH_ASSOC);
$idProgetto = $project['idProgetto'];

// Fetch Classrooms and Teachers
$aulas   = $dbh->query("SELECT * FROM aula ORDER BY nAula")->fetchAll(PDO::FETCH_ASSOC);
$docenti = $dbh->query("SELECT idDocente, cognome, nome, isDeleted FROM docente WHERE isDeleted=0 ORDER BY cognome, nome")->fetchAll(PDO::FETCH_ASSOC);

// Verify Selected Room idAula (GET or POST)
$idAula = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['idAula'])) {
    $idAula = (int)$_POST['idAula'];
} elseif (!empty($_GET['idAula'])) {
    $idAula = (int)$_GET['idAula'];
}

// Trova il nome dell'aula selezionata (una sola scansione)
$nAula = '--';
foreach ($aulas as $a) {
    if ($a['idAula'] == $idAula) {
        $nAula = $a['nAula'];
        break;
    }
}

// Load Existing Schedule for Classroom and Project
$schedule = [];
if ($idAula) {
    $stmt = $dbh->prepare(
        "SELECT giorno, 
        ora_inizio, 
        ora_fine, 
        idDocente, classe
         FROM orario_settimana
         WHERE idProgetto = ? AND idAula = ?"
    );
    $stmt->execute([$idProgetto, $idAula]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Truncate Seconds to Get HH:MM
        $row['ora_inizio'] = substr($row['ora_inizio'], 0, 5);
        $row['ora_fine'] = substr($row['ora_fine'], 0, 5);
        $schedule[$row['giorno']][$row['ora_inizio']] = [
            'docente' => $row['idDocente'],
            'classe'  => $row['classe'],
        ];
    }
}

// Form Submission Management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idAula) {
    // Verifica CSRF
    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Token CSRF non valido.');
    }

    $autore = $_SESSION['id'];

    // Remove Any Previous Entries
    $dbh->beginTransaction();
    $stmt = $dbh->prepare("DELETE FROM orario_settimana WHERE idProgetto = ? AND idAula = ?");
    $stmt->execute([$idProgetto, $idAula]);

    // Re-enter using loop indices directly (no array_search on associative arrays)
    foreach ($giorni as $dIdx => $g) {
        foreach ($ore as $idx => $fascia) {
            $start = $fascia['inizio'];
            $end   = $fascia['fine'];
            $docKey   = 'docente_' . $dIdx . '_' . $idx;
            $classKey = 'classe_' . $dIdx . '_' . $idx;
            $doc      = !empty($_POST[$docKey])   ? $_POST[$docKey]   : null;
            $cls      = !empty($_POST[$classKey]) ? trim($_POST[$classKey]) : null;

            if ($cls) {
                $dbh->prepare(
                    "INSERT INTO orario_settimana
                      (idProgetto, idAula, idDocente, classe, giorno, ora_inizio, ora_fine, autore)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([$idProgetto, $idAula, $doc, $cls, $g, $start, $end, $autore]);
            }
        }
    }
    $dbh->commit();

    // Flash message via session
    $_SESSION['msg_success'] = "Appuntamenti registrati con successo!";
    header("Location: orario_lab.php?idAula=" . $idAula);
    exit;
}

// Recupera e resetta flash message
$msgSuccess = $_SESSION['msg_success'] ?? null;
unset($_SESSION['msg_success']);

$pageTitle = 'OggiInLab | Gestione orario settimanale ' . htmlspecialchars($project['nomeProgetto']);
$pageScriptFiles = ['assets/js/orario_lab.js'];
$pageCsrf = true;
$pageStyles = '
.table.disabled-table {
    opacity: 0.4;
    pointer-events: none;
    user-select: none;
}
';
?>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <?php if ($msgSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msgSuccess) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="mb-3">
            <label for="idAula" class="form-label">Laboratorio (Aula):</label>
            <select class="form-select" name="idAula" id="idAula" required>
                <option value="">-- Seleziona Aula --</option>
                <?php foreach ($aulas as $a): ?>
                    <option value="<?= $a['idAula'] ?>" <?= $a['idAula'] === $idAula ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['nAula']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <table class="table table-striped table-bordered<?= $idAula ? '' : ' disabled-table' ?>" id="orarioTable">
            <caption id="captionAula">Aula: <strong><?= htmlspecialchars($nAula) ?></strong></caption>
            <thead class="table-dark">
                <tr><th>Ora</th><?php foreach ($giorni as $g): ?><th><?= $g ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
            <?php foreach ($ore as $idx => $fascia): ?>
                <tr>
                    <td><?= $fascia['inizio'] . ' - ' . $fascia['fine'] ?></td>
                    <?php foreach ($giorni as $dIdx => $g): ?>
                        <?php
                        $prev = $schedule[$g][$fascia['inizio']] ?? ['docente' => null, 'classe' => null];
                        ?>
                        <td>
                            <select class="form-select" name="docente_<?= $dIdx ?>_<?= $idx ?>">
                                <option value="">-- Docente --</option>
                                <?php foreach ($docenti as $doc): ?>
                                    <option value="<?= $doc['idDocente'] ?>"
                                        <?= $doc['idDocente'] === $prev['docente'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($doc['cognome'] . ' ' . $doc['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-control mt-1"
                                   name="classe_<?= $dIdx ?>_<?= $idx ?>"
                                   placeholder="Classe"
                                   value="<?= htmlspecialchars($prev['classe'] ?? '') ?>">
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <h6>Registra appuntamenti va fatto una volta solo dopo l'approvazione dell'orario definitivo e dopo aver compilato tutti i campi di ogni laboratorio.
            Ogni settimana vanno calendarizzati gli appuntamenti per la settimana successiva. Gli appuntamenti non andranno a sovrascrivere altri impegni già previsti</h6>
        <button type="submit" class="btn btn-primary mt-3">Registra Appuntamenti</button>
    </form>
    <button class="btn btn-secondary btn-print mt-3" onclick="fillOrarioSett()">
    Calendarizza per la prossima settimana
    </button>
    <div id="message" class="alert alert-info mt-3"></div>

</div>

<?php include 'includes/footer.php'; ?>
