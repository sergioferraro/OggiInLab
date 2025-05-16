<?php
// add-project.php
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
    header("location:index.php");
    exit();
} else {
    // Initialize variables for storing form data with default empty strings
    $nome_progetto = $desc_progetto = "";
    $id_tutor = $id_esperto = null;
    $errors = [];

    if (isset($_POST['submit'])) {
        // Retrieve and validate project details from POST request
        $nome_progetto = $_POST['nome_progetto'] ?? '';
        $desc_progetto = $_POST['desc_progetto'] ?? '';
        $id_tutor = $_POST['id_tutor'] ?? '';
        $id_tutor = $id_tutor === '' ? null : $id_tutor;

        $id_esperto = $_POST['id_esperto'] ?? '';
        $id_esperto = $id_esperto === '' ? null : $id_esperto;

        // Validate mandatory fields
        $has_errors = false;
        if (empty($nome_progetto)) {
            $errors[] = "Il nome del progetto e la descrizione sono obbligatori.";
            $has_errors = true;
        }

        // Handle Tutor Selection or Creation
        if (isset($_POST['tutor_option'])) {
            if ($_POST['tutor_option'] == '' && !empty($_POST['id_tutor'])) {
                $id_tutor = intval($_POST['id_tutor']);
            } elseif ($_POST['tutor_option'] == 'new' && !empty($_POST['tutor_cognome'])) {
                try {
                    // Insert new tutor
                    $sql_insert_tutor = "INSERT INTO docente (cognome) VALUES (:cognome)";
                    $stmt = $dbh->prepare($sql_insert_tutor);
                    $stmt->execute([':cognome' => $_POST['tutor_cognome']]);

                    $id_tutor = $dbh->lastInsertId();
                } catch (PDOException $e) {
                    $errors[] = "Errore nell'inserimento del tutor: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $errors[] = "Seleziona o inserisci un tutor.";
            }
        }

        // Handle Esperto Selection or Creation
        if (isset($_POST['esperto_option'])) {
            if ($_POST['esperto_option'] == '' && !empty($_POST['idEsperto'])) {
                $id_esperto = intval($_POST['idEsperto']);
            } elseif ($_POST['esperto_option'] == 'new' && !empty($_POST['esperto_cognome'])) {
                try {
                    // Insert new expert
                    $sql_insert_experto = "INSERT INTO docente (cognome) VALUES (:cognome)";
                    $stmt = $dbh->prepare($sql_insert_experto);
                    $stmt->execute([':cognome' => $_POST['esperto_cognome']]);

                    $id_esperto = $dbh->lastInsertId();
                } catch (PDOException $e) {
                    $errors[] = "Errore nell'inserimento dell'esperto: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $errors[] = "Seleziona o inserisci un esperto.";
            }
        }

        // Insert project if no errors
        if (empty($errors)) {
            try {
                $sql_insert_project = "INSERT INTO progetto (nomeProgetto, idTutor, idEsperto, descProgetto) VALUES (:nome, :tutor_id, :esperto_id, :descrizione)";
                $stmt = $dbh->prepare($sql_insert_project);
                $stmt->execute([
                    ':nome' => $nome_progetto,
                    ':tutor_id' => $id_tutor,
                    ':esperto_id' => $id_esperto,
                    ':descrizione' => $desc_progetto
                ]);

                echo "<div id='success-alert' class='alert alert-success'>Progetto aggiunto con successo!</div>";
                echo "<script>
                    setTimeout(function() {
                        document.getElementById('success-alert').style.display = 'none';
                    }, 3000);
                </script>";
            } catch (PDOException $e) {
                $errors[] = "Errore nell'inserimento del progetto: " . htmlspecialchars($e->getMessage());
            }
        }
    }

    // Fetch all docents for dropdown
    try {
        $sql_fetch_docenti = "SELECT idDocente, cognome FROM docente";
        $docenti = $dbh->query($sql_fetch_docenti)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Errore nel recupero dei docenti: " . htmlspecialchars($e->getMessage());
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
    <title>OggiInLab | Aggiungi progetto</title>
    <!-- Tema scuro con Bootswatch Cyborg -->
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
</head>
<body>
<!------MENU SECTION START-->
<?php include "includes/header.php"; ?>
<!-- MENU SECTION END-->
<div class="container mt-4">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Project Form -->
    <form method="POST" action="">
        <!-- Nome Progetto -->
        <div class="mb-3">
            <label for="nome_progetto" class="form-label">Nome Progetto</label>
            <input type="text" id="nome_progetto" name="nome_progetto" value="<?= htmlspecialchars($nome_progetto) ?>" required class="form-control">
        </div>

        <!-- Descrizione -->
        <div class="mb-3">
            <label for="desc_progetto" class="form-label">Descrizione</label>
            <textarea id="desc_progetto" name="desc_progetto" rows="4" required class="form-control"><?= htmlspecialchars($desc_progetto) ?></textarea>
        </div>

        <!-- Tutor Section -->
        <h3>Tutor</h3>
        <div class="form-check">
            <input type="radio" class="form-check-input" id="select_tutor" name="tutor_option" value="" style="display:none;">
            <label class="form-check-label" for="select_tutor">Seleziona un docente esistente:</label>
        </div>
        <?php if (!empty($docenti)): ?>
            <select name="id_tutor" id="tutorSelect" class="form-select mt-2">
                <option value="">Scegli un tutor...</option>
                <?php foreach ($docenti as $doc): ?>
                    <option value="<?= $doc['idDocente'] ?>"><?= htmlspecialchars($doc['cognome']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <div class="form-check mt-2">
            <input type="radio" class="form-check-input" id="new_tutor" name="tutor_option" value="new" data-bs-toggle="modal" data-bs-target="#tutorModal">
            <label class="form-check-label" for="new_tutor">Aggiungi un nuovo tutor:</label>
        </div>

        <!-- Esperto Section -->
        <h3 class="mt-4">Esperto</h3>
        <div class="form-check">
            <input type="radio" class="form-check-input" id="select_esperto" name="esperto_option" value="" style="display:none;">
            <label class="form-check-label" for="select_esperto">Seleziona un docente esistente:</label>
        </div>
        <?php if (!empty($docenti)): ?>
            <select name="id_esperto" id="espertoSelect" class="form-select mt-2">
                <option value="">Scegli un esperto...</option>
                <?php foreach ($docenti as $doc): ?>
                    <option value="<?= $doc['idDocente'] ?>"><?= htmlspecialchars($doc['cognome']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <div class="form-check mt-2">
            <input type="radio" class="form-check-input" id="new_esperto" name="esperto_option" value="new" data-bs-toggle="modal" data-bs-target="#espertoModal">
            <label class="form-check-label" for="new_esperto">Aggiungi un nuovo esperto:</label>
        </div>

    <!-- Submit Button -->
    <button type="submit" name="submit" class="btn btn-primary mt-4">Aggiungi Progetto</button>
    </form>



    <!-- Modal TUTOR -->
    <div class="modal fade" id="tutorModal" tabindex="-1" aria-labelledby="tutorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <form id="tutorForm" onsubmit="event.preventDefault(); addDocente('tutor');">
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



                    </div> 
<!-- FOOTER SECTION START-->
<?php include "includes/footer.php"; ?>
<!-- FOOTER SECTION END-->
<!-- Bootstrap JS and dependencies -->
 

<script>

document.addEventListener('DOMContentLoaded', function() {
     // Gestione per il form del tutor
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

    // Gestione per il form dell’esperto
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

                const option = new Option(data.docente.cognome, data.docente.id, true, true);
                select.add(option);
                select.value = data.docente.id;

                // Seleziona il radio "existing"
                document.getElementById(`select_${type}`).checked = true;

                // Chiudi il modale
                const modalId = `#${type}Modal`;
                $(modalId).modal('hide');


                // Pulisce il form
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