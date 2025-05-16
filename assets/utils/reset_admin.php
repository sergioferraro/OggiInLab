<?php
// reset_admin.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
include('../../includes/config.php');
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = intval($_POST['admin_id']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        die("Errore: Le password non corrispondono.");
    }

    // Password hash
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

    // Update
    $sql = "UPDATE admin SET Password = :password WHERE id = :admin_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
    $query->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);

    if ($query->execute()) {
        echo "Successo: Password aggiornata con successo.";
    } else {
        echo "Errore: Non è stato possibile aggiornare la password.";
    }
} else {
    header("Location: index.php");
}
?>
