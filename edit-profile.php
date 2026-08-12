<?php
// edit-profile.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
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
    // --- CSRF validation ---
    if (empty($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = "Token di sicurezza non valido. Riprova.";
    } else {
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
    } // fine CSRF else
}

$pageTitle = 'OggiInLab | Modifica profilo';
$pageHeadScripts = '<script type="text/javascript">
    function validateForm() {
        if (document.modifyadmin.nomeCompleto.value == "") {
            alert("Inserisci il nome completo");
            return false;
        }
        if (document.modifyadmin.adminEmail.value == "") {
            alert("Inserisci l\'email");
            return false;
        }
        if (document.modifyadmin.userName.value == "") {
            alert("Inserisci l\'username");
            return false;
        }
        return true;
    }
</script>';
?>
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
                                <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>" />
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