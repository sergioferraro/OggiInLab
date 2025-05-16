<?php
// edit-profile.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
include('includes/config.php');
error_reporting(0);

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// Retrieve current admin's details
$currentAdminStmt = $dbh->prepare("SELECT * FROM admin WHERE userName = :username");
$currentAdminStmt->bindParam(':username', $_SESSION['alogin']);
$currentAdminStmt->execute();
$currentAdmin = $currentAdminStmt->fetch();

if (!$currentAdmin) {
    header('location:index.php');
    exit;
}

$error = ""; $msg = "";

if (isset($_POST['submit'])) {
    $newNome = $_POST['nomeCompleto'];
    $newEmail = $_POST['adminEmail'];
    $newUsername = $_POST['userName'];

    // Validate required fields
    if (empty($newNome) || empty($newEmail) || empty($newUsername)) {
        $error = "Tutti i campi sono obbligatori";
    } else {
        // Check if email or username already exists (excluding current admin)
        $checkStmt = $dbh->prepare("SELECT * FROM admin WHERE (adminEmail = :email OR userName = :username) AND id != :currentID");
        $checkStmt->bindParam(':email', $newEmail);
        $checkStmt->bindParam(':username', $newUsername);
        $checkStmt->bindParam(':currentID', $currentAdmin['id']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $error = "Email o username già utilizzati da un altro utente";
        } else {
            // Update admin data
            $updateStmt = $dbh->prepare("UPDATE admin SET nomeCompleto = :nome, adminEmail = :email, userName = :username WHERE id = :currentID");
            $updateStmt->bindParam(':nome', $newNome);
            $updateStmt->bindParam(':email', $newEmail);
            $updateStmt->bindParam(':username', $newUsername);
            $updateStmt->bindParam(':currentID', $currentAdmin['id']);
            $updateStmt->execute();

            if ($updateStmt->rowCount() > 0) {
                $msg = "Dati del profilo aggiornati con successo";
            } else {
                $error = "Errore durante l'aggiornamento dei dati";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>OggiInLab | Modifica profilo</title>
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
<script type="text/javascript">
    function validateForm() {
        if (document.modifyadmin.nomeCompleto.value == "") {
            alert("Inserisci il nome completo");
            return false;
        }
        if (document.modifyadmin.adminEmail.value == "") {
            alert("Inserisci l'email");
            return false;
        }
        if (document.modifyadmin.userName.value == "") {
            alert("Inserisci l'username");
            return false;
        }
        return true;
    }
</script>

<body>
    <?php include('includes/header.php'); ?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Modifica dati profilo</h4>
                </div>
            </div>
            <?php if ($error) { ?>
                <div class="errorWrap"><strong>Errore:</strong> <?php echo htmlentities($error); ?></div>
            <?php } else if ($msg) { ?>
                <div class="succWrap"><strong>Successo:</strong> <?php echo htmlentities($msg); ?></div>
            <?php } ?>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            Aggiorna informazioni del profilo
                        </div>
                        <div class="panel-body">
                            <form role="form" name="modifyadmin" method="post" onSubmit="return validateForm();">
                                <div class="form-group">
                                    <label>Nome completo</label>
                                    <input class="form-control" type="text" name="nomeCompleto" value="<?php echo htmlentities($currentAdmin['nomeCompleto']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input class="form-control" type="email" name="adminEmail" value="<?php echo htmlentities($currentAdmin['adminEmail']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input class="form-control" type="text" name="userName" value="<?php echo htmlentities($currentAdmin['userName']); ?>" required>
                                </div>
                                <button type="submit" name="submit" class="btn btn-info">Salva modifiche</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php'); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
</html>