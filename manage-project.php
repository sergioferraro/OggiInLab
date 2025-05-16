<?php
// manage-project.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "includes/config.php";
$nome_progetto = $desc_progetto = "";
$id_tutor = $id_esperto = null;
$errors = [];

// Check login session
if (empty($_SESSION["alogin"])) {
    header("Location: index.php");
    exit();
}

// Get project data based on ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errors[] = "Progetto non specificato.";
} else {
    $projectId = intval($_GET['id']);
    
    try {
        // Fetch project with docente names using JOINs
        $stmt = $dbh->prepare("SELECT 
            p.*, 
            t.cognome AS TutorCognome, 
            e.cognome AS EspertoCognome 
            FROM progetto p
            LEFT JOIN docente t ON p.idTutor = t.idDocente
            LEFT JOIN docente e ON p.idEsperto = e.idDocente
            WHERE idProgetto = :id");
        
        $stmt->execute([':id' => $projectId]);
        $projectData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Errore nel recupero dati: " . htmlspecialchars($e->getMessage());
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    error_log("manage-project.php: POST request received.");
    error_log("manage-project.php: GET parameters during POST: " . var_export($_GET, true));
    error_log("manage-project.php: Project ID variable (before use): " . var_export($projectId ?? 'not set', true));

    // Validate and sanitize inputs
    $formData = [
        'nomeProgetto' => $_POST['nomeProgetto'],
        'descProgetto' => $_POST['Desc_Progetto'],
        'idTutor' => ($_POST['id_tutor'] === '') ? null : (int)$_POST['id_tutor'],
        'idEsperto' => ($_POST['id_esperto'] === '') ? null : (int)$_POST['id_esperto'],
        'CNP' => $_POST['CNP'],
        'CUP' => $_POST['CUP'],
        'startDate' => $_POST['startDate'],
        'endDate' => $_POST['endDate']
    ];
    
    
    // Perform validation
    if (empty($formData['nomeProgetto']) || empty($formData['descProgetto'])) {
        $errors[] = "Il nome e la descrizione sono obbligatori.";
    } elseif (
        !empty($formData['endDate']) && // Check if end_date is not empty or "0000-00-00"
        $formData['endDate'] != "0000-00-00" &&
        $formData['startDate'] > $formData['endDate']
    ) {
        $errors[] = "La data di inizio non può essere dopo quella di fine.";
    }
    
    // Update query
    if (empty($errors)) {
        try {
            $sqlUpdate = "
                UPDATE progetto SET 
                    nomeProgetto = :nome,
                    descProgetto = :descrizione,
                    idTutor = :tutor_id,
                    idEsperto = :esperto_id,
                    CNP = :cnp,
                    CUP = :cup,
                    startDate = :start,
                    endDate = :end
                WHERE idProgetto = :id";
                
            $stmt = $dbh->prepare($sqlUpdate);
            
            if ($stmt->execute([
                ':nome' => $formData['nomeProgetto'],
                ':descrizione' => $formData['descProgetto'],
                ':tutor_id' => $formData['idTutor'],
                ':esperto_id' => $formData['idEsperto'],
                ':cnp' => $formData['CNP'],
                ':cup' => $formData['CUP'],
                ':start' => $formData['startDate'],
                ':end' => $formData['endDate'],
                ':id' => $projectId
            ])) {
                header("Location: manage-project.php?id={$projectId}&saved=1");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Errore database: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>OggiInLab | Modifica Progetto</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME STYLE  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
    /* Sets the background color of the body to dark gray and text color to light */
    body {
        background-color: #1e1e1e;
        color: #f8f9fa;
    }

    /* Sets the background color and border color for cards */
    .card {
        background-color: #2c2c2c !important;
        border-color: #444;
    }

    /* Styles for primary text links, setting their color to a specific blue */
    .btn-link.text-primary {
        color: #0d6efd !important;
    }

    /* Overrides the default background color of dark theme elements */
    .bg-dark {
        background-color: #1e1e1e !important;
    }

    /* Ensures text within white context is colored light for readability */
    .text-white {
        color: #f8f9fa !important;
    }

    /* Styles form controls with a dark background, specific text and border colors */
    .form-control.bg-dark {
        background-color: #1e1e1e;
        color: #f8f9fa;
        border-color: #444;
    }

    /* Defines styles for primary buttons including hover effects */
    .btn.btn-primary {
        background-color: #0d6efd !important; /* Light blue */
        border-color: #0d6efd !important;
        color: white !important;
    }

    .btn.btn-primary:hover {
        background-color: #0a58ca !important; /* Darker blue on hover */
        border-color: #0a58ca !important;
        color: white !important;
    }

    /* Ensures form labels are bold and have a small margin below them */
    .form-label {
        font-weight: bold;
        margin-bottom: 5px;
    }

    /* Styles for file input elements, including padding and borders */
    /* Style for the file input element */
    input[type="file"] {
        padding: 10px !important;
        border-radius: 4px;
    }
    input[type="file"] {
        background-color: #1e1e1e !important; /* Dark gray background */
        color: #f8f9fa !important; /* Light text color */
        border: 2px solid #444 !important; /* Gray border */
        padding: 10px !important;
        border-radius: 4px !important;
    }

    /* Sets font size and removes padding for primary text links */
    .btn-link.text-primary {
        font-size: 1.2rem; /* Emoji sizes */
        padding: 0;
    }

    /* Distance between image and like button */
    form.d-inline.mt-2 {
        margin-top: 15px;
    }

    /* Styles form controls with a specific background and text color */
    .form-control {
        background-color: #5c5e62 !important;
        color: white !important;
    }

    /* Styles select inputs with a specific background and text color */
    .form-select {
        background-color: #5c5e62 !important;
        color: white !important;
    }


    </style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include "includes/header.php"; ?>

<div class="container mt-4">
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php elseif (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Modifiche salvate con successo!</div>
    <?php endif; ?>

    <!-- Project Form -->
    <form method="POST" action="">
        <h2>Modifica Progetto #<?= $projectId ?></h2>

        <!-- Basic Details -->
        <div class="form-group">
            <label for="nome">Nome Progetto:</label>
            <input type="text" name="nomeProgetto" value="<?= htmlspecialchars($projectData['nomeProgetto'] ?? '') ?>" required class="form-control form-control-lg">
        </div>

        <div class="form-group">
            <label>Descrizione:</label>
            <textarea name="Desc_Progetto" rows="4" class="form-control"><?= htmlspecialchars($projectData['descProgetto'] ?? '') ?></textarea>
        </div>

        <!-- CNP/CUP -->
        <div class="row mb-3">
            <div class="col-md-6 form-group">
                <label>CNP:</label>
                <input type="text" name="CNP" value="<?= htmlspecialchars($projectData['CNP'] ?? '') ?>" class="form-control">
            </div>

            <div class="col-md-6 form-group">
                <label>CUP:</label>
                <input type="text" name="CUP" value="<?= htmlspecialchars($projectData['CUP'] ?? '') ?>" class="form-control">
            </div>
        </div>

        <!-- Date Range -->
        <div class="row mb-3">
            <div class="col-md-6 form-group">
                <label>Data Inizio:</label>
                <input type="date" name="startDate" value="<?= htmlspecialchars($projectData['startDate'] ?? '') ?>"  class="form-control">
            </div>

            <div class="col-md-6 form-group">
                <label>Data Fine:</label>
                <input type="date" name="endDate" value="<?= htmlspecialchars($projectData['endDate'] ?? '') ?>"  class="form-control">
            </div>
        </div>

        <!-- Docenti Selection -->
        <h4>Docenti Associati</h4>

        <?php foreach (['Tutor', 'Esperto'] as $role): ?>
            
            <div class="border p-3 mb-4 rounded">
                <legend><?= $role ?></legend>
                
                <div class="form-check form-check-inline">
                    <input type="radio" id="<?= strtolower($role) ?>_existing"
                        name="<?= strtolower($role).'_option' ?>" value="existing"
                        <?= (isset($projectData['ID_'.$role]) && $projectData['ID_'.$role] != 0) ? 'checked' : '' ?>>
                    
                    <label for="<?= strtolower($role) ?>_existing">Seleziona esistente</label>
                </div>

                <select name="id_<?= strtolower($role) ?>" class="form-control mt-2">
                    <option value="">-- Selezionare --</option>
                    <?php 
                    try {
                        $docQuery = $dbh->query("SELECT idDocente, CONCAT(nome,' ',cognome) AS FullName FROM docente WHERE isDeleted=0");
                        while ($row = $docQuery->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $row['idDocente'] ?>" 
                                <?= (isset($projectData['id'.$role]) && $row['idDocente'] == $projectData['id'.$role]) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['FullName']) ?>
                            </option>
                    <?php endwhile;
                    } catch(PDOException $e) {
                        echo '<option value="">Errore caricamento docenti</option>';
                    }
                    ?>
                </select>

                <div class="form-check form-check-inline mt-3">
            <input type="radio" 
                id="<?= strtolower($role) ?>_new" 
                name="<?= strtolower($role) ?>_option" 
                value="new" 
                data-bs-toggle="modal" 
                data-bs-target="#<?= strtolower($role) ?>Modal"
                <?= (empty($projectData['ID_'.$role])) ? 'checked' : '' ?>>
            <label for="<?= strtolower($role) ?>_new">Aggiungi nuovo</label>
        </div>

            </div> <!-- End Docenti Section -->
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary mt-4">Salva Modifiche</button>
    </form>
</div>

  <!-- Modal TUTOR -->
  <div class="modal fade" id="tutorModal" tabindex="-1" aria-labelledby="tutorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="tutorForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tutorModalLabel">Aggiungi Tutor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nomeTutor" class="form-label">Nome</label>
                            <input type="text" name="nome" required class="form-control" id="nomeTutor">
                        </div>
                        <div class="mb-3">
                            <label for="cognomeTutor" class="form-label">Cognome</label>
                            <input type="text" name="cognome" required class="form-control" id="cognomeTutor">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Aggiungi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<!-- Modal ESPERTO -->
<div class="modal fade" id="espertoModal" tabindex="-1" aria-labelledby="espertoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="espertoForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="espertoModalLabel">Aggiungi Esperto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nomeEsperto" class="form-label">Nome</label>
                            <input type="text" name="nome" required class="form-control" id="nomeEsperto">
                        </div>
                        <div class="mb-3">
                            <label for="cognomeEsperto" class="form-label">Cognome</label>
                            <input type="text" name="cognome" required class="form-control" id="cognomeEsperto">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Aggiungi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Bootstrap JS -->
<!-- FOOTER SECTION START-->
<?php include "includes/footer.php"; ?>

 

<script>
document.addEventListener('DOMContentLoaded', function() {
 // Management for the tutor's form
 const tutorForm = document.getElementById('tutorForm');
     // Initialize modals and handle focus properly
    const tutorModal = new bootstrap.Modal(document.getElementById('tutorModal'));
    const espertoModal = new bootstrap.Modal(document.getElementById('espertoModal'));
    // Tutor Modal Focus Handling
    tutorModal._element.addEventListener('shown.bs.modal', function () {
        this.querySelector('input[name="nome"]').focus();
    });

    // Esperto Modal Focus Handling
    espertoModal._element.addEventListener('shown.bs.modal', function () {
        this.querySelector('input[name="nome"]').focus();
    });

    // Optional: Handle closing to return focus to the triggering element
    document.querySelectorAll('[data-bs-target="#tutorModal"]').forEach(trigger => {
        trigger.addEventListener('click', function () {
            // Focus returns here after closing
        });
    });
    if (tutorForm) {
        tutorForm.addEventListener('submit', function (e) {
            e.preventDefault();
            addDocente('tutor');
        });
    }

    // Management for the expert's form
    const espertoForm = document.getElementById('espertoForm');
    if (espertoForm) {
        espertoForm.addEventListener('submit', function (e) {
            e.preventDefault();
            addDocente('esperto');
        });
    }

    function addDocente(type) {
        const form = type === 'tutor' ? document.getElementById('tutorForm') : document.getElementById('espertoForm');
        const formData = new FormData(form);

        fetch('assets/utils/add_docente.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log("Risposta ricevuta:", data);
            if (data.success) {
                const selectId = type === 'tutor' ? 'tutorSelect' : 'espertoSelect';
                const select = document.getElementById(selectId);

                const option = new Option(data.docente.cognome + ' ' + data.docente.nome, data.docente.id, true, true);
                select.add(option);
                select.value = data.docente.id;

                // Select the "existing" radio button
                document.getElementById(`select_${type}`).checked = true;

                // Close the modal
                const modalId = `#${type}Modal`;
                $(modalId).modal('hide');


                // Clear the form
                form.reset();
            } else {
                alert('Errore: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Errore nella richiesta:', error);
            alert("Errore durante l'invio dei dati.");
        });
    }
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
</html>