<?php
// orario_lab.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */

include 'includes/config.php';
session_start();
if (empty($_SESSION["alogin"])) {
    header("location: index.php");
    exit();
}
// Definition of Time Slots (8 hours)
try {
    // PDO connection
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query
    $stmt = $dbh->query("SELECT DATE_FORMAT(inizio, '%H:%i') AS inizio, DATE_FORMAT(fine, '%H:%i') AS fine FROM fasce ORDER BY id");


    // Empty array
    $ore = [];

    // Populate array
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ore[] = [
            'inizio' => $row['inizio'],
            'fine'   => $row['fine']
        ];
    }

} catch (PDOException $e) {
    // Errors handle
    echo "Errore di connessione o query: " . $e->getMessage();
}
// Days of the Week Mon-Sat
global $giorni;
$giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];

// Retrieve Project "orario"
$stmt = $dbh->prepare("SELECT idProgetto, nomeProgetto FROM progetto WHERE nomeProgetto = 'orario'");
$stmt->execute();
$project = $stmt->fetch(PDO::FETCH_ASSOC);
$idProgetto = $project['idProgetto'];

// Fetch Classrooms and Teachers
$aulas   = $dbh->query("SELECT * FROM aula ORDER BY nAula")->fetchAll(PDO::FETCH_ASSOC);
$docenti = $dbh->query("SELECT idDocente, cognome, nome FROM docente ORDER BY cognome, nome")->fetchAll(PDO::FETCH_ASSOC);

//  Verify Selected Room idAula (GET or POST)
$idAula = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['idAula'])) {
    $idAula = (int)$_POST['idAula'];
} elseif (!empty($_GET['idAula'])) {
    $idAula = (int)$_GET['idAula'];
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $idAula) {
    $autore = $_SESSION['id'];
    
    //  Remove Any Previous Entries
    $dbh->beginTransaction();
    $stmt = $dbh->prepare("DELETE FROM orario_settimana WHERE idProgetto = ? AND idAula = ? AND autore = ?");
    $stmt->execute([$idProgetto, $idAula, $autore]);

    // Re-enter
    foreach ($giorni as $g) {
        foreach ($ore as $fascia) {
            $d = $g;
            $start = $fascia['inizio'];
            $end   = $fascia['fine'];
            $docKey   = 'docente_'.array_search($g, $giorni).'_'.array_search($fascia, $ore);
            $classKey = 'classe_'.array_search($g, $giorni).'_'.array_search($fascia, $ore);
            $doc      = !empty($_POST[$docKey])   ? $_POST[$docKey]   : null;
            $cls      = !empty($_POST[$classKey]) ? $_POST[$classKey] : null;
            if ($cls) {
                $dbh->prepare(
                    "INSERT INTO orario_settimana
                      (idProgetto, idAula, idDocente, classe, giorno, ora_inizio, ora_fine, autore)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([$idProgetto, $idAula, $doc, $cls, $d, $start, $end, $autore]);
            }
                }
    }
    $dbh->commit();
    $msgSuccess = !empty($_GET['saved']) ? "Appuntamenti registrati con successo!" : null;
    // Reload the Updated Schedule
    header("Location: orario_lab.php?idAula=".$idAula.'&saved=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>OggiInLab | Gestione orario settimanale <?= htmlspecialchars($project['nomeProgetto']) ?></title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">
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
        background-color: #0d6efd !important; 
        border-color: #0d6efd !important;
        color: white !important;
        }

        .btn.btn-primary:hover {
            background-color: #0a58ca !important; 
            border-color: #0a58ca !important;
            color: white !important;
        }
        .form-label {
        font-weight: bold;
        margin-bottom: 5px;
        }

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
            font-size: 1.2rem; /* emoji sizes*/
            padding: 0;
        }

        /* Distance Between Image and Like Button */
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <?php if (!empty($_GET['saved'])): ?>
        <div class="alert alert-success"></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="idAula" class="form-label">Laboratorio (Aula):</label>
            <select class="form-select" name="idAula" id="idAula" required>
                <option value="">-- Seleziona Aula --</option>
                <?php foreach ($aulas as $a): ?>
                    <option value="<?= $a['idAula'] ?>" <?= $a['idAula']==$idAula ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['nAula']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <table class="table table-striped table-bordered">
            <caption>Aula: <strong><?= $idAula ? htmlspecialchars(
                        array_filter($aulas, fn($x) => $x['idAula']==$idAula)[array_key_first(
                            array_filter($aulas, fn($x) => $x['idAula']==$idAula)
                        )]['nAula']
                    ) : '--' ?></strong></caption>
            <thead class="table-dark">
                <tr><th>Ora</th><?php foreach ($giorni as $g): ?><th><?= $g ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
            <?php foreach ($ore as $idx => $fascia): ?>
                <tr>
                    <td><?= $fascia['inizio'].' - '.$fascia['fine'] ?></td>
                    <?php foreach ($giorni as $dIdx => $g): ?>
                        <?php
                        $prev = $schedule[$g][$fascia['inizio']] ?? ['docente'=>null,'classe'=>null];
                        ?>
                        <td>
                            <select class="form-select" name="docente_<?= $dIdx ?>_<?= $idx ?>">
                                <option value="">-- Docente --</option>
                                <?php foreach ($docenti as $doc): ?>
                                    <option value="<?= $doc['idDocente'] ?>"
                                        <?= $doc['idDocente']==$prev['docente'] ? 'selected' : '' ?> >
                                        <?= htmlspecialchars($doc['cognome'].' '.$doc['nome']) ?>
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
    <button class="btn btn-secondary btn-print" onclick="fillOrarioSett()">
    Calendarizza per la prossima settimana
    </button>
    <div id="message" class="alert alert-info mt-3"></div>

</div>

<?php include 'includes/footer.php'; ?>


<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script>
// Update Caption with Selected Room Name
document.querySelector('select[name="idAula"]').addEventListener('change', function(){
    var txt = this.options[this.selectedIndex].text;
    document.getElementById('captionAula').textContent = txt;
});
// Refresh Page on Room Change
$('#idAula').on('change', function() {
    const id = $(this).val();
    window.location.href = 'orario_lab.php' + (id ? '?idAula=' + id : '');
});
function fillOrarioSett() {
    fetch('assets/utils/fillOrarioSett2.php')
    .then(response => {
            if (!response.ok) {
                throw new Error('Risposta del server non valida');
            }
            return response.json();
        })
        .then(data => {
            // Show Message (if Present)
            if (data.message) {
                alert(data.message);
                document.getElementById('message').innerText = data.message;
            }

            // Handle
            if (data.status === 'success') {
                console.log('Dati inseriti:', data.inserted);
            } else {
                console.error('Errore:', data.message || 'Stato non riconosciuto');
            }
        })
        .catch(error => {
            console.error('Errore durante la chiamata AJAX:', error);
            alert('Si è verificato un errore. Riprova.');
        });
}
</script>
</body>
</html>
