<?php
//calend_ann.php
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
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Management for school start-end dates
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'] &&
    isset($_POST['startDate']) && isset($_POST['endDate'])) {
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    
    try {
        $stmt = $dbh->prepare("UPDATE progetto SET startDate = :start, endDate = :end WHERE nomeProgetto = 'orario'");
        $stmt->execute([
            ':start' => $startDate,
            ':end' => $endDate
        ]);
    } catch (PDOException $e) {
        echo "Errore salvataggio date: " . $e->getMessage();
    }
}
function CalcolaAnnoScolastico(DateTime $data): string
{
    $mese = (int)$data->format('m');
    $anno = (int)$data->format('Y');
    if ($mese >= 9 && $mese <= 12) {
        $annoseg=$anno+1;
        return "{$anno}/{$annoseg}";
    } else {
        $annoprec=$anno-1;
        return "{$annoprec}/{$anno}";
    }
}
function CalcolaPasqua(DateTime $year): string {
    // Calculate Easter date using the algorithm
    $year = (int)$year->format('Y');
    $a = $year % 19;
    $b = intdiv($year, 100);
    $c = $year % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    // Determine month and day
    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return "{$year}-{$month}-{$day}";
}

$festivi = [];
$annoScolasticoCorrente = CalcolaAnnoScolastico(new DateTime());
$pasqua = CalcolaPasqua(new DateTime());
$dataPasquetta = new DateTime($pasqua);

$dataPasquetta->modify('+1 day');
$pasquetta = $dataPasquetta->format('Y-m-d');

// Split the academic year into anno1 and anno2
list($anno1, $anno2) = explode('/', $annoScolasticoCorrente);


// Define required holidays
$requiredHolidays = [
    ["date" => "$anno1-12-08", "description" => "Immacolata"],
    ["date" => "$anno1-11-01", "description" => "Tutti i Santi"],
    ["date" => "$anno1-12-25", "description" => "Natale"],
    ["date" => "$anno1-12-26", "description" => "Santo Stefano"],
    ["date" => "$anno2-01-06", "description" => "Epifania"],
    ["date" => "$anno2-04-25", "description" => "Festa della Liberazione"],
    ["date" => "$anno2-05-01", "description" => "Festa del Lavoro"],
    ["date" => "$anno2-06-02", "description" => "Festa della Repubblica"],
    ["date" => "$pasqua", "description" => "Pasqua"],
    ["date" => "$pasquetta", "description" => "Lunedì dell'angelo"],
    ["date" => "$anno2-08-15", "description" => "Ferragosto"]
];

// Check and insert each holiday if it doesn't exist
foreach ($requiredHolidays as $holiday) {
    $giorno = $holiday['date'];
    $nome = $holiday['description'];
    
    $stmtCheck = $dbh->prepare("SELECT COUNT(*) FROM calendario WHERE annoScolastico = :anno AND giorno = :giorno");
    $stmtCheck->execute([
        ':anno' => $annoScolasticoCorrente,
        ':giorno' => $giorno
    ]);
    
    if ($stmtCheck->fetchColumn() === 0) {
        $stmtInsert = $dbh->prepare("INSERT INTO calendario (annoScolastico, giorno, nomeChiusura) VALUES (:anno, :giorno, :nome)");
        $stmtInsert->execute([
            ':anno' => $annoScolasticoCorrente,
            ':giorno' => $giorno,
            ':nome' => $nome
        ]);
    }
}
// Management of dynamic addition and saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if (isset($_POST['data']) && isset($_POST['descrizione'])) {
        $data = $_POST['data'];
        $descrizione = $_POST['descrizione'];
        
        if (!empty($data)) {
            try {
                $stmt = $dbh->prepare("INSERT INTO calendario (annoScolastico, giorno, nomeChiusura) VALUES (:anno, :giorno, :nome)");
                $stmt->bindParam(':anno', $annoScolasticoCorrente, PDO::PARAM_STR);
                $stmt->bindParam(':giorno', $data, PDO::PARAM_STR);
                $stmt->bindParam(':nome', $descrizione, PDO::PARAM_STR);
                $stmt->execute();
            } catch (PDOException $e) {
                // Handle insertion error
                echo "Errore durante l'inserimento: " . $e->getMessage();
            }
        }
    }
    // Reload data after insertion to display the new entry
    $sql = "SELECT idCalendario, giorno, nomeChiusura FROM calendario WHERE annoScolastico = :anno ORDER BY giorno";
    $query = $dbh->prepare($sql);
    $query->bindParam(':anno', $annoScolasticoCorrente, PDO::PARAM_STR);
    $query->execute();
    $festivi = $query->fetchAll(PDO::FETCH_OBJ);
} else {
    // Retrieve existing entries for the current school year
    $sql = "SELECT idCalendario, giorno, nomeChiusura FROM calendario WHERE annoScolastico = :anno ORDER BY giorno";
    $query = $dbh->prepare($sql);
    $query->bindParam(':anno', $annoScolasticoCorrente, PDO::PARAM_STR);
    $query->execute();
    $festivi = $query->fetchAll(PDO::FETCH_OBJ);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <title>OggiInLab | Calendario scolastico</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
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
            font-size: 1.2rem; 
            padding: 0;
        }

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
    <?php include "includes/header.php"; ?>

        <div class="container mt-5">
            <div class="row">
                <h4 class="mb-3 text-center">Pannello di controllo amministratore</h4>
            </div>
            <!--  New form for school year dates -->
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="row mb-3">
                    <div class="col">
                        <label for="startDate" class="form-label">Data inizio anno scolastico:</label>
                        <input type="date" class="form-control" name="startDate" required id="startDate">
                    </div>
                    <div class="col">
                        <label for="endDate" class="form-label">Data fine anno scolastico:</label>
                        <input type="date" class="form-control" name="endDate" required id="endDate">
                    </div>
                </div>
                <!-- Added submit button -->
                <div class="row">
                <h6>*Le date di inizio/fine anno vanno impostate 1 volta sola all'inizio dell'anno secondo il calendario scolastico di riferimento, faranno parte del progetto "orario scolastico"</h6>
                    <div class="col text-center">
                        <button type="submit" class="btn btn-primary btn-lg">Salva date inizio/fine anno</button>
                    </div>
                    
                </div>
            </form>
            <div class="row mb-4">
                <div class="col-md-8 offset-md-2">
                    <h5>Festività e chiusure per l'anno scolastico <?= htmlspecialchars($annoScolasticoCorrente) ?></h5>
                    <h6>*Le festività (compresa la Pasqua) sono calcolate automaticamente ed è sconsigliabile cancellarle. Aggiungere solo le chiusure e/o le sospensioni didattiche secondo l'autonomia d'Istituto</h6>
                    <?php if (!empty($festivi)) : ?>
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Descrizione</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($festivi as $festa) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($festa->giorno))) ?></td>
                                            <td><?= htmlspecialchars($festa->nomeChiusura) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                        data-id="<?= htmlspecialchars($festa->idCalendario) ?>">
                                                    <i class="fas fa-trash"></i> Elimina
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    <?php else : ?>
                        <p>Non ci sono festività o chiusure registrate per questo anno scolastico.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <h5>Aggiungi una nuova chiusura</h5>
                    <form method="post" action="" id="aggiungiChiusuraForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="idCalendario" id="editAppointmentId">

                        <div class="mb-3">
                            <label for="giornoChiusura" class="form-label">Data</label>
                            <input type="date" class="form-control" id="editAppointmentData" name="data" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkboxSospensione" name="sospensione">
                                <label class="form-check-label" for="checkboxSospensione">
                                    Sospensione dell'attività didattica
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nomeChiusura" class="form-label">Descrizione</label>
                            <input type="text" class="form-control" id="editAppointmentDescrizione" name="descrizione">
                        </div>

                        <button type="submit" class="btn btn-primary">Aggiungi chiusura</button>
                    </form>
                    <div id="nuoviCampi">
                        </div>
                    <button type="button" class="btn btn-success mt-2" id="aggiungiAltro">Aggiungi un'altra chiusura</button>
                </div>
            </div>
        </div>


        <div class="modal fade" id="eliminaEventoModal" tabindex="-1" aria-labelledby="eliminaEventoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eliminaEventoModalLabel">Conferma eliminazione</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Sei sicuro di voler eliminare questo evento?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <form id="eliminaEventoForm" method="post" action="assets/utils/elimina_calendario.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="idCalendario" id="modalDeleteId">
                            <button type="submit" class="btn btn-danger">Elimina</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var counter = 0;

            $("#aggiungiAltro").click(function() {
                counter++;
                var newDiv = $('<div class="row mb-3" id="campo' + counter + '">');
                newDiv.append('<div class="col-md-6"><label for="dataNuova' + counter + '" class="form-label">Data</label><input type="date" class="form-control" id="dataNuova' + counter + '" name="nuove_chiusure[' + counter + '][data]" required></div>');
                newDiv.append('<div class="col-md-6"><label for="descrizioneNuova' + counter + '" class="form-label">Descrizione</label><input type="text" class="form-control" id="descrizioneNuova' + counter + '" name="nuove_chiusure[' + counter + '][descrizione]"></div>');
                $("#nuoviCampi").append(newDiv);
            });

            

            $(".btn-delete").click(function() {
                var id = $(this).data('id');
                $("#modalDeleteId").val(id);
                $("#eliminaEventoModal").modal('show');
            });
            // Management of suspension checkbox
                $('#checkboxSospensione').change(function() {
                    const $descrizione = $('#editAppointmentDescrizione');
                    if ($(this).is(':checked')) {
                        $descrizione
                            .val('Sospensione dell’attività didattica')
                            .prop('readonly', true);
                    } else {
                        $descrizione
                            .val('')
                            .prop('readonly', false);
                    }
                }).change(); // Set initial state
                });
    </script>
     <?php include 'includes/footer.php';?>
    </body>
</html>