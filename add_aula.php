<?php
// add_aula.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);


include "includes/config.php";

if (empty($_SESSION["alogin"])) {
    header("location: index.php");
    exit();
}

try {
    $stmt = $dbh->query("SELECT * FROM aula ORDER BY idAula"); 
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Errore database aule: " . $e->getMessage());
}

// Handle form submission
$errors = [];
$success = '';
$editingAula = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Addition of Classroom (action = 'add')
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $nAula = trim($_POST['nAula']);
        $nPosti = intval($_POST['nPosti']);
        $computer = isset($_POST['computer']) ? 1 : 0;
        $richiedeAt = isset($_POST['richiedeAt']) ? 1 : 0;
        $lim = isset($_POST['lim']) ? 1 : 0;
        $pcDocente = isset($_POST['pcDocente']) ? 1 : 0;

        // Validate
        if (empty($nAula)) {
            $errors[] = "Inserisci il numero dell'aula.";
        }
        if (empty($nPosti) || $nPosti <= 0) {
            $errors[] = "Inserisci il numero dei posti validi.";
        }

        if (empty($errors)) {
            // Prepare the Statement
            $stmt = $dbh->prepare("INSERT INTO aula (nAula, nPosti, computer, richiedeAt, lim, pcDocente) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            
            //  Execute the Query
            $success = $stmt->execute([$nAula, $nPosti, $computer, $richiedeAt, $lim, $pcDocente])
                ? "Aula aggiunta con successo."
                : "Errore: " . $stmt->errorInfo()[2];
        }
    } 
    // Modification of Classroom
    elseif (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $idAula = intval($_POST['idAula']);
        try {
            $stmt = $dbh->prepare("SELECT * FROM aula WHERE idAula = ?");
            $stmt->execute([$idAula]);
            $editingAula = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Errore durante la ricerca dell'aula per modifica: " . $e->getMessage());
        }
    } 
    // Update of Classroom
    elseif (isset($_POST['action']) && $_POST['action'] == 'update') {
        $idAula = intval($_POST['idAula']);
        $nAula = trim($_POST['nAula']);
        $nPosti = intval($_POST['nPosti']);
        $computer = isset($_POST['computer']) ? 1 : 0;
        $richiedeAt = isset($_POST['richiedeAt']) ? 1 : 0;
        $lim = isset($_POST['lim']) ? 1 : 0;
        $pcDocente = isset($_POST['pcDocente']) ? 1 : 0;

        // Validate
        if (empty($nAula)) {
            $errors[] = "Inserisci il numero dell'aula.";
        }
        if (empty($nPosti) || $nPosti <= 0) {
            $errors[] = "Inserisci il numero dei posti validi.";
        }

        if (empty($errors)) {
            // Prepare the Statement
            $stmt = $dbh->prepare("UPDATE aula SET nAula = ?, nPosti = ?, computer = ?, richiedeAt = ?, lim = ?, pcDocente = ? WHERE idAula = ?");
            
            // Execute the Query
            $success = $stmt->execute([$nAula, $nPosti, $computer, $richiedeAt, $lim, $pcDocente, $idAula])
                ? "Aula aggiornata con successo."
                : "Errore: " . $stmt->errorInfo()[2];
        }
    } 
    // Deletion of Classroom
    elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $idAula = intval($_POST['idAula']);
        try {
            // Verify if there are Future Appointments
            $stmtCheck = $dbh->prepare("SELECT COUNT(*) FROM appuntamento WHERE luogo = ? AND data > CURRENT_DATE");
            $stmtCheck->execute([$idAula]);
            $countAppuntamenti = $stmtCheck->fetchColumn();
    
            if ($countAppuntamenti > 0) {
                //  If There Are Future Appointments, Show an Error
                $errors[] = "Impossibile eliminare l'aula. Esistono appuntamenti futuri.";
            } else {
                // Otherwise Proceed with the Deletion
                $stmtDelete = $dbh->prepare("DELETE FROM aula WHERE idAula = ?");
                $success = $stmtDelete->execute([$idAula])
                    ? "Aula eliminata con successo."
                    : "Errore: " . $stmtDelete->errorInfo()[2];
            }
        } catch (PDOException $e) {
            error_log("Errore durante l'eliminazione dell'aula: " . $e->getMessage());
            $errors[] = "Errore durante l'eliminazione.";
        }
    
        // Reload Data to Display Changes
        try {
            $stmt = $dbh->query("SELECT * FROM aula ORDER BY idAula");
            $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Errore database aule: " . $e->getMessage());
        }
    }

    // Reload Data to Display Changes
    try {
        $stmt = $dbh->query("SELECT * FROM aula ORDER BY idAula");
        $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Errore database aule: " . $e->getMessage());
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
    <!--[if IE]>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <![endif]-->
    <title>OggiInLab | Aule</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME STYLE  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        background-color: #0d6efd !important; /* Light Blue */
        border-color: #0d6efd !important;
        color: white !important;
        }

        .btn.btn-primary:hover {
            background-color: #0a58ca !important; /* Dark Blue on Hover */
            border-color: #0a58ca !important;
            color: white !important;
        }
        .form-label {
        font-weight: bold;
        margin-bottom: 5px;
        }

        /* Style for File input */
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
            font-size: 1.2rem; /* Emoji Sizes */
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
</head>

<body>
<?php include "includes/header.php"; ?>

<div class="container mt-5">
    <div class="row">
        <h4 class="mb-3 text-center">Gestione Aule</h4>
    </div>

    <!-- Error/Success messages -->
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
    <!-- List of Existing Classrooms -->
    <div class="row mt-4">
        <div class="col-md-6">
            <h5>Elenco Aule Esistenti</h5>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome Aula</th>
                        <th>Posti</th>
                        <th>Computer</th>
                        <th>Richiede AT</th>
                        <th>LIM</th>
                        <th>PC Docente</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aulas as $aula): ?>
                        <tr>
                            <td><?= htmlspecialchars($aula['nAula']) ?></td>
                            <td><?= htmlspecialchars($aula['nPosti']) ?></td>
                            <td><?= $aula['computer'] ? 'Si' : 'No' ?></td>
                            <td><?= $aula['richiedeAt'] ? 'Si' : 'No' ?></td>
                            <td><?= $aula['lim'] ? 'Si' : 'No' ?></td>
                            <td><?= $aula['pcDocente'] ? 'Si' : 'No' ?></td>
                            <td>
                                <!-- Container for Adjacent Buttons -->
                                <div style="display: flex; gap: 8px;">
                                    <!-- Form to Modify the Classroom -->
                                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                        <input type="hidden" name="idAula" value="<?= $aula['idAula'] ?>">
                                        <input type="hidden" name="action" value="edit">
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </form>
                                
                                    <!-- Delete Button (Only for Super Admin) -->
                                    <?php if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 1): ?>
                                        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <input type="hidden" name="idAula" value="<?= $aula['idAula'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
        <h5><?= ($editingAula ? 'Modifica Aula' : 'Aggiungi Nuova Aula') ?></h5>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <?php if ($editingAula): ?>
                <input type="hidden" name="idAula" value="<?= $editingAula['idAula'] ?>">
                <input type="hidden" name="action" value="update">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>
            <div class="mb-3">
                <label for="nAula" class="form-label">Nome Aula:</label>
                <input type="text" class="form-control" id="nAula" name="nAula" required 
                       value="<?= $editingAula ? htmlspecialchars($editingAula['nAula']) : '' ?>">
            </div>
            <div class="mb-3">
                <label for="nPosti" class="form-label">Numero Posti:</label>
                <input type="number" class="form-control" id="nPosti" name="nPosti" required 
                       value="<?= $editingAula ? htmlspecialchars($editingAula['nPosti']) : '' ?>">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="computer" name="computer"
                       <?= isset($editingAula) && $editingAula['computer'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="computer">Computer</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="richiedeAt" name="richiedeAt"
                       <?= isset($editingAula) && $editingAula['richiedeAt'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="richiedeAt">Richiede AT</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="lim" name="lim"
                       <?= isset($editingAula) && $editingAula['lim'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="lim">LIM</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="pcDocente" name="pcDocente"
                       <?= isset($editingAula) && $editingAula['pcDocente'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="pcDocente">PC Docente</label>
            </div>
            <button type="submit" class="btn btn-primary">Salva Aula</button>
        </form>
    </div>
    </div>
    <!-- Form to add new aula -->
    


<?php include 'includes/footer.php';?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

</body>
</html>
