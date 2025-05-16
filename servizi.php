<?php
// servizi.php
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

$idAssistente = $_SESSION['id'];

try {
    // Fetch all services for the logged-in assistant
    $stmt = $dbh->prepare("SELECT s.*, a.nAula, p.nomeProgetto, ad.nomeCompleto
                            FROM servizi s
                            JOIN aula a ON s.serviziLuogo = a.idAula
                            LEFT JOIN progetto p ON s.serviziProj = p.idProgetto
                            JOIN admin ad ON s.idAssistente = ad.id
                            WHERE s.idAssistente = :idAssistente
                            ORDER BY s.serviziData DESC, s.serviziOraInizio DESC");
    $stmt->bindParam(':idAssistente', $idAssistente, PDO::PARAM_INT);
    $stmt->execute();
    $servizi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all available locations for the dropdown
    $stmtAula = $dbh->query("SELECT idAula, nAula FROM aula ORDER BY nAula");
    $aule = $stmtAula->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all available projects for the dropdown
    $stmtProgetto = $dbh->query("SELECT idProgetto, nomeProgetto FROM progetto ORDER BY nomeProgetto");
    $progetti = $stmtProgetto->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Errore database: " . $e->getMessage());
    $error_db = "Si è verificato un errore nel recupero dei dati.";
}

// Handle form submission for adding a new service
$errors = [];
$success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiungiServizio'])) {
    $serviziData = trim($_POST['serviziData']);
    $serviziOraInizio = trim($_POST['serviziOraInizio']);
    $serviziOraFine = trim($_POST['serviziOraFine']);
    $serviziDescrizione = trim($_POST['serviziDescrizione']);
    $serviziLuogo = intval($_POST['serviziLuogo']);
    $serviziProj = !empty($_POST['serviziProj']) ? intval($_POST['serviziProj']) : null;

    // Validation
    if (empty($serviziData)) {
        $errors[] = "Inserisci la data del servizio.";
    }
    if (empty($serviziOraInizio)) {
        $errors[] = "Inserisci l'ora di inizio del servizio.";
    }
    if (empty($serviziOraFine)) {
        $errors[] = "Inserisci l'ora di fine del servizio.";
    }
    if ($serviziOraInizio >= $serviziOraFine) {
        $errors[] = "L'ora di inizio deve essere precedente all'ora di fine.";
    }
    if (empty($serviziLuogo)) {
        $errors[] = "Seleziona il luogo del servizio.";
    }

    // Check for overlap
    $sql_check = "
        SELECT COUNT(*) FROM servizi
        WHERE serviziLuogo = :luogo
          AND serviziData = :data
          AND (
                (serviziOraInizio < :fine AND serviziOraFine > :inizio)
              )
    ";
    $stmt_check = $dbh->prepare($sql_check);
    $stmt_check->bindParam(':luogo', $serviziLuogo, PDO::PARAM_INT);
    $stmt_check->bindParam(':data', $serviziData, PDO::PARAM_STR);
    $stmt_check->bindParam(':inizio', $serviziOraInizio, PDO::PARAM_STR);
    $stmt_check->bindParam(':fine', $serviziOraFine, PDO::PARAM_STR);
    $stmt_check->execute();
    $count = $stmt_check->fetchColumn();

    if ($count > 0) {
        $errors[] = "L'orario selezionato si sovrappone a un servizio esistente in questa aula.";
    }

    if (empty($errors)) {
        try {
            $stmt = $dbh->prepare("INSERT INTO servizi (idAssistente, serviziData, serviziOraInizio, serviziOraFine, serviziDescrizione, serviziLuogo, serviziProj)
                                    VALUES (:idAssistente, :serviziData, :serviziOraInizio, :serviziOraFine, :serviziDescrizione, :serviziLuogo, :serviziProj)");
            $stmt->bindParam(':idAssistente', $idAssistente, PDO::PARAM_INT);
            $stmt->bindParam(':serviziData', $serviziData, PDO::PARAM_STR);
            $stmt->bindParam(':serviziOraInizio', $serviziOraInizio, PDO::PARAM_STR);
            $stmt->bindParam(':serviziOraFine', $serviziOraFine, PDO::PARAM_STR);
            $stmt->bindParam(':serviziDescrizione', $serviziDescrizione, PDO::PARAM_STR);
            $stmt->bindParam(':serviziLuogo', $serviziLuogo, PDO::PARAM_INT);
            $stmt->bindParam(':serviziProj', $serviziProj, PDO::PARAM_INT);

            $success = $stmt->execute() ? "Servizio aggiunto con successo." : "Errore nell'aggiunta del servizio.";
        } catch (PDOException $e) {
            error_log("Errore database inserimento servizio: " . $e->getMessage());
            $errors[] = "Errore del database durante l'inserimento del servizio.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>OggiInLab | Servizi</title>
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
    <script>
        function eliminaServizio(idServizio) {
            if (confirm("Sei sicuro di voler eliminare questo servizio?")) {
                $.ajax({
                    url: 'delete-servizi.php',
                    type: 'DELETE',
                    dataType: 'json',
                    data: { idServizio: idServizio },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            // Reload the page
                            location.reload();
                        } else {
                            alert("Errore durante l'eliminazione: " + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Errore AJAX:", error);
                        alert("Si è verificato un errore durante la comunicazione con il server.");
                    }
                });
            }
        }
    </script>
</head>

<body>
<?php include "includes/header.php"; ?>

<div class="container mt-5">
    <div class="row">
        <h4 class="mb-3 text-center">Gestione Servizi</h4>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_db); ?></div>
    <?php endif; ?>

    <div class="row mt-4">
        <div class="col-md-12">
            <h5>Elenco Servizi</h5>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tecnico</th>
                        <th>Data</th>
                        <th>Ora Inizio</th>
                        <th>Ora Fine</th>
                        <th>Descrizione</th>
                        <th>Luogo</th>
                        <th>Progetto</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($servizi)): ?>
                        <tr><td colspan="7" class="text-center">Nessun servizio trovato.</td></tr>
                    <?php else: ?>
                        <?php foreach ($servizi as $servizio): ?>
                            <tr>
                                <td><?= htmlspecialchars($servizio['nomeCompleto']) ?></td>
                                <td><?= htmlspecialchars($servizio['serviziData']) ?></td>
                                <td><?= htmlspecialchars($servizio['serviziOraInizio']) ?></td>
                                <td><?= htmlspecialchars($servizio['serviziOraFine']) ?></td>
                                <td><?= htmlspecialchars($servizio['serviziDescrizione']) ?></td>
                                <td><?= htmlspecialchars($servizio['nAula']) ?></td>
                                <td><?= htmlspecialchars($servizio['nomeProgetto'] ?? 'N/A') ?></td>
                                <td class="text-center">
                                    <button class="btn btn-danger btn-sm" onclick="eliminaServizio(<?= $servizio['idServizio'] ?>)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <h5>Aggiungi Nuovo Servizio</h5>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="idAssistente" value="<?= htmlspecialchars($idAssistente); ?>">
                <input type="hidden" name="aggiungiServizio" value="1">
                <div class="mb-3">
                    <label for="serviziData" class="form-label">Data:</label>
                    <input type="date" class="form-control" id="serviziData" name="serviziData" required>
                </div>
                <div class="mb-3">
                    <label for="serviziOraInizio" class="form-label">Ora Inizio:</label>
                    <input type="time" class="form-control" id="serviziOraInizio" name="serviziOraInizio" required>
                </div>
                <div class="mb-3">
                    <label for="serviziOraFine" class="form-label">Ora Fine:</label>
                    <input type="time" class="form-control" id="serviziOraFine" name="serviziOraFine" required>
                </div>
                <div class="mb-3">
                    <label for="serviziDescrizione" class="form-label">Descrizione:</label>
                    <textarea class="form-control" id="serviziDescrizione" name="serviziDescrizione"></textarea>
                </div>
                <div class="mb-3">
                    <label for="serviziLuogo" class="form-label">Luogo:</label>
                    <select class="form-select" id="serviziLuogo" name="serviziLuogo" required>
                        <option value="">Seleziona un luogo</option>
                        <?php foreach ($aule as $aula): ?>
                            <option value="<?= htmlspecialchars($aula['idAula']) ?>"><?= htmlspecialchars($aula['nAula']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="serviziProj" class="form-label">Progetto (opzionale):</label>
                    <select class="form-select" id="serviziProj" name="serviziProj">
                        <option value="">Nessun progetto</option>
                        <?php foreach ($progetti as $progetto): ?>
                            <option value="<?= htmlspecialchars($progetto['idProgetto']) ?>"><?= htmlspecialchars($progetto['nomeProgetto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Aggiungi Servizio</button>
            </form>
        </div>
    </div>

<?php include 'includes/footer.php';?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

</body>
</html>