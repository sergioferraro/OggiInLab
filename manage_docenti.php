<?php
// manage_docenti.php
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
    <title>OggiInLab | Gestione docenti</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME STYLE  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- GOOGLE FONT -->
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
        <h4 class="mb-3 text-center">Gestione Docenti</h4>

        <!-- Docenti Count Card -->
        <div class="col-md-6 mb-4">
            <div class="card bg-light" style="max-width: 18rem;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Docenti Elencati</span>
                    <button type="button" id="viewDocentiButton"
                            class="btn btn-primary"
                            data-bs-toggle="collapse"
                            href="#docentiList"
                            role="button"
                            aria-expanded="false"
                            aria-controls="docentiList">
                        Visualizza docenti
                    </button>
                </div>

                <div class="card-body text-center">
                    <?php
                        try {
                            $sql = "SELECT idDocente FROM docente";
                            $query = $dbh->prepare($sql);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_NUM);
                            $listdocenti = count($results);
                        } catch (PDOException $e) {
                            error_log("Database query error: " . $e->getMessage());
                            $listdocenti = 0;
                        }
                    ?>
                    <i class="bi bi-list-ul fa-5x"></i>
                    <h3 id="docentiCount"><?php echo intval($listdocenti); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card bg-light">
                <div class="card-header">Aggiungi un Nuovo Docente</div>
                <div class="card-body">
                    <form id="addDocenteForm">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control bg-dark text-white" id="nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="cognome" class="form-label">Cognome</label>
                            <input type="text" class="form-control bg-dark text-white" id="cognome" name="cognome" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Aggiungi Docente</button>
                    </form>
                </div>
            </div>
        </div>


        <!-- Collapsible Docenti List -->
        <div id="docentiList" class="col-md-12 collapse mt-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Risultati dei Docenti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="collapse"></button>
                </div>

                <div class="card-body p-3">
                    <?php
                    try {
                        $sql = "SELECT * FROM docente";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $docenti = $query->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($docenti)) {
                            echo "<div class='alert alert-warning'>Nessun docente trovato.</div>";
                        } else {
                    ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <?php foreach ($docenti[0] as $key => $value): ?>
                                            <th><?= htmlspecialchars(str_replace('_', ' ', ucwords($key))) ?></th>
                                        <?php endforeach; ?>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($docenti as $docente): ?>
                                        <tr>
                                            <?php foreach ($docente as $field): ?>
                                                <td><?= htmlspecialchars($field) ?></td>
                                            <?php endforeach; ?>
                                            <td>
                                                <!-- <a href="manage-docente.php?id=<?= htmlspecialchars($docente['idDocente']) ?>" class="btn btn-primary btn-modify">
                                                    <i class="fas fa-edit"></i> Modifica
                                                </a> -->
                                                <button type="button" class="btn-delete btn btn-warning" data-id="<?= htmlspecialchars($docente['idDocente']) ?>">
                                                    <i class="fas fa-lock"></i> Attiva/Disattiva
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                    <?php
                        }
                    } catch (PDOException $e) {
                        error_log("Error fetching docenti: " . $e->getMessage());
                        echo "<div class='alert alert-danger'>Errore nel recupero dei docenti.</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php';?>

<script>
$(document).ready(function() {
    // Handle docente deletion
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const $button = $(this);
        const deleteId = $button.data('id');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (confirm("Confermi il cambiamento di stato del docente?")) {
            $.ajax({
                url: 'assets/utils/delete-docente.php',
                method: 'POST',
                data: {
                    delete_id: deleteId,
                    _token: csrfToken
                },
                beforeSend: function() {
                    $button.prop('disabled', true).html('Eliminando...');
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').slideUp(300, function() {
                            $(this).remove();
                            // Update count
                            let currentCount = parseInt($('#docentiCount').text());
                            $('#docentiCount').text(currentCount - 1);
                        });
                        $button.prop('disabled', false).html('Elimina');
                    } else {
                        alert('Errore: ' + response.message);
                        $button.prop('disabled', false).html('Elimina');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error, xhr);
                    try {
                        const errResponse = JSON.parse(xhr.responseText);
                        alert('Errore di sistema: ' + (errResponse.message || 'Imprevisto'));
                    } catch {
                        alert('Errore critico: ' + error);
                    }
                    $button.prop('disabled', false).html('Elimina');
                }
            });
        }
    });
    $('#addDocenteForm').on('submit', function(e) {
        e.preventDefault();
        const formData = {
            nome: $('#nome').val(),
            cognome: $('#cognome').val()
        };

        $.ajax({
            url: 'assets/utils/add_docente.php', // Assicurati che questo percorso sia corretto
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true).text('Aggiungendo...');
            },
            success: function(response) {
                if (response.success) {
                    

                    

                    // Resetta il form
                    $('#addDocenteForm')[0].reset();
                } else {
                    alert('Errore: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error, xhr);
                try {
                    const errResponse = JSON.parse(xhr.responseText);
                    alert('Errore di sistema: ' + (errResponse.message || 'Imprevisto'));
                } catch {
                    alert('Errore critico: ' + error);
                }
            },
            complete: function() {
                $('button[type="submit"]').prop('disabled', false).text('Aggiungi Docente');
            }
        });
    });
});
</script>

<!-- SCRIPTS -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

</body>
</html>
