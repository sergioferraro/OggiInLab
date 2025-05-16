<?php
// add-admin.php
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
} else {
            // Management of Super Admin status change
            if (isset($_POST['toggle_super_admin'])) {
                $adminId = $_POST['admin_id'];
                $newStatus = $_POST['new_status'];
            
                // Verify if the user is attempting to deactivate a Super Admin
                if ($newStatus == 0) {
                    // Check the current status of the Super Admin
                    $stmt = $dbh->prepare("SELECT is_super_admin FROM admin WHERE id = :adminId");
                    $stmt->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                    $stmt->execute();
                    $currentSuperAdminStatus = $stmt->fetchColumn();
            
                    //  If the user was a Super Admin
                    if ($currentSuperAdminStatus == 1) {
                        // Count the current number of Super Admins
                        $superAdminCount = $dbh->query("SELECT COUNT(*) FROM admin WHERE is_super_admin = 1")->fetchColumn();
                        
                        // Verify if at least two Super Admins would remain after the change
                        if ($superAdminCount <= 2) {
                            echo "Impossibile disattivare il toggle: devono esserci almeno due super admin.";
                            exit;
                        }
                    }
                }
            
                // Perform the role update
                $sql = "UPDATE admin SET is_super_admin = :newStatus WHERE id = :adminId";
                $query = $dbh->prepare($sql);
                $query->bindParam(':newStatus', $newStatus, PDO::PARAM_INT);
                $query->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                $query->execute();
            }
            if (isset($_POST['delete_admin'])) {
                $adminId = $_POST['admin_id'];
            
                // Verify if the user being attempted to be deleted is a Super Admin
                $stmt = $dbh->prepare("SELECT is_super_admin FROM admin WHERE id = :adminId");
                $stmt->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                $stmt->execute();
                $isSuperAdmin = $stmt->fetchColumn();
            
                if ($isSuperAdmin === false) {
                    echo "Amministratore non trovato.";
                    exit;
                }
            
                if ($isSuperAdmin == 1) {
                    echo "Impossibile eliminare un Super Admin.";
                    exit;
                }
            
                // Execute the deletion
                $stmt = $dbh->prepare("DELETE FROM admin WHERE id = :adminId");
                $stmt->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                $stmt->execute();
            
                echo "Amministratore eliminato con successo.";
            }
            
            
    if (isset($_POST['submit'])) {
        $nomeCompleto = $_POST['nomeCompleto'];
        $adminEmail = $_POST['adminEmail'];
        $username = $_POST['userName'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        // Validate password match in plaintext
        if ($password != $confirmPassword) {
            $error = "Le password non corrispondono";
        } else {
            // Check if email or username already exists
            $stmt = $dbh->prepare("SELECT * FROM admin WHERE adminEmail=:email OR userName=:userName");
            $stmt->bindParam(':email', $adminEmail);
            $stmt->bindParam(':userName', $username); // Fixed placeholder name
            $stmt->execute();
            $result = $stmt->fetchAll();

            if ($stmt->rowCount() > 0) {
                $error = "Email o username già utilizzati";
            } else {
                // Hash password securely with bcrypt
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new admin with hashed password
                $sql = "INSERT INTO admin (nomeCompleto, adminEmail, userName, Password) VALUES (:nome, :email, :username, :password)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':nome', $nomeCompleto);
                $query->bindParam(':email', $adminEmail);
                $query->bindParam(':username', $username);
                $query->bindParam(':password', $hashedPassword);
                $query->execute();

                if ($query->rowCount() > 0) {
                    $msg = "Nuovo amministratore aggiunto con successo";
                } else {
                    $error = "Errore nell'aggiunta dell'utente";
                }
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
    <title>OggiInLab | Gestione amministratori</title>
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
        if (document.addadmin.nomeCompleto.value == "") {
            alert("Inserisci il nome completo");
            return false;
        }
        if (document.addadmin.adminEmail.value == "") {
            alert("Inserisci l'email");
            return false;
        }
        if (document.addadmin.userName.value == "") {
            alert("Inserisci lo username");
            return false;
        }
        // Fixed JavaScript variable name for confirm password
        if (document.addadmin.password.value != document.addadmin.confirmPassword.value) {
            alert("Le password non corrispondono");
            return false;
        }
        return true;
    }
</script>


<body>
    <?php include('includes/header.php'); ?>
    <div class="content-wrapper">
        <div class="container">
        <div class="row">
    <!-- Add Administrator Form -->
    <div class="col-md-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                Inserisci dati dell'amministratore
            </div>
            <div class="panel-body">
                <form role="form" name="addadmin" method="post" onSubmit="return validateForm();">
                    <div class="form-group">
                        <label>Nome completo</label>
                        <input class="form-control" type="text" name="nomeCompleto" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" type="email" name="adminEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input class="form-control" type="text" name="userName" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Conferma password</label>
                        <input class="form-control" type="password" name="confirmPassword" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-info">Aggiungi</button>
                </form>
            </div>
        </div>
    </div>

    <!-- List of Administrators -->
    <div class="col-md-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                Amministratori presenti
            </div>
            <div class="panel-body">
                <?php
                $sql = "SELECT id, nomeCompleto, adminEmail, userName, is_super_admin FROM admin ORDER BY nomeCompleto ASC";
                $query = $dbh->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);
                if ($query->rowCount() > 0) {
                    echo '<ul class="list-group">';
foreach ($results as $result) {
    echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
    echo '<div>';
    echo htmlentities($result->nomeCompleto) . ' (' . htmlentities($result->userName) . ')';
    echo '</div>';
    echo '<div class="d-flex align-items-center gap-2">';

    // Show email as a badge
    echo '<span class="badge bg-primary rounded-pill">' . htmlentities($result->adminEmail) . '</span>';

    // If the logged-in user is a Super Admin, show the toggle
    if ($_SESSION['is_super_admin'] == 1) {
        echo '<form method="post" style="margin:0">';
        echo '<input type="hidden" name="admin_id" value="' . $result->id . '">';
        echo '<input type="hidden" name="new_status" value="' . ($result->is_super_admin ? 0 : 1) . '">';
        echo '<div class="form-check form-switch">';
        echo '<input class="form-check-input" type="checkbox" onChange="this.form.submit()" ' . ($result->is_super_admin ? 'checked' : '') . '>';
        echo '</div>';
        echo '<input type="hidden" name="toggle_super_admin" value="1">';
        echo '</form>';
    
        // Reset Password Button
        echo '<button 
            type="button" 
            class="btn btn-warning btn-sm d-flex align-items-center gap-2"
            data-bs-toggle="modal" 
            data-bs-target="#resetPasswordModal" 
            data-admin-id="' . $result->id . '">
        <i class="fas fa-key"></i> Reset
        </button>';
    
        // Delete Admin Button
        echo '<form method="post" style="display:inline; margin-left:5px">';
        echo '<input type="hidden" name="admin_id" value="' . $result->id . '">';
        echo '<button 
            type="submit"
            name="delete_admin"
            value="1"
            class="btn btn-danger btn-sm d-flex align-items-center gap-2"
            onclick="return confirm(\'Sei sicuro di voler eliminare questo amministratore?\')">
        <i class="fas fa-trash-alt"></i> Elimina
        </button>';
        echo '</form>';
    
        echo '</div>';
        echo '</li>';
    }
}
echo '</ul>';
                } else {
                    echo '<div class="alert alert-warning">Nessun amministratore registrato.</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
<!--  Modal for resetting the password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title" id="resetPasswordLabel">Reset Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="assets/utils/reset_admin.php">
                    <input type="hidden" name="admin_id" id="adminId" />
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">Nuova Password</label>
                        <input type="password" class="form-control bg-dark text-white border-secondary" id="newPassword" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Conferma Password</label>
                        <input type="password" class="form-control bg-dark text-white border-secondary" id="confirmPassword" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Resetta</button>
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
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var resetModal = document.getElementById('resetPasswordModal');
        resetModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var adminId = button.getAttribute('data-admin-id');
            resetModal.querySelector('#adminId').value = adminId;
        });
    });
</script>
</body>
</html>
